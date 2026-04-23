<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Support\FinancialAudit;
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
                AllowedFilter::partial('description'),
            )
            ->allowedSorts('transaction_date', 'created_at', 'amount_cents')
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

        FinancialAudit::log($user->id, $transaction, 'transaction.created', null, FinancialAudit::attributes($transaction), [
            'source' => 'api',
        ]);

        return new TransactionResource($transaction);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);
        $before = FinancialAudit::attributes($transaction);

        $validated = $request->validated();
        $paymentSourceId = $validated['payment_source_id'] ?? $transaction->payment_source_id;
        $categoryId = $validated['category_id'] ?? $transaction->category_id;

        $this->assertPaymentSourceOwnership($paymentSourceId, $request->user()->id);
        $this->assertCategoryOwnership($categoryId, $request->user()->id);

        $type = isset($validated['type'])
            ? TransactionType::from($validated['type'])
            : $transaction->type;

        $amountInCents = array_key_exists('amount', $validated)
            ? Money::parseToCents($validated['amount'])
            : abs($transaction->amount_cents);

        $status = $validated['status'] ?? $transaction->status->value;

        $transaction->update([
            'payment_source_id' => $paymentSourceId,
            'category_id' => $categoryId,
            'type' => $type,
            'status' => $status,
            'amount_cents' => $type === TransactionType::Expense
                ? -abs($amountInCents)
                : abs($amountInCents),
            'currency_code' => $validated['currency_code'] ?? $transaction->currency_code,
            'transaction_date' => $validated['transaction_date'] ?? $transaction->transaction_date,
            'due_date' => array_key_exists('due_date', $validated) ? $validated['due_date'] : $transaction->due_date,
            'posted_at' => $status === 'posted' ? ($transaction->posted_at ?? now()) : null,
            'description' => $validated['description'] ?? $transaction->description,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $transaction->notes,
            'cancelled_at' => $status === 'cancelled' ? ($transaction->cancelled_at ?? now()) : null,
        ]);

        FinancialAudit::log(
            $request->user()->id,
            $transaction,
            'transaction.updated',
            $before,
            FinancialAudit::attributes($transaction->fresh()),
            ['source' => 'api']
        );

        return new TransactionResource($transaction->fresh());
    }

    public function destroy(Transaction $transaction): \Illuminate\Http\JsonResponse
    {
        abort_unless($transaction->user_id === request()->user()->id, 404);
        $before = FinancialAudit::attributes($transaction);

        $transaction->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'posted_at' => null,
        ]);

        FinancialAudit::log(
            request()->user()->id,
            $transaction,
            'transaction.cancelled',
            $before,
            FinancialAudit::attributes($transaction->fresh()),
            ['source' => 'api']
        );

        return response()->json(status: 204);
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
