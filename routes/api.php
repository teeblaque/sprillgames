<?php

use App\Constants\TokenAbility;
use App\Http\Controllers\AddFundController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WithdrawalController;
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
     //banks
     Route::get('banks', [AddFundController::class, 'banks']);
     //resolve account number
     Route::post('resolve-account', [AddFundController::class, 'resolveAccount']);
     //initiate pin
     Route::post('initiate-transaction', [AddFundController::class, 'initiateTransaction']);

    Route::group(['prefix' => 'user'], function () {
        Route::group(['prefix' => 'dashboard'], function(){
            Route::get('analytics', [DashboardController::class, 'index']);
            Route::get('transactions', [DashboardController::class, 'transactions']);
        });
        //create transaction pin
        Route::post('update-transaction-pin', [UserController::class, 'updateTransactionPin']);
        #get user
        Route::get('/', [UserController::class, 'index']);
        Route::delete('/remove-account', [UserController::class, 'remove']);
        Route::put('/update', [UserController::class, 'update']);

        Route::post('/add-bank', [BankController::class, 'store']);
        Route::delete('delete-bank/{id}', [BankController::class, 'removeBank']);
        Route::delete('delete-card/{id}', [BankController::class, 'removeCard']);
        Route::get('/banks', [BankController::class, 'index']);

        //transaction history
        Route::get('transaction-history', [UserController::class, 'transactionHistory']);

        #paystack money
        Route::group(['prefix' => 'funds'], function () {
            Route::post('verify-payment', [AddFundController::class, 'verifyPayment']);
            //authorized card
            Route::post('authorized-card', [AddFundController::class, 'authorizedCard']);
            Route::get('cards', [AddFundController::class, 'cards']);
        });

        #withdrawal
        Route::group(['prefix' => 'withdrawal'], function () {
            Route::post('/', [WithdrawalController::class, 'store']);
            Route::get('/', [WithdrawalController::class, 'index']);
            Route::post('/cancel/{id}', [WithdrawalController::class, 'cancel']);
        });

        #Bet
        Route::group(['prefix' => 'bet'], function () {
            Route::post('special', [BetController::class, 'special']);
            Route::post('one-on-one', [BetController::class, 'oneOnOne']);
            Route::get('one-bets', [BetController::class, 'oneBets']);
            Route::get('transactions', [BetController::class, 'transactions']);
            Route::patch('join-bet/{id}', [BetController::class, 'joinBet']);
        });
    });
});
