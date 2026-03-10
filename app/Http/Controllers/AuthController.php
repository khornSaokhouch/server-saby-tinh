<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

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

        return $this->respondWithToken($token, Auth::guard('api')->user(), 'Login successful');
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
            return $this->successResponse($user->load(['profile', 'store', 'socialAccounts'])->loadCount('shopOrders'));
        });
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
        $user->load(['profile', 'store', 'socialAccounts'])->loadCount('shopOrders');
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
