<?php

namespace App\Middleware; // Ensure this matches your file path

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Use ...$roles to capture all roles passed from the route
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Get user from API guard
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // $roles is already an array because of the ... operator
        // If route is 'role:admin,owner', $roles = ['admin', 'owner']

        $userRole = strtolower(trim($user->role));
        
        // Convert all allowed roles to lowercase for comparison
        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: insufficient permissions',
                'your_role' => $userRole, // Helpful for debugging
                'allowed' => $allowedRoles
            ], 403);
        }

        return $next($request);
    }
}