<?php
use Tests\Testcase;
use App\Models\Shortlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test("users can edit their short link", function () {
    $user = User::factory()->create();
    $this->actingas($user);

    $shortlink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://old-url.com",
    ]);

    $response = $this->put("/api/short-links/{$shortlink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response
        ->assertstatus(200)
        ->assertjson(["message" => "Link updated successfully"]);

    $this->assertdatabasehas("short_links", [
        "id" => $shortlink->id,
        "url" => "https://new-url.com",
    ]);
});

test("users can only update their own links", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // user 1 creates a short link
    $shortlink = ShortLink::factory()->create([
        "user_id" => $user1->id,
        "url" => "https://old-url.com",
    ]);

    $this->actingas($user2);

    $response = $this->put("/api/short-links/{$shortlink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertstatus(403);

    $this->assertdatabasehas("short_links", [
        "id" => $shortlink->id,
        "url" => "https://old-url.com",
    ]);
});

test("unauthenticated users cannot update links", function () {
    $shortlink = ShortLink::factory()->create();

    $response = $this->patchjson("/api/short-links/{$shortlink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertstatus(401);
});

test("users can delete their short link", function () {
    $user = User::factory()->create();
    $this->actingas($user);

    $shortlink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://old-url.com",
    ]);

    $response = $this->delete("/api/short-links/{$shortlink->id}");

    $response
        ->assertstatus(200)
        ->assertjson(["message" => "Link deleted successfully"]);

    $this->assertdatabasemissing("short_links", [
        "id" => $shortlink->id,
    ]);
});

test("unauthenticated users cannot delete links", function () {
    $shortLink = ShortLink::factory()->create();

    $response = $this->deleteJson("/api/short-links/{$shortLink->id}");

    $response->assertStatus(401);
});

test("users cannot delete links they don't own", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shortlink = ShortLink::factory()->create(["user_id" => $user1->id]);

    $this->actingas($user2);

    $response = $this->delete("/api/short-links/{$shortlink->id}");

    $response->assertstatus(403);

    $this->assertdatabasehas("short_links", [
        "id" => $shortlink->id,
    ]);
});

test("unauthenticated users cannot edit links", function () {
    $shortlink = ShortLink::factory()->create();

    $response = $this->putJson("/api/short-links/{$shortlink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertstatus(401);
});

test("users cannot edit others links", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shortlink = ShortLink::factory()->create(["user_id" => $user1->id]);

    $this->actingas($user2);

    $response = $this->put("/api/short-links/{$shortlink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertstatus(403);
});

test("users cannot create duplicate short links for the same url", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shortlink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
    ]);

    $response = $this->post("/api/short-links", [
        "url" => "https://example.com",
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseCount("short_links", 1);
});

test("clicks count increments when short link is visited", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shortLink = shortlink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "clicks" => 0, // start with 0 clicks
    ]);

    $response = $this->get("/{$shortLink->short_url}");

    $response->assertRedirect($shortLink->url);

    $shortLink->refresh();

    $this->assertEquals(1, $shortLink->clicks);
});

test("url must be valid", function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/short-links", [
        "url" => "invalid-url",
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(["url"]);
});

test("can redirect shortened link", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://google.com",
    ]);

    $shortCode = $shortLink->short_url;

    $redirectResponse = $this->get(
        route("web.redirect", ["shortCode" => $shortCode])
    );

    $redirectResponse->assertRedirect("https://google.com");
});

test("user can edit their short link's short code", function () {
    $user = user::factory()->create();

    $shortLink = shortlink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "short_url" => "abcd123",
    ]);

    $response = $this->actingAs($user)->put(
        "/api/short-links/{$shortLink->id}",
        [
            "short_url" => "newcode1",
        ]
    );

    $response->assertStatus(200);
    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
        "short_url" => "newcode1",
    ]);
});

test("user cannot edit short link to an existing short code", function () {
    $user = user::factory()->create();

    $existingLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com/1",
        "short_url" => "exist123",
    ]);

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com/2",
        "short_url" => "original123",
    ]);

    $response = $this->actingAs($user)->putJson(
        "/api/short-links/{$shortLink->id}",
        [
            "short_url" => "exist123",
        ]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(["short_url"]);
});

test("user cannot edit another users short link", function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $shortLink = ShortLink::factory()->create([
        "user_id" => $otherUser->id,
        "url" => "https://example.com",
        "short_url" => "other123",
    ]);

    $response = $this->actingAs($user)->putJson(
        "/api/short-links/{$shortLink->id}",
        [
            "short_url" => "mine123",
        ]
    );

    $response->assertStatus(403);
});

test("database reflects the updated short code", function () {
    $user = User::factory()->create();

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "short_url" => "abcd123",
    ]);

    $newShortCode = "newSC";
    $response = $this->actingas($user)->put(
        "/api/short-links/{$shortLink->id}",
        [
            "short_url" => $newShortCode,
        ]
    );

    $response->assertStatus(200);
    $this->assertDatabasehas("short_links", [
        "id" => $shortLink->id,
        "short_url" => $newShortCode,
    ]);
});

test("short code is generated when shortening a link", function () {
    $user = user::factory()->create();

    $response = $this->actingas($user)->post("/api/short-links", [
        "url" => "https://example.com",
    ]);

    $response->assertstatus(201)->assertjsonstructure(["short_url"]);

    $shorturl = $response->json("short_url");
    $shortcode = last(explode("/", $shorturl));

    $this->assertgreaterthanorequal(2, strlen($shortcode));
    $this->assertlessthanorequal(8, strlen($shortcode));

    $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $shortcode);
});
