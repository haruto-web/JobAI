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

        // stateless() is available on the two-provider implementation. Annotating the
        // $driver variable above helps static analyzers (intelephense) understand
        // the available methods and prevents false-positive "undefined method" warnings.
        return $driver->stateless()->redirect();
    }

    // Handle callback
    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(16)), // ✅ fixed Hash import
                ]
            );

            // Generate Sanctum token
            $token = $user->createToken('google-login')->plainTextToken;

            // Redirect back to frontend with token
            return redirect("http://localhost:3000/dashboard?token=$token");

        } catch (\Exception $e) {
            return redirect("http://localhost:3000/login?error=google_auth_failed");
        }
    }
}
