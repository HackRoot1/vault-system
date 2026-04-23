<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->ip() . '|' . $request->route()?->getName();

        // Different limits for different endpoints
        $maxAttempts = match ($request->route()?->getName()) {
            'login' => 10, // 10 attempts per minute for login
            'register' => 5, // 5 attempts per minute for registration
            default => 100, // 100 requests per minute for other endpoints
        };

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key);

        return $next($request);
    }
}
