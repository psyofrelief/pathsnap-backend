<?php
use Tests\TestCase;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test("users can edit their short link", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://old-url.com",
    ]);

    $response = $this->put("/api/shorten/{$shortLink->id}", [
        "url" => "https://new-url.com",
    ]);

    // Assert response and check if database is updated
    $response
        ->assertStatus(200)
        ->assertJson(["message" => "Link updated successfully"]);

    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
        "url" => "https://new-url.com",
    ]);
});

test("users can only update their own links", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // User 1 creates a short link
    $shortLink = ShortLink::factory()->create([
        "user_id" => $user1->id,
        "url" => "https://old-url.com",
    ]);

    // User 2 tries to update User 1's short link
    $this->actingAs($user2);

    $response = $this->patchJson("/api/shorten/{$shortLink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertStatus(403);

    // Ensure the link has NOT been updated
    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
        "url" => "https://old-url.com",
    ]);
});

test("unauthenticated users cannot update links", function () {
    $shortLink = ShortLink::factory()->create();

    $response = $this->patchJson("/api/shorten/{$shortLink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertStatus(401);
});

test("users can delete their short link", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://old-url.com",
    ]);

    $response = $this->delete("/api/shorten/{$shortLink->id}");

    $response
        ->assertStatus(200)
        ->assertJson(["message" => "Link deleted successfully"]);

    // Ensure the link is removed from the database
    $this->assertDatabaseMissing("short_links", [
        "id" => $shortLink->id,
    ]);
});

test("unauthenticated users cannot delete links", function () {
    $shortLink = ShortLink::factory()->create();

    $response = $this->delete("/api/shorten/{$shortLink->id}");

    $response->assertStatus(401);
});

test("users cannot delete links they don't own", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shortLink = ShortLink::factory()->create(["user_id" => $user1->id]);

    $this->actingAs($user2);

    $response = $this->delete("/api/shorten/{$shortLink->id}");

    $response->assertStatus(403); // Forbidden

    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
    ]);
});

test("unauthenticated users cannot edit links", function () {
    $shortLink = ShortLink::factory()->create();

    $response = $this->put("/api/shorten/{$shortLink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertStatus(401);
});

test("users cannot edit others links", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shortLink = ShortLink::factory()->create(["user_id" => $user1->id]);

    $this->actingAs($user2);

    $response = $this->put("/api/shorten/{$shortLink->id}", [
        "url" => "https://new-url.com",
    ]);

    $response->assertStatus(403); // Forbidden
});

test("users cannot create duplicate short links for the same URL", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a short link
    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
    ]);

    // Try creating another short link with the same URL
    $response = $this->postJson("/api/shorten", [
        "url" => "https://example.com",
    ]);

    // Assert validation failure
    $response->assertStatus(422)->assertJsonValidationErrors(["url"]);

    // Ensure only one record exists for this URL
    $this->assertDatabaseCount("short_links", 1);
});

test("clicks count increments when short link is visited", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a short link
    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "clicks" => 0, // Start with 0 clicks
    ]);

    // Simulate visiting the short link
    $response = $this->get("/{$shortLink->short_url}");

    // Assert the response is a redirect to the original URL
    $response->assertRedirect($shortLink->url);

    // Reload the short link from the database
    $shortLink->refresh();

    // Assert that the clicks count has incremented by 1
    $this->assertEquals(1, $shortLink->clicks);
});

use App\Models\User;
use App\Models\ShortLink;

test("user can only have a maximum of 15 links", function () {
    // Create a user
    $user = User::factory()->create();

    // Create 15 short links for the user
    ShortLink::factory()
        ->count(15)
        ->create([
            "user_id" => $user->id,
        ]);

    // Assert that the user now has 15 links
    $this->assertCount(15, $user->shortLinks);

    // Try to create a 16th link
    $response = $this->actingAs($user)->post("/api/shorten", [
        "url" => "https://example.com/16",
    ]);

    // Assert that the response indicates the creation failed (e.g., validation error)
    $response->assertStatus(422); // or whatever status your validation returns

    // Assert that the user still only has 15 links
    $this->assertCount(15, $user->fresh()->shortLinks);
});

