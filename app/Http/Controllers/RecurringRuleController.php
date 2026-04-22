<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreRecurringRuleRequest;
use App\Http\Requests\UpdateRecurringRuleRequest;
use App\Http\Resources\RecurringRuleResource;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\RecurringRule;
use App\Support\FinancialAudit;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecurringRuleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $rules = QueryBuilder::for(
            request()->user()->recurringRules()->withCount('transactions')
        )
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('frequency'),
                AllowedFilter::exact('status_on_generate'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('payment_source_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('next_run_from', function ($query, $value): void {
                    $query->whereDate('next_run_on', '>=', $value);
                }),
                AllowedFilter::callback('next_run_to', function ($query, $value): void {
                    $query->whereDate('next_run_on', '<=', $value);
                }),
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts(['next_run_on', 'created_at', 'amount_cents'])
            ->defaultSort('next_run_on', '-created_at')
            ->paginate(15);

        return RecurringRuleResource::collection($rules);
    }

    public function store(StoreRecurringRuleRequest $request): RecurringRuleResource
    {
        $validated = $request->validated();
        $user = $request->user();

        $paymentSourceId = $validated['payment_source_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;

        $this->assertPaymentSourceOwnership($paymentSourceId, $user->id);
        $this->assertCategoryOwnership($categoryId, $user->id);

        $type = TransactionType::from($validated['type']);
        $amountInCents = Money::parseToCents($validated['amount']);

        $rule = RecurringRule::create([
            'user_id' => $user->id,
            'payment_source_id' => $paymentSourceId,
            'category_id' => $categoryId,
            'type' => $type,
            'frequency' => $validated['frequency'],
            'status_on_generate' => $validated['status_on_generate'] ?? 'posted',
            'amount_cents' => $type === TransactionType::Expense ? -abs($amountInCents) : abs($amountInCents),
            'currency_code' => $validated['currency_code'] ?? $user->currency_code,
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
            'starts_on' => $validated['starts_on'],
            'next_run_on' => $validated['next_run_on'] ?? $validated['starts_on'],
            'ends_on' => $validated['ends_on'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        FinancialAudit::log($user->id, $rule, 'recurring_rule.created', null, FinancialAudit::attributes($rule), [
            'source' => 'api',
        ]);

        return new RecurringRuleResource($rule->loadCount('transactions'));
    }

    public function update(UpdateRecurringRuleRequest $request, RecurringRule $recurringRule): RecurringRuleResource
    {
        abort_unless($recurringRule->user_id === $request->user()->id, 404);
        $before = FinancialAudit::attributes($recurringRule);

        $validated = $request->validated();
        $paymentSourceId = $validated['payment_source_id'] ?? $recurringRule->payment_source_id;
        $categoryId = $validated['category_id'] ?? $recurringRule->category_id;

        $this->assertPaymentSourceOwnership($paymentSourceId, $request->user()->id);
        $this->assertCategoryOwnership($categoryId, $request->user()->id);

        $type = isset($validated['type']) ? TransactionType::from($validated['type']) : $recurringRule->type;
        $amountInCents = array_key_exists('amount', $validated)
            ? Money::parseToCents($validated['amount'])
            : abs($recurringRule->amount_cents);

        $recurringRule->update([
            'payment_source_id' => $paymentSourceId,
            'category_id' => $categoryId,
            'type' => $type,
            'frequency' => $validated['frequency'] ?? $recurringRule->frequency,
            'status_on_generate' => $validated['status_on_generate'] ?? $recurringRule->status_on_generate,
            'amount_cents' => $type === TransactionType::Expense ? -abs($amountInCents) : abs($amountInCents),
            'currency_code' => $validated['currency_code'] ?? $recurringRule->currency_code,
            'description' => $validated['description'] ?? $recurringRule->description,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $recurringRule->notes,
            'starts_on' => $validated['starts_on'] ?? $recurringRule->starts_on,
            'next_run_on' => $validated['next_run_on'] ?? $recurringRule->next_run_on,
            'ends_on' => array_key_exists('ends_on', $validated) ? $validated['ends_on'] : $recurringRule->ends_on,
            'is_active' => $validated['is_active'] ?? $recurringRule->is_active,
        ]);

        FinancialAudit::log(
            $request->user()->id,
            $recurringRule,
            'recurring_rule.updated',
            $before,
            FinancialAudit::attributes($recurringRule->fresh()),
            ['source' => 'api']
        );

        return new RecurringRuleResource($recurringRule->fresh()->loadCount('transactions'));
    }

    public function destroy(RecurringRule $recurringRule): JsonResponse
    {
        abort_unless($recurringRule->user_id === request()->user()->id, 404);
        $before = FinancialAudit::attributes($recurringRule);

        $recurringRule->update([
            'is_active' => false,
            'ends_on' => $recurringRule->ends_on ?? now()->toDateString(),
        ]);

        FinancialAudit::log(
            request()->user()->id,
            $recurringRule,
            'recurring_rule.deactivated',
            $before,
            FinancialAudit::attributes($recurringRule->fresh()),
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
