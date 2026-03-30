<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    /**
     * Handle an incoming request and log it to the console.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getRequestUri();
        $timestamp = date('H:i:s');

        // Log the incoming request
        $params = $request->all();
        // Mask sensitive data if any (e.g., password, token)
        if (isset($params['password'])) { $params['password'] = '******'; }
        
        $payload = json_encode($params, JSON_UNESCAPED_UNICODE);
        Log::info("[RQ] {$method} {$uri} | Payload: {$payload}");

        $response = $next($request);

        // Log the outgoing response
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->getStatusCode();
        Log::info("[RS] {$method} {$uri} | Status: {$status} | Duration: {$duration}ms");

        return $response;
    }
}
