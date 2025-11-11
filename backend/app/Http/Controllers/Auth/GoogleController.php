<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 

class GoogleController extends Controller
{
    // Redirect to Google
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            \Log::error('Google OAuth redirect failed', ['error' => $e->getMessage()]);
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/login?error=oauth_error");
        }
    }

    // Handle callback
    public function handleGoogleCallback()
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if ($user) {
                // Existing user - log them in
                $token = $user->createToken('google-login')->plainTextToken;
                return redirect("{$frontendUrl}/oauth/complete?token={$token}");
            } else {
                // New user - send to registration completion
                return redirect("{$frontendUrl}/oauth/complete?email=" . urlencode($googleUser->getEmail()) . 
                    "&name=" . urlencode($googleUser->getName()) . 
                    "&avatar=" . urlencode($googleUser->getAvatar() ?? '') . 
                    "&provider=google");
            }
        } catch (\Exception $e) {
            \Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return redirect("{$frontendUrl}/login?error=oauth_failed");
        }
    }
}
