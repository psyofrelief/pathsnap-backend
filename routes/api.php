<?php

use App\Http\Controllers\ShortLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return response()->json($request->user() ?? null);
});

Route::middleware("auth:sanctum")->apiResource(
    "short-links",
    ShortLinkController::class
);
