<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});



//USERS ROUTES
// Route::inertia('/user', 'Home');
Route::resource('/user', UserController::class);
Route::get('/auth/sign-up', function () {
    return Inertia::render('Auth/SignUp');
})->name('register');
Route::get('/auth/sign-in', function () {
    return Inertia::render('Auth/SignIn');
})->name('login');

Route::middleware(['user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
   
});

Route::post('/login', [UserController::class, 'login']);
Route::get('/logout', [UserController::class, 'logout']);
