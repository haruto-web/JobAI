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
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect("{$frontendUrl}/login?error=google_oauth_disabled&message=" . urlencode('Google login is temporarily unavailable. Please use email/password login.'));
    }

    // Handle callback
    public function handleGoogleCallback()
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect("{$frontendUrl}/login?error=google_oauth_disabled&message=" . urlencode('Google login is temporarily unavailable. Please use email/password login.'));
    }
}
