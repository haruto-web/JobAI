<?php

use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('jobs', [JobController::class, 'index']);
Route::get('jobs/{id}', [JobController::class, 'show']);
Route::get('jobs/search', [JobController::class, 'search']);
Route::get('urgent-jobs', [JobController::class, 'urgentJobs']);
Route::get('users/search', [UserController::class, 'search']);



// Storage route for serving files
Route::get('/storage/{path}', [StorageController::class, 'show'])->where('path', '.*');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user', [AuthController::class, 'updateUser']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
    Route::post('user/profile-image', [AuthController::class, 'uploadProfileImage']);
    Route::post('user/upload-resume-for-job', [UserController::class, 'uploadResumeForJob']);
    Route::get('user/notifications', [AuthController::class, 'getNotifications']);
    Route::put('user/notifications/{id}/read', [AuthController::class, 'markNotificationAsRead']);
    Route::put('user/notifications/read-all', [AuthController::class, 'markAllNotificationsAsRead']);
    Route::post('jobs', [JobController::class, 'store']);
    Route::put('jobs/{id}', [JobController::class, 'update']);
    Route::delete('jobs/{id}', [JobController::class, 'destroy']);

    // Applications
    Route::get('applications', [ApplicationController::class, 'index']);
    Route::post('applications', [ApplicationController::class, 'store']);
    Route::put('applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('applications/{id}', [ApplicationController::class, 'destroy']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // AI chat for skills -> job suggestions
    Route::post('ai/skill-chat', [\App\Http\Controllers\Api\AiController::class, 'skillChat']);

    // AI conversational chat
    Route::post('ai/chat', [\App\Http\Controllers\Api\AiController::class, 'chat']);



    // Get chat history
    Route::get('ai/chat-history', [\App\Http\Controllers\Api\AiController::class, 'getChatHistory']);

    // Payments
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::post('manage-money', [PaymentController::class, 'manageMoney']);

    // Admin routes (only for admin users)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('admin/users', [AdminController::class, 'getUsers']);
        Route::put('admin/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('admin/users/{user}', [AdminController::class, 'deleteUser']);

        Route::get('admin/jobs', [AdminController::class, 'getJobs']);
        Route::put('admin/jobs/{job}', [AdminController::class, 'updateJob']);
        Route::delete('admin/jobs/{job}', [AdminController::class, 'deleteJob']);

        Route::get('admin/applications', [AdminController::class, 'getApplications']);
        Route::put('admin/applications/{application}', [AdminController::class, 'updateApplication']);
        Route::delete('admin/applications/{application}', [AdminController::class, 'deleteApplication']);

        Route::get('admin/payments', [AdminController::class, 'getPayments']);
    });
});
