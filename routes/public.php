<?php

use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('landing');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::get('/forgot-password', [AuthenticatedSessionController::class, 'forgotPassword'])->middleware('guest')->name('password.request');
Route::get('/reset-password/{token}', [AuthenticatedSessionController::class, 'showResetPasswordForm'])->middleware('guest')->name('password.reset');

Route::get('/packages/{package}', [PublicSiteController::class, 'packageShow'])->name('public.packages.show');
Route::post('/packages/{package}/book', [PublicSiteController::class, 'packageBook'])->name('public.packages.book');
Route::get('/booking/{booking}/confirmation', [PublicSiteController::class, 'bookingConfirmation'])->name('booking.confirmation');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest')->name('login.store');
Route::post('/forgot-password', [AuthenticatedSessionController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::post('/reset-password', [AuthenticatedSessionController::class, 'resetPassword'])->middleware('guest')->name('password.store');
Route::post('/quick-login', [AuthenticatedSessionController::class, 'quickLogin'])->middleware('guest')->name('login.quick');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
