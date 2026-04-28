<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryUserPreferenceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAuditLogController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\PaymentSourceController;
use App\Http\Controllers\RecurringRuleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware(HandlePrecognitiveRequests::class);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware(HandlePrecognitiveRequests::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('dashboard', [DashboardController::class, 'show']);
        Route::get('financial-audit-logs', [FinancialAuditLogController::class, 'index']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store'])->middleware(HandlePrecognitiveRequests::class);
        Route::put('categories/{category}/preferences', [CategoryUserPreferenceController::class, 'upsert'])->middleware(HandlePrecognitiveRequests::class);
        Route::delete('categories/{category}/preferences', [CategoryUserPreferenceController::class, 'destroy']);

        Route::get('payment-sources', [PaymentSourceController::class, 'index']);
        Route::post('payment-sources', [PaymentSourceController::class, 'store'])->middleware(HandlePrecognitiveRequests::class);

        Route::get('recurring-rules', [RecurringRuleController::class, 'index']);
        Route::post('recurring-rules', [RecurringRuleController::class, 'store'])->middleware(HandlePrecognitiveRequests::class);
        Route::patch('recurring-rules/{recurringRule}', [RecurringRuleController::class, 'update'])->middleware(HandlePrecognitiveRequests::class);
        Route::delete('recurring-rules/{recurringRule}', [RecurringRuleController::class, 'destroy']);

        Route::get('installment-plans', [InstallmentPlanController::class, 'index']);
        Route::delete('installment-plans/{installmentPlan}', [InstallmentPlanController::class, 'destroy']);
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store'])->middleware(HandlePrecognitiveRequests::class);
        Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->middleware(HandlePrecognitiveRequests::class);
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

        Route::post('installment-plans', [InstallmentPlanController::class, 'store'])->middleware(HandlePrecognitiveRequests::class);
    });
});
