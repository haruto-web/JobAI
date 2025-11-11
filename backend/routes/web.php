<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController; // use the GoogleController for social login

Route::get('/ping', function () {
    return 'pong';
});

Route::get('/', function () {
    try {
        return view('welcome');
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'app_running' => true,
            'laravel_version' => app()->version()
        ], 500);
    }
});

Route::get('/debug', function () {
    try {
        return response()->json([
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'app_key_set' => !empty(env('APP_KEY')),
            'db_connection' => env('DB_CONNECTION'),
            'google_configured' => !empty(config('services.google.client_id')),
            'cors_origins' => config('cors.allowed_origins'),
            'cors_patterns' => config('cors.allowed_origins_patterns')
        ]);
    } catch (\Exception $e) {
        return response($e->getMessage(), 500);
    }
});

Route::get('/cors-test', function () {
    return response()->json(['message' => 'CORS test successful', 'timestamp' => now()]);
});

Route::get('/urgent-jobs', [App\Http\Controllers\Api\JobController::class, 'urgentJobs']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/api/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index'])
    ->middleware('auth:sanctum');

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});

require __DIR__.'/auth.php';
