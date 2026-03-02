<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OtpAuthController;
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

Route::prefix('admin-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

});

Route::prefix('user-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

});
