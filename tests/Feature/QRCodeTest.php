<?php
use Tests\TestCase;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test(
    "qr code URL is generated and stored when a short link is created",
    function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a short link
        $response = $this->postJson("/api/shorten", [
            "url" => "https://example.com",
        ]);

        // Assert the response contains the short URL
        $response->assertStatus(201)->assertJsonStructure(["short_url"]);

        // Retrieve the created short link from the database
        $shortLink = ShortLink::where("url", "https://example.com")->first();

        // Assert QR code URL exists in the database
        $this->assertNotNull($shortLink->qr_code_url);

        // Check the QR code URL is a valid URL
        $this->assertTrue(
            filter_var($shortLink->qr_code_url, FILTER_VALIDATE_URL) !== false
        );
    }
);
