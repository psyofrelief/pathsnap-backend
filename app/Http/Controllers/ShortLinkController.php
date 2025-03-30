<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ShortLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shortLinks = Auth::user()->links;
        return response()->json($shortLinks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => "string", // Allow title to be missing but still stored
            "url" => "required|url",
            "short_url" =>
                "nullable|string|min:2|max:8|unique:short_links,short_url",
        ]);

        $user = Auth::user();
        Log::info("Received data:", $request->all());

        // Check if the user already has a short link for the same URL
        if (
            ShortLink::where("user_id", $user->id)
                ->where("url", $data["url"])
                ->exists()
        ) {
            return response()->json(
                ["message" => "You already have a short link for this URL."],
                422
            );
        }

        $data["user_id"] = $user->id;
        $data["short_url"] = $data["short_url"] ?? $this->generateShortCode();
        $data["qr_code"] = $this->generateQRCode($data["short_url"]);

        $shortLink = ShortLink::create($data);

        Log::info("Received link:", [$shortLink]);

        return response()->json($shortLink, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShortLink $shortLink)
    {
        if ($shortLink->user_id !== Auth::user()->id) {
            return response()->json(
                ["message" => "You are not authorised to update this link."],
                403
            );
        }

        $data = $request->validate([
            "title" => "string",
            "url" => "sometimes|required|url",
            "short_url" => [
                "sometimes",
                "string",
                "min:2",
                "max:8",
                Rule::unique("short_links", "short_url")->ignore(
                    $shortLink->id
                ),
            ],
        ]);

        // Update the short link
        $shortLink->update($data);

        return response()->json(["message" => "Link updated successfully"]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the short link by ID
        $shortLink = ShortLink::findOrFail($id);

        // Check if the authenticated user is the owner of the short link
        if ($shortLink->user_id !== auth()->id()) {
            return response()->json(
                ["message" => "You are not authorized to delete this link."],
                403 // Forbidden status code
            );
        }

        // If the user is the owner, proceed to delete the link
        $shortLink->delete();

        return response()->json(
            ["message" => "Link deleted successfully"],
            200
        );
    }

    /**
     * Generate a unique short code.
     */
    private function generateShortCode()
    {
        $length = rand(2, 8);

        do {
            $shortCode = substr(
                str_shuffle(
                    "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
                ),
                0,
                $length
            );
        } while (ShortLink::where("short_url", $shortCode)->exists());

        return $shortCode;
    }

    public function redirect($shortCode)
    {
        $shortLink = ShortLink::where("short_url", $shortCode)->firstOrFail();
        $shortLink->increment("clicks");

        return redirect()->away($shortLink->url);
    }

    /**
     * Generate a QR code URL for the given short link.
     */
    private function generateQRCode($shortUrl)
    {
        $baseUrl = config("app.url");
        $url = "{$baseUrl}/r/{$shortUrl}"; // Build the full URL for the QR code
        return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" .
            urlencode($url);
    }
}
