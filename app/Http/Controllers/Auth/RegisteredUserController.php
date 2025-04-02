<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Laravel\Socialite\Facades\Socialite;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            "name" => ["required", "string", "max:255"],
            "email" => [
                "required",
                "string",
                "lowercase",
                "email",
                "max:255",
                "unique:" . User::class,
            ],
            "password" => ["required", "confirmed", Rules\Password::defaults()],
        ]);

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->string("password")),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }

    /**
     * Update the authenticated user's details (excluding email).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(["message" => "Unauthorized"], 401);
        }

        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "password" => ["nullable", "confirmed", Rules\Password::defaults()],
        ]);

        $user->name = $validated["name"];

        if (!empty($validated["password"])) {
            $user->password = Hash::make($validated["password"]);
        }

        $user->save();

        return response()->json(
            ["message" => "Profile updated successfully", "user" => $user],
            200
        );
    }

    /**
     * Delete the authenticated user's account.
     */
    public function destroy()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(["message" => "Unauthorized"], 401);
        }

        // Logout user and invalidate session
        Auth::guard("web")->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Delete the user account
        $user->delete();

        return response()->json(
            ["message" => "Account deleted successfully"],
            200
        );
    }
}
