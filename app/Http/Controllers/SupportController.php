<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupportMail;

class SupportController extends Controller
{
    public function sendSupportEmail(Request $request)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|email|max:255",
            "message" => "required|string|min:10",
        ]);

        Mail::to(env("SUPPORT_EMAIL", "6faried@gmail.com"))->send(
            new SupportMail($validated)
        );

        return response()->json(
            ["message" => "Support email sent successfully"],
            200
        );
    }
}
