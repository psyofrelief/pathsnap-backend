<?php

use App\Models\ShortLink;
use App\Models\User;

test(
    "qr code URL is generated and stored when a short link is created",
    function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->actingAs($user)->postJson("/api/short-links", [
            "url" => "https://example.com",
        ]);

        // Assert the response contains the short URL
        $response->assertStatus(201)->assertJsonStructure(["short_url"]);

        // Retrieve the created short link from the database
        $shortLink = ShortLink::where("url", "https://example.com")->first();

        // Reload the model to make sure QR code URL is generated
        $shortLink->refresh();

        // Assert QR code URL exists in the database
        $this->assertNotNull($shortLink->qr_code);

        // Check the QR code URL is a valid URL
        $this->assertTrue(
            filter_var($shortLink->qr_code, FILTER_VALIDATE_URL) !== false
        );
    }
);
