<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TransactionController;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::post('/transactions/deposit', [TransactionController::class, 'deposit'])
    ->name('transactions.deposit')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/onit/callback', [TransactionController::class, 'callback'])
    ->name('onit.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
