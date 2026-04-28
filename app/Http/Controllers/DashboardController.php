<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = Carbon::parse($request->query('month', now()->format('Y-m-01')))->startOfMonth();
        $start = $month->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $baseQuery = QueryBuilder::for(
            Transaction::query()
                ->where('user_id', $user->id)
                ->whereDate('transaction_date', '>=', $start)
                ->whereDate('transaction_date', '<=', $end)
                ->where('status', '!=', 'cancelled')
        )
            ->allowedFilters(
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('payment_source_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('from', function ($query, $value): void {
                    $query->whereDate('transaction_date', '>=', $value);
                }),
                AllowedFilter::callback('to', function ($query, $value): void {
                    $query->whereDate('transaction_date', '<=', $value);
                }),
            )
            ->allowedSorts('transaction_date', 'created_at', 'amount_cents');

        $incomeCents = (int) (clone $baseQuery)
            ->where('type', TransactionType::Income->value)
            ->sum('amount_cents');

        $expenseCents = abs((int) (clone $baseQuery)
            ->where('type', TransactionType::Expense->value)
            ->sum('amount_cents'));

        $balanceCents = $incomeCents - $expenseCents;

        $expensesByCategory = (clone $baseQuery)
            ->selectRaw('category_id, COALESCE(SUM(ABS(amount_cents)), 0) as total_cents')
            ->where('type', TransactionType::Expense->value)
            ->with('category:id,name,slug')
            ->groupBy('category_id')
            ->orderByDesc('total_cents')
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'category_id' => $transaction->category_id,
                'category_name' => $transaction->category?->name,
                'category_slug' => $transaction->category?->slug,
                'total_cents' => (int) $transaction->total_cents,
                'total' => Money::formatFromCents((int) $transaction->total_cents),
            ])
            ->values();

        return response()->json([
            'data' => [
                'month' => $month->format('Y-m'),
                'period' => ['from' => $start, 'to' => $end],
                'summary' => [
                    'income_cents' => $incomeCents,
                    'income' => Money::formatFromCents($incomeCents),
                    'expense_cents' => $expenseCents,
                    'expense' => Money::formatFromCents($expenseCents),
                    'balance_cents' => $balanceCents,
                    'balance' => Money::formatFromCents($balanceCents),
                ],
                'expenses_by_category' => $expensesByCategory,
            ],
        ]);
    }
}
