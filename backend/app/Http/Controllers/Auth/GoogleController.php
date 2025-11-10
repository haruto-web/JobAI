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
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');

            // Ensure the provider uses the backend callback URL when retrieving
            // the user information (this prevents mismatched redirect URIs).
            $callbackUrl = url('/auth/google/callback');
            $googleUser = $driver->stateless()->redirectUrl($callbackUrl)->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            
            if ($user) {
                // User exists, log in
                $token = $user->createToken('google-login')->plainTextToken;
                // Redirect to frontend oauth completion page which will postMessage back to opener or handle direct redirect
                return redirect("{$frontendUrl}/oauth/complete?token=$token");
            } else {
                // User does not exist, redirect to frontend oauth completion page with profile details
                $email = urlencode($googleUser->getEmail());
                $name = urlencode($googleUser->getName());
                $avatar = urlencode($googleUser->getAvatar());
                return redirect("{$frontendUrl}/oauth/complete?email={$email}&name={$name}&avatar={$avatar}&provider=google");
            }

        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/login?error=google_auth_failed");
        }
    }
}
