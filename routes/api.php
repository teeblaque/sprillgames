<?php

use App\Constants\TokenAbility;
use App\Http\Controllers\AddFundController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Transaction;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredictController;
use App\Http\Controllers\SirupaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\BlockAccess;
use App\Http\Middleware\CheckAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

#webhook
Route::post('siru-webhook', [WebhookController::class, 'confirm_payment']);

#unauthentication route
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value)->group(function () {
        Route::get('/refresh-token', [AuthController::class, 'refreshToken']);
    });

    #The OTP section
    Route::group(['prefix' => 'otp'], function () {
        Route::get('send-to-admin', [VerificationController::class, 'sendToAdmin']);
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

        #Siru Payment
        Route::group(['prefix' => 'siru'], function(){
            Route::post('initiate-payment', [SirupaymentController::class, 'initiate']);
            Route::get('confirm-payment/{reference}', [SirupaymentController::class, 'confirm']);
        });

        #Bet
        Route::group(['prefix' => 'bet'], function () {
            Route::post('special', [BetController::class, 'special']);
            Route::post('one-on-one', [BetController::class, 'oneOnOne']);
            Route::get('one-bets', [BetController::class, 'oneBets']);
            Route::get('transactions', [BetController::class, 'transactions']);
            Route::patch('join-bet/{id}', [BetController::class, 'joinBet']);

            //predict
            Route::get('fixtures', [PredictController::class, 'predictMach']);
            Route::post('predict', [PredictController::class, 'predict']);
            Route::get('pending-prediction', [PredictController::class, 'pendingBet']);
        });
    });

    Route::group(['prefix' => 'admin', 'middleware' => [CheckAdmin::class]], function() {
        Route::get('analytics', [AdminDashboardController::class, 'index']);
        Route::get('users', [AdminDashboardController::class, 'users']);
        Route::get('admin-users', [AdminDashboardController::class, 'adminUsers']);
        Route::post('admin-users', [AuthController::class, 'createAdminUsers']);
        Route::post('admin-players', [AuthController::class, 'createAdminPlayers']);
        Route::patch('user/block/{id}', [AdminDashboardController::class, 'blockUser']);
        Route::patch('user/unblock/{id}', [AdminDashboardController::class, 'unblockUser']);
        Route::get('user/{id}', [AdminDashboardController::class, 'singleUser']);
        Route::delete('user/{id}', [AdminDashboardController::class, 'deleteUser']);

        Route::post('balance_correction/{user_id}', [AdminDashboardController::class, 'balance_correction']);

        //withdrawal request
        Route::get('withdrawal-request', [AdminDashboardController::class, 'withdrawalRequest']);
        Route::patch('update-withdrawal-status/{id}', [AdminDashboardController::class, 'updateWithdrawalStatus']);

        Route::group(['prefix' => 'transactions'], function() {
            Route::get('/', [Transaction::class, 'index']);
            Route::get('special-bet', [Transaction::class, 'special_bet']);
            Route::get('one-on-one', [Transaction::class, 'one_on_one']);
            Route::get('predict', [Transaction::class, 'predict']);
        });
    });
});
