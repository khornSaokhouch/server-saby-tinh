<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotSecretMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botSecret = config('app.bot_secret', env('BOT_SECRET'));
        
        if (!$botSecret || $request->header('X-Bot-Secret') !== $botSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing X-Bot-Secret header.'
            ], 401);
        }

        return $next($request);
    }
}
