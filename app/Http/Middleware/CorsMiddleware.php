<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow p-s.co, www.p-s.co, and q.p-s.co domains
        $origin = $request->header("Origin");
        if (
            $origin == "http://localhost" ||
            $origin == "http://localhost:8000" ||
            $origin == "http://localhost:3000" ||
            $origin == "https://p-s.co" ||
            $origin == "https://www.p-s.co" ||
            $origin == "https://q.p-s.co"
        ) {
            $response = $next($request);
            $response->headers->set("Access-Control-Allow-Origin", $origin);
        } else {
            // If origin is not allowed, prevent the request from continuing
            return response()->json(["error" => "CORS not allowed"], 403);
        }

        // Handle preflight OPTIONS requests
        if ($request->getMethod() == "OPTIONS") {
            $response = response("", 200); // Respond with 200 OK
            $response->headers->set(
                "Access-Control-Allow-Methods",
                "GET, POST, PUT, DELETE, OPTIONS"
            );
            $response->headers->set(
                "Access-Control-Allow-Headers",
                "Origin, Content-Type, Authorization"
            );
            $response->headers->set("Access-Control-Allow-Credentials", "true");
            return $response;
        }

        // Default response for other HTTP methods
        $response->headers->set(
            "Access-Control-Allow-Methods",
            "GET, POST, PUT, DELETE, OPTIONS"
        );
        $response->headers->set(
            "Access-Control-Allow-Headers",
            "Origin, Content-Type, Authorization"
        );
        $response->headers->set("Access-Control-Allow-Credentials", "true");

        return $response;
    }
}
