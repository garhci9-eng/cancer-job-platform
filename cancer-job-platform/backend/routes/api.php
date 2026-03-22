<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\JobMatchController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\LegalGuideController;

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Authenticated
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // User profile
    Route::get('/profile',    [UserProfileController::class, 'show']);
    Route::put('/profile',    [UserProfileController::class, 'update']);

    // Job matching
    Route::get('/jobs',           [JobMatchController::class, 'index']);
    Route::get('/jobs/{id}',      [JobMatchController::class, 'show']);
    Route::post('/jobs/{id}/save', [JobMatchController::class, 'save']);
    Route::get('/jobs/saved',     [JobMatchController::class, 'saved']);

    // AI Chat (SSE streaming)
    Route::post('/chat',          [ChatController::class, 'send']);
    Route::get('/chat/history',   [ChatController::class, 'history']);
    Route::delete('/chat/clear',  [ChatController::class, 'clear']);

    // Community
    Route::get('/community/posts',         [CommunityController::class, 'index']);
    Route::post('/community/posts',        [CommunityController::class, 'store']);
    Route::get('/community/posts/{id}',    [CommunityController::class, 'show']);
    Route::post('/community/posts/{id}/reply', [CommunityController::class, 'reply']);
    Route::get('/community/mentors',       [CommunityController::class, 'mentors']);
    Route::post('/community/mentor-request', [CommunityController::class, 'mentorRequest']);

    // Legal guide
    Route::get('/legal/guides',      [LegalGuideController::class, 'index']);
    Route::get('/legal/guides/{id}', [LegalGuideController::class, 'show']);
});
