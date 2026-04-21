<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\PaymentSourceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('v1')->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);

    Route::get('payment-sources', [PaymentSourceController::class, 'index']);
    Route::post('payment-sources', [PaymentSourceController::class, 'store']);

    Route::get('installment-plans', [InstallmentPlanController::class, 'index']);
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);

    Route::post('installment-plans', [InstallmentPlanController::class, 'store']);
});
