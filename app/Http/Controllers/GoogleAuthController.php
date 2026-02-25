<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\UserSocial;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToProvider(string $provider)
    {
        if ($provider !== 'google') {
            return response()->json(['message' => 'Provider not supported.'], 422);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google callback
     */
    public function handleProviderCallback(string $provider)
    {
        if ($provider !== 'google') {
            return response()->json(['message' => 'Provider not supported.'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Check if the user already has a social account
            $userSocial = UserSocial::where('provider', $provider)
                                     ->where('social_id', $socialUser->getId())
                                     ->first();

            if ($userSocial) {
                // Existing social account → get user
                $user = $userSocial->user;
            } else {
                // No social account → check if user exists by email (optional)
                $user = User::where('email', $socialUser->getEmail())->first();

                if (!$user) {
                    // Create new user
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'password' => Hash::make(Str::random(16)),
                        'role' => 'user',
                    ]);
                }

                // Create new social account
                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'social_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Redirect to frontend with token
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect()->away($frontendUrl . '/auth/callback?token=' . $token);

        } catch (\Exception $e) {
            Log::error("Socialite callback error for provider {$provider}: " . $e->getMessage());
            return response()->json([
                'message' => 'Authentication failed. Please try again.',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
