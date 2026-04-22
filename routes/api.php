<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryUserPreferenceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\PaymentSourceController;
use App\Http\Controllers\RecurringRuleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('dashboard', [DashboardController::class, 'show']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}/preferences', [CategoryUserPreferenceController::class, 'upsert']);
        Route::delete('categories/{category}/preferences', [CategoryUserPreferenceController::class, 'destroy']);

        Route::get('payment-sources', [PaymentSourceController::class, 'index']);
        Route::post('payment-sources', [PaymentSourceController::class, 'store']);

        Route::get('recurring-rules', [RecurringRuleController::class, 'index']);
        Route::post('recurring-rules', [RecurringRuleController::class, 'store']);
        Route::patch('recurring-rules/{recurringRule}', [RecurringRuleController::class, 'update']);
        Route::delete('recurring-rules/{recurringRule}', [RecurringRuleController::class, 'destroy']);

        Route::get('installment-plans', [InstallmentPlanController::class, 'index']);
        Route::delete('installment-plans/{installmentPlan}', [InstallmentPlanController::class, 'destroy']);
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::patch('transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

        Route::post('installment-plans', [InstallmentPlanController::class, 'store']);
    });
});
