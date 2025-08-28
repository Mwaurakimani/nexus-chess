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

Route::post('/onit/deposit/callback', [TransactionController::class, 'callback'])
    ->name('onit.deposit.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/transactions/withdrawal', [TransactionController::class, 'withdraw'])
    ->name('transactions.withdrawal')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/onit/withdrawal/callback', [TransactionController::class, 'callback'])
    ->name('onit.withdrawal.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
