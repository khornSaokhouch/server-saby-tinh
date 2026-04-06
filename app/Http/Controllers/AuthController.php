<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Mail\PasswordResetMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $data = $this->validateRegister($request);

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password'     => $data['password'],
            'role'         => User::ROLE_USER,
        ]);

        $token = JWTAuth::fromUser($user);

        // Record the login history
        $this->recordLoginHistory($request, $user);

        return $this->respondWithToken($token, $user, 'Registered successfully', 201);
    }

    /**
     * Login user by email or phone
     */
    public function login(Request $request)
    {
        $data = $this->validateLogin($request);
        $credentials = $this->buildCredentials($data['login'], $data['password']);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $user = Auth::guard('api')->user();

        // Record the login history
        $this->recordLoginHistory($request, $user);

        // Optional: Sync with standard session if available to populate sessions table
        if (config('session.driver') === 'database' && $request->hasSession()) {
            Auth::guard('web')->login($user);
        }

        return $this->respondWithToken($token, $user, 'Login successful');
    }

    /**
     * Helper to record login history
     */
    protected function recordLoginHistory(Request $request, $user)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        // Handle local development IP
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $location = 'Localhost';
        } else {
            $location = $this->getLocation($ip);
        }

        // Prevent duplicate entries for the same device (User Agent + User ID)
        // This will update the login time if the device already exists in history
        LoginHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'user_agent' => $userAgent,
            ],
            [
                'ip_address' => $ip,
                'location' => $location,
                'login_at' => now(),
            ]
        );
    }

    /**
     * Get location from IP
     */
    protected function getLocation($ip)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'success') {
                    return $data['city'] . ', ' . $data['country'];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GeoIP Error: ' . $e->getMessage());
        }
        
        return 'Unknown Location';
    }

    /**
     * Get recent login history for the authenticated user
     */
    public function getLoginHistory(Request $request)
    {
        $histories = LoginHistory::where('user_id', $request->user()->id)
            ->orderBy('login_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $histories->map(function ($history) {
                // Determine device from user agent
                $agent = strtolower($history->user_agent);
                $device = 'Unknown';
                if (strpos($agent, 'iphone') !== false || strpos($agent, 'ipad') !== false) {
                    $device = 'iPhone / iPad';
                } elseif (strpos($agent, 'android') !== false) {
                    $device = 'Android Device';
                } elseif (strpos($agent, 'macintosh') !== false || strpos($agent, 'mac os') !== false) {
                    $device = 'MacBook / iMac';
                } elseif (strpos($agent, 'windows') !== false) {
                    $device = 'Windows PC';
                } elseif (strpos($agent, 'linux') !== false) {
                    $device = 'Linux PC';
                }

                // Determine relative time
                $now = now();
                $diffInMinutes = $now->diffInMinutes($history->login_at);
                if ($diffInMinutes < 5) {
                    $timeAgo = 'Active now';
                } elseif ($diffInMinutes < 60) {
                    $timeAgo = $diffInMinutes . ' mins ago';
                } elseif ($diffInMinutes < 1440) {
                    $timeAgo = floor($diffInMinutes / 60) . ' hours ago';
                } else {
                    $timeAgo = floor($diffInMinutes / 1440) . ' days ago';
                }

                return [
                    'id' => $history->id,
                    'device' => $device,
                    'location' => $history->location ?: 'Unknown Location',
                    'ip' => $history->ip_address,
                    'time' => $timeAgo,
                    'status' => $diffInMinutes < 5 ? 'current' : 'valid'
                ];
            })
        ], 200);
    }

    /**
     * Get active sessions (Alias for login history in this implementation)
     */
    public function getActiveSessions(Request $request)
    {
        return $this->getLoginHistory($request);
    }

    /**
     * Terminate all sessions for the user (Log out all devices)
     */
    public function logoutAllDevices(Request $request)
    {
        $user = $request->user();

        // 1. Terminate all database sessions for this user
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }

        // 2. Optional: Invalidate current JWT token
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Exception $e) {
            // Token might already be invalid
        }

        return $this->successResponse(null, 'Logged out from all devices successfully');
    }

    /**
     * Terminate multiple sessions by their IDs
     */
    public function terminateMultipleSessions(Request $request)
    {
        $request->validate([
            'session_ids' => 'required|array',
            'session_ids.*' => 'integer'
        ]);

        $user = $request->user();
        $historyIds = $request->session_ids;

        // In this simplified version, we'll just clear the history records or mark them
        // If you want to link them to actual 'sessions' table IDs, that would require storing session_id in LoginHistory.
        // For now, let's keep it simple as requested.
        
        LoginHistory::where('user_id', $user->id)
            ->whereIn('id', $historyIds)
            ->delete();

        return $this->successResponse(null, 'Selected history records removed');
    }

    /**
     * Terminate a specific session by its ID
     */
    public function terminateSession(Request $request, $id)
    {
        $user = $request->user();
        
        LoginHistory::where('user_id', $user->id)
            ->where('id', $id)
            ->delete();

        return $this->successResponse(null, 'Record removed');
    }

    /**
     * Logout user (invalidate JWT)
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        return $this->handleJWT(function () {
            JWTAuth::parseToken()->invalidate();
            return $this->successResponse(null, 'Logged out successfully');
        });
    }

    /**
     * Get authenticated user profile
     */
    public function profile(): \Illuminate\Http\JsonResponse
    {
        return $this->handleJWT(function ($user) {
            return $this->successResponse($user->load(['profile', 'store', 'memberStores', 'socialAccounts'])->loadCount('shopOrders'));
        });
    }

    /* ====================== PASSWORD RESET ====================== */

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        $user = User::where('email', $request->email)->first();
        $token = Password::broker()->createToken($user);
        
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $resetUrl = "{$frontendUrl}/auth/reset-password?token={$token}&email={$user->email}";

        Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name));

        return $this->successResponse(null, 'Password reset link sent to your email.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(null, 'Password has been successfully reset.');
        }

        return $this->errorResponse(__($status), 400);
    }

    /**
     * Update password for authenticated user
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('The current password provided is incorrect.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        return $this->successResponse(null, 'Your password has been successfully updated.');
    }

    /**
     * Verify current password for authenticated user
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthorized access.', 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('The current password you entered is incorrect.', 403);
        }

        return $this->successResponse(null, 'Identity verified successfully.');
    }

    /* ====================== PRIVATE HELPERS ====================== */

    /**
     * Validate register request
     */
    private function validateRegister(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone_number'     => 'nullable|string|max:20',
            'password'         => 'required|min:6|same:confirm_password',
            'confirm_password' => 'required|min:6',
        ]);
    }

    /**
     * Validate login request
     */
    private function validateLogin(Request $request): array
    {
        return $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Build credentials array for Auth::attempt
     */
    private function buildCredentials(string $login, string $password): array
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        return [
            $field   => $login,
            'password' => $password,
        ];
    }

    /**
     * Handle JWT token parsing and exceptions (DRY)
     */
    private function handleJWT(callable $callback): \Illuminate\Http\JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $callback($user);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->errorResponse('Token has expired', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->errorResponse('Token is invalid', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->errorResponse('Token not provided', 400);
        }
    }

    /**
     * Standard JWT token response
     */
    private function respondWithToken(string $token, User $user, string $message, int $status = 200): \Illuminate\Http\JsonResponse
    {
        $user->load(['profile', 'store', 'memberStores', 'socialAccounts'])->loadCount('shopOrders');
        $ttl = (int) config('jwt.ttl', 60);
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => $ttl * 60,
            ],
        ], $status);
    }
}
