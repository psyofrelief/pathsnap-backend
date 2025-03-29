<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

test("users can authenticate using the login screen", function () {
    $user = User::factory()->create();

    $response = $this->post("/login", [
        "email" => $user->email,
        "password" => "password",
    ]);

    $this->assertAuthenticated();
    $response->assertNoContent();
});

test("users can not authenticate with invalid password", function () {
    $user = User::factory()->create();

    $this->post("/login", [
        "email" => $user->email,
        "password" => "wrong-password",
    ]);

    $this->assertGuest();
});

test("users can logout", function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post("/logout");

    $this->assertGuest();
    $response->assertNoContent();
});

test("users can delete their account", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->delete(route("auth.delete", ["id" => $user->id]));

    $this->assertGuest();
    $this->assertDatabaseMissing("users", ["id" => $user->id]);

    $response->assertStatus(200)->assertJson([
        "message" => "Account deleted successfully",
    ]);
});
