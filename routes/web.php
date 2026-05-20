<?php

declare(strict_types=1);

use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [FinanceController::class, 'dashboard'])->name('dashboard');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('history', [FinanceController::class, 'history'])->name('history');
        Route::get('deposit', [FinanceController::class, 'showDeposit'])->name('show-deposit');
        Route::post('deposit', [FinanceController::class, 'deposit'])->name('deposit');
        Route::get('transfer', [FinanceController::class, 'showTransfer'])->name('show-transfer');
        Route::post('transfer', [FinanceController::class, 'transfer'])->name('transfer');
        Route::post('reverse/{subledger}', [FinanceController::class, 'reverse'])->name('reverse');
    });
});

require __DIR__.'/settings.php';
