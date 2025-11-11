<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            Log::error('Google redirect error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL');
            if (!$frontendUrl || $frontendUrl === 'http://localhost:3000') {
                $frontendUrl = env('APP_ENV') === 'production' ? 'https://job-ai-liart.vercel.app' : 'http://localhost:3000';
            }
            return redirect("{$frontendUrl}/login?error=oauth_config_error");
        }
    }

    public function handleGoogleCallback()
    {
        $frontendUrl = env('FRONTEND_URL');
        if (!$frontendUrl || $frontendUrl === 'http://localhost:3000') {
            $frontendUrl = env('APP_ENV') === 'production' ? 'https://job-ai-liart.vercel.app' : 'http://localhost:3000';
        }
        
        try {
            if (!config('services.google.client_id') || !config('services.google.client_secret')) {
                Log::error('Google OAuth not configured');
                return redirect("{$frontendUrl}/login?error=oauth_not_configured");
            }
            
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $token = $user->createToken('google-login')->plainTextToken;
                return redirect("{$frontendUrl}/oauth/complete?token={$token}");
            }
            
            return redirect("{$frontendUrl}/oauth/complete?email=" . urlencode($googleUser->getEmail()) . 
                "&name=" . urlencode($googleUser->getName()) . 
                "&avatar=" . urlencode($googleUser->getAvatar() ?? '') . 
                "&provider=google");
                
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect("{$frontendUrl}/login?error=oauth_failed");
        }
    }
}
