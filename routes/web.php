<?php

declare(strict_types=1);

use App\Http\Controllers\SteamAuthController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('auth/steam', [SteamAuthController::class, 'redirect'])->name('auth.steam');
Route::get('auth/steam/callback', [SteamAuthController::class, 'callback'])->name('auth.steam.callback');
Route::post('auth/logout', [SteamAuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('auth.logout');

Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/{any?}', fn () => view('spa'))
    ->where('any', '^(?!api|admin|livewire).*$')
    ->name('spa');
