<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\StoreInstallmentPlanRequest;
use App\Http\Resources\InstallmentPlanResource;
use App\Models\Category;
use App\Models\InstallmentPlan;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InstallmentPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = QueryBuilder::for(
            request()->user()->installmentPlans()->with('transactions')
        )
            ->allowedFilters([
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('payment_source_id'),
                AllowedFilter::callback('from_due_date', function ($query, $value): void {
                    $query->whereDate('first_due_date', '>=', $value);
                }),
                AllowedFilter::callback('to_due_date', function ($query, $value): void {
                    $query->whereDate('first_due_date', '<=', $value);
                }),
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts(['first_due_date', 'created_at', 'total_amount_cents'])
            ->defaultSort('-created_at')
            ->paginate(15);

        return InstallmentPlanResource::collection($plans);
    }

    public function store(StoreInstallmentPlanRequest $request): InstallmentPlanResource
    {
        $validated = $request->validated();
        $user = $request->user();
        $paymentSourceId = $validated['payment_source_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;

        $this->assertPaymentSourceOwnership($paymentSourceId, $user->id);
        $this->assertCategoryOwnership($categoryId, $user->id);
        $this->assertSequentialInstallments($validated['installments']);
        $totalInstallments = count($validated['installments']);
        $totalAmountInCents = collect($validated['installments'])
            ->sum(fn (array $installment) => abs(Money::parseToCents($installment['amount'])));

        $plan = DB::transaction(function () use (
            $validated,
            $user,
            $paymentSourceId,
            $categoryId,
            $totalInstallments,
            $totalAmountInCents
        ) {
            $plan = InstallmentPlan::create([
                'user_id' => $user->id,
                'payment_source_id' => $paymentSourceId,
                'category_id' => $categoryId,
                'description' => $validated['description'],
                'total_installments' => $totalInstallments,
                'total_amount_cents' => $totalAmountInCents,
                'currency_code' => $validated['currency_code'] ?? $user->currency_code,
                'first_due_date' => $validated['installments'][0]['due_date'],
            ]);

            foreach ($validated['installments'] as $installment) {
                Transaction::create([
                    'user_id' => $user->id,
                    'payment_source_id' => $paymentSourceId,
                    'category_id' => $categoryId,
                    'installment_plan_id' => $plan->id,
                    'type' => TransactionType::Expense,
                    'status' => TransactionStatus::Pending,
                    'amount_cents' => -abs(Money::parseToCents($installment['amount'])),
                    'currency_code' => $validated['currency_code'] ?? $user->currency_code,
                    'transaction_date' => $validated['transaction_date'],
                    'due_date' => $installment['due_date'],
                    'description' => $validated['description'],
                    'installment_number' => $installment['number'],
                    'total_installments' => $totalInstallments,
                ]);
            }

            return $plan->load(['transactions']);
        });

        return new InstallmentPlanResource($plan);
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

    private function assertSequentialInstallments(array $installments): void
    {
        $numbers = collect($installments)
            ->pluck('number')
            ->map(fn (mixed $number) => (int) $number)
            ->sort()
            ->values();

        $expected = collect(range(1, count($installments)));

        if ($numbers->all() !== $expected->all()) {
            throw new HttpException(422, 'Installments must be sequential.');
        }
    }
}
