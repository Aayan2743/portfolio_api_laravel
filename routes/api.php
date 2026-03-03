<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectActionController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    // ================= AUTH =================

    // Login with OTP / Register
    Route::post('/send-otp', [OtpAuthController::class, 'sendOtp']);
    Route::post('/verify-login-otp', [OtpAuthController::class, 'verifyOtp']);

    Route::post('super-admin-login', [AuthController::class, 'super_admin_login']);
    Route::post('admin-register', [AuthController::class, 'admin_register']);
    Route::post('admin-login', [AuthController::class, 'admin_login']);
    Route::post('user-register', [AuthController::class, 'register']);
    Route::post('user-login', [AuthController::class, 'login']);

    // ================= PASSWORD / OTP =================
    Route::post('forgot-password', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::post('organization/forgot-password', [AuthController::class, 'OrgsendOtp']);

});


Route::prefix('public')->group(function () {

    Route::get('/projects', [ProjectController::class, 'getProjects']);
    Route::get('categories', [CategoryController::class, 'getCategories']);
    Route::post('contact/send', [ContactController::class, 'send']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/user-dashboard', [DashboardController::class, 'user_index']);

});

Route::prefix('admin-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::post('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);


    Route::prefix('projects')->group(function () {

        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::post('/store', [ProjectController::class, 'store']);
        Route::post('/update/{id}', [ProjectController::class, 'update']);
        Route::delete('/delete/{id}', [ProjectController::class, 'destroy']);

    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'getProfile']);
        Route::post('/update', [ProfileController::class, 'update']);
    });

    Route::prefix('config')->group(function () {
        Route::get('/email', [ConfigController::class, 'getEmailConfig']);
        Route::post('/update-email', [ConfigController::class, 'updateEmailConfig']);

        Route::get('/whatsapp', [ConfigController::class, 'getWhatsappConfig']);
        Route::post('/update-whatsapp', [ConfigController::class, 'updateWhatsappConfig']);
    });
});

Route::prefix('user-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {
       Route::post('projects/{id}/like', [ProjectActionController::class, 'toggleLike']);
        Route::post('projects/{id}/interested', [ProjectActionController::class, 'toggleInterested']);
});



