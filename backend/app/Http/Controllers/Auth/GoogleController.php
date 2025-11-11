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
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        // Force the redirect URL to the backend callback route so Google returns
        // to the server (not the frontend) regardless of environment config.
        // Use Laravel's url() helper which respects APP_URL.
        $callbackUrl = url('/auth/google/callback');

        // stateless() is available on the two-provider implementation. Annotating the
        // $driver variable above helps static analyzers (intelephense) understand
        // the available methods and prevents false-positive "undefined method" warnings.
        return $driver->stateless()->redirectUrl($callbackUrl)->redirect();
    }

    // Handle callback
    public function handleGoogleCallback()
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if ($user) {
                $token = $user->createToken('google-login')->plainTextToken;
                return redirect("{$frontendUrl}/oauth/complete?token={$token}");
            } else {
                $email = urlencode($googleUser->getEmail());
                $name = urlencode($googleUser->getName());
                $avatar = urlencode($googleUser->getAvatar() ?? '');
                return redirect("{$frontendUrl}/oauth/complete?email={$email}&name={$name}&avatar={$avatar}&provider=google");
            }
        } catch (\Throwable $e) {
            \Log::error('Google OAuth callback error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect("{$frontendUrl}/login?error=oauth_failed");
        }
    }
}
