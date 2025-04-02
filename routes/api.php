<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ShortLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return response()->json($request->user() ?? null);
});

Route::middleware("auth:sanctum")->group(function () {
    Route::put("/user", [RegisteredUserController::class, "update"]);
    Route::delete("/user", [RegisteredUserController::class, "destroy"]);
});

Route::middleware("auth:sanctum")->apiResource(
    "short-links",
    ShortLinkController::class
);
