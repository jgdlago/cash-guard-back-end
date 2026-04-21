<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransactionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = QueryBuilder::for(request()->user()->transactions())
            ->allowedFilters([
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
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts(['transaction_date', 'created_at', 'amount_cents'])
            ->defaultSort('-transaction_date', '-created_at');

        return TransactionResource::collection($query->paginate(15));
    }

    public function store(StoreTransactionRequest $request): TransactionResource
    {
        $validated = $request->validated();
        $user = $request->user();

        $paymentSourceId = $validated['payment_source_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;

        $this->assertPaymentSourceOwnership($paymentSourceId, $user->id);
        $this->assertCategoryOwnership($categoryId, $user->id);

        $type = TransactionType::from($validated['type']);
        $amountInCents = Money::parseToCents($validated['amount']);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'payment_source_id' => $paymentSourceId,
            'category_id' => $categoryId,
            'type' => $type,
            'status' => $validated['status'] ?? 'posted',
            'amount_cents' => $type === TransactionType::Expense
                ? -abs($amountInCents)
                : abs($amountInCents),
            'currency_code' => $validated['currency_code'] ?? $user->currency_code,
            'transaction_date' => $validated['transaction_date'],
            'due_date' => $validated['due_date'] ?? null,
            'posted_at' => ($validated['status'] ?? 'posted') === 'posted' ? now() : null,
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return new TransactionResource($transaction);
    }

    private function assertPaymentSourceOwnership(?int $paymentSourceId, int $userId): void
    {
        if ($paymentSourceId === null) {
            return;
        }

        $exists = PaymentSource::query()
            ->whereKey($paymentSourceId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            throw new HttpException(422, 'Invalid payment source.');
        }
    }

    private function assertCategoryOwnership(?int $categoryId, int $userId): void
    {
        if ($categoryId === null) {
            return;
        }

        $exists = Category::query()
            ->whereKey($categoryId)
            ->where(function ($query) use ($userId): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->exists();

        if (! $exists) {
            throw new HttpException(422, 'Invalid category.');
        }
    }
}
