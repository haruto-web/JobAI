<?php

use App\Http\Controllers\Api\{
    JobController, AuthController, ApplicationController, DashboardController,
    PaymentController, StorageController, UserController, AdminController, AiController
};
use Illuminate\Support\Facades\Route;

// ---------------- Public Routes ----------------
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
Route::post('send-password-reset', [AuthController::class, 'sendPasswordReset']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::get('jobs', [JobController::class, 'index']);
Route::get('jobs/search', [JobController::class, 'search']);
Route::get('jobs/{id}', [JobController::class, 'show']);
Route::get('urgent-jobs', [JobController::class, 'urgentJobs']);
Route::get('users/search', [UserController::class, 'search']);

// Google OAuth (use web-style redirect flow but exposed under /api for frontend)
use App\Http\Controllers\Auth\GoogleController as SocialGoogleController;
Route::get('auth/google', [SocialGoogleController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialGoogleController::class, 'handleGoogleCallback']);

// Serve storage files
Route::get('/storage/{path}', [StorageController::class, 'show'])->where('path', '.*');

// ---------------- Protected Routes ----------------
Route::middleware('auth:sanctum')->group(function () {

    // User
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user', [AuthController::class, 'updateUser']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
    Route::post('user/profile-image', [AuthController::class, 'uploadProfileImage']);
    Route::post('user/upload-resume-for-job', [UserController::class, 'uploadResumeForJob']);
    Route::get('user/notifications', [AuthController::class, 'getNotifications']);
    Route::put('user/notifications/{id}/read', [AuthController::class, 'markNotificationAsRead']);
    Route::put('user/notifications/read-all', [AuthController::class, 'markAllNotificationsAsRead']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Jobs
    Route::post('jobs', [JobController::class, 'store']);
    Route::put('jobs/{id}', [JobController::class, 'update']);
    Route::delete('jobs/{id}', [JobController::class, 'destroy']);
    Route::get('employer/jobs', [JobController::class, 'employerJobs']);

    // Applications
    Route::get('applications', [ApplicationController::class, 'index']);
    Route::post('applications', [ApplicationController::class, 'store']);
    Route::put('applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('applications/{id}', [ApplicationController::class, 'destroy']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // AI Chat
    Route::post('ai/skill-chat', [AiController::class, 'skillChat']);
    Route::post('ai/chat', [AiController::class, 'chat']);
    Route::post('ai/resume-chat', [AiController::class, 'resumeChat']);
    Route::get('ai/chat-history', [AiController::class, 'getChatHistory']);
    Route::post('ai/job-action', [AiController::class, 'jobAction']);

    // Payments
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::post('manage-money', [PaymentController::class, 'manageMoney']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('users', [AdminController::class, 'getUsers']);
        Route::put('users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('users/{user}', [AdminController::class, 'deleteUser']);

        Route::get('jobs', [AdminController::class, 'getJobs']);
        Route::put('jobs/{job}', [AdminController::class, 'updateJob']);
        Route::delete('jobs/{job}', [AdminController::class, 'deleteJob']);

        Route::get('applications', [AdminController::class, 'getApplications']);
        Route::put('applications/{application}', [AdminController::class, 'updateApplication']);
        Route::delete('applications/{application}', [AdminController::class, 'deleteApplication']);

        Route::get('payments', [AdminController::class, 'getPayments']);
    });
});
