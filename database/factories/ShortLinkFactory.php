<?php
namespace Database\Factories;

use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortLinkFactory extends Factory
{
    protected $model = ShortLink::class;

    public function definition(): array
    {
        // Generate a short URL as before
        $shortUrl = substr(
            $this->faker->unique()->bothify("??###"),
            0,
            rand(2, 8)
        );

        // Generate the QR code URL based on the short URL
        $qrCodeUrl = $this->generateQRCode($shortUrl);

        return [
            "user_id" => \App\Models\User::factory(), // Create a user if needed
            "url" => $this->faker->url(),
            "short_url" => $this->faker->colorName(),
            "clicks" => $this->faker->numberBetween(0, 100),
            "qr_code" => $this->faker->url(), // Fake QR code URL or generate one
        ];
    }

    /**
     * Generate a QR code URL.
     */
    private function generateQRCode($shortUrl)
    {
        $baseUrl = config("app.url"); // Use the app's base URL from the .env file
        $url = "{$baseUrl}/r/{$shortUrl}"; // Build the full URL for the QR code
        return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" .
            urlencode($url);
    }
}
