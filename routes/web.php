<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});



//USERS ROUTES
// Route::inertia('/user', 'Home');
// Route::resource('/user', UserController::class);
Route::get('/auth/sign-up', [AuthController::class, 'signup'])->name('register');
Route::get('/auth/sign-in', [AuthController::class, 'signin'])->name('login');
Route::get('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::get('/auth/password-reset', [AuthController::class, 'resetPassword'])->name('password-reset');

// Auth::routes([
//     "verify" => true
// ]);

Route::get('/email/verify', [AuthController::class, 'verify'])->name('verify-email');


Route::middleware(['user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


// Inertia request routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password-reset-request', [AuthController::class, 'passwordResetRequest']);
Route::post('/reset-password', [AuthController::class, 'passwordResetRequest']);
Route::post('/update-password', [AuthController::class, 'updatePassword']);


Route::get('/logout', [AuthController::class, 'logout']);
