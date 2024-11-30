<?php

use App\Constants\TokenAbility;
use App\Http\Controllers\AddFundController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\BlockAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

#unauthentication route
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value)->group(function () {
        Route::get('/refresh-token', [AuthController::class, 'refreshToken']);
    });

     #The OTP section
     Route::group(['prefix' => 'otp'], function () {
        Route::post('resend', [VerificationController::class, 'resendOTP']);
        Route::post('validate', [VerificationController::class, 'validateOTP']);
    });

    #The Forgot Password section
    Route::group(['prefix' => 'password'], function () {
        Route::post('requestPassword', [VerificationController::class, 'resendOTP']);
        Route::post('resetPassword', [VerificationController::class, 'resetPassword']);
    });
});

#authenticated route
Route::group(['middleware' => ['auth:sanctum', BlockAccess::class]], function () {
    Route::group(['prefix' => 'user'], function () {
        #get user
        Route::get('/', [UserController::class, 'index']);

         #paystack money
         Route::group(['prefix' => 'funds'], function () {
            Route::post('verify-payment', [AddFundController::class, 'verifyPayment']);
            //authorized card
            Route::post('authorized-card', [AddFundController::class, 'authorizedCard']);
            Route::get('cards', [AddFundController::class, 'cards']);
        });
    });
});