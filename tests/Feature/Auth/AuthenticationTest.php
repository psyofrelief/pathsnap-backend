<?php

use App\Models\User;

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
    // Create a user
    $user = User::factory()->create();

    // Authenticate the user
    $response = $this->actingAs($user);

    // Send DELETE request to delete the account
    $response = $this->delete("/auth/delete"); // Adjust this route to match your actual route

    // Assert that the user is logged out
    $this->assertGuest();

    // Assert the account is deleted from the database
    $this->assertDatabaseMissing("users", [
        "email" => $user->email,
    ]);

    // Assert that the response is successful and provides the expected message
    $response->assertStatus(200)->assertJson([
        "message" => "Account deleted successfully",
    ]);
});

test("users cannot delete another user's account", function () {
    // Create two users
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    // Authenticate as the second user (not the account owner)
    $response = $this->actingAs($anotherUser);

    // Send DELETE request to delete the first user's account
    $response = $this->delete("/api/account"); // Adjust this route to match your actual route

    // Assert the account is not deleted from the database
    $this->assertDatabaseHas("users", [
        "email" => $user->email,
    ]);

    // Assert that the response is forbidden (status code 403)
    $response->assertStatus(403); // 403 Forbidden, or other appropriate status
});
