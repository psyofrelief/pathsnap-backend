<?php

use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return ["Laravel" => app()->version()];
});

Route::get("/{shortCode}", [ShortLinkController::class, "redirect"])->name(
    "web.redirect"
);

require __DIR__ . "/auth.php";