test("URL must be valid", function () {
    $user = User::factory()->create();

    // Try to create an invalid URL
    $response = $this->actingAs($user)->post("/api/shorten", [
        "url" => "invalid-url",
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(["url"]);
});

test("can redirect shortened link", function () {
    $response = $this->postJson("/api/shorten", [
        "url" => "https://example.com",
    ]);

    $response->assertStatus(201)->assertJsonStructure(["short_url"]);

    // Extract short code from the response
    $shortUrl = $response->json("short_url");
    $shortCode = last(explode("/", $shortUrl)); // Extract the short code from URL

    $redirectResponse = $this->get("/$shortCode");

    $redirectResponse->assertRedirect("https://example.com");
});

test("can shorten link", function () {
    $response = $this->post("/api/shorten", [
        "url" => "https://example.com",
    ]);

    $response->assertStatus(201)->assertJsonStructure(["short_url"]);
});

test('user cannot access another user\'s short links', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $shortLink = ShortLink::factory()->create([
        "user_id" => $otherUser->id,
        "url" => "https://example.com",
    ]);

    // Authenticate as a different user
    $response = $this->actingAs($user)->get("/api/shorten/{$shortLink->id}");

    $response->assertStatus(403);
});

test("user can edit their short link's short code", function () {
    $user = User::factory()->create();

    // Create a short link
    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "short_url" => "abcd123",
    ]);

    // Edit the short link's short URL
    $response = $this->actingAs($user)->put("/api/shorten/{$shortLink->id}", [
        "short_url" => "newcode123",
    ]);

    // Assert that the short link was updated successfully
    $response->assertStatus(200);
    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
        "short_url" => "newcode123",
    ]);
});

test("user cannot edit short link to an existing short code", function () {
    $user = User::factory()->create();

    // Create two short links, one of which will be used as an "existing" code
    $existingLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com/1",
        "short_url" => "existing123",
    ]);

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com/2",
        "short_url" => "original123",
    ]);

    // Attempt to change the short code to the existing one
    $response = $this->actingAs($user)->put("/api/shorten/{$shortLink->id}", [
        "short_url" => "existing123",
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(["short_url"]);
});

test('user cannot edit another user\'s short link', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create a short link for the other user
    $shortLink = ShortLink::factory()->create([
        "user_id" => $otherUser->id,
        "url" => "https://example.com",
        "short_url" => "otheruser123",
    ]);

    // Attempt to edit another user's short link
    $response = $this->actingAs($user)->put("/api/shorten/{$shortLink->id}", [
        "short_url" => "newshortcode",
    ]);

    $response->assertStatus(403);
});

test("database reflects the updated short code", function () {
    $user = User::factory()->create();

    $shortLink = ShortLink::factory()->create([
        "user_id" => $user->id,
        "url" => "https://example.com",
        "short_url" => "abcd123",
    ]);

    // Edit the short link's short URL
    $newShortCode = "newshortcode";
    $response = $this->actingAs($user)->put("/api/shorten/{$shortLink->id}", [
        "short_url" => $newShortCode,
    ]);

    // Assert the change in the database
    $response->assertStatus(200);
    $this->assertDatabaseHas("short_links", [
        "id" => $shortLink->id,
        "short_url" => $newShortCode,
    ]);
});

test("short code is generated when shortening a link", function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post("/api/shorten", [
        "url" => "https://example.com",
    ]);

    // Assert the response status and structure
    $response->assertStatus(201)->assertJsonStructure(["short_url"]);

    // Extract the short code from the response
    $shortUrl = $response->json("short_url");
    $shortCode = last(explode("/", $shortUrl)); // Extract the short code from the URL

    // Validate the length of the short code
    $this->assertGreaterThanOrEqual(2, strlen($shortCode));
    $this->assertLessThanOrEqual(8, strlen($shortCode));

    // Optionally, check that it only contains alphanumeric characters
    $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $shortCode);
});
