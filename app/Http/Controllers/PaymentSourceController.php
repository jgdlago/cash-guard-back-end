<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentSourceRequest;
use App\Http\Resources\PaymentSourceResource;
use App\Models\PaymentSource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentSourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $paymentSources = QueryBuilder::for(
            request()->user()->paymentSources()
        )
            ->allowedFilters(
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('parent_payment_source_id'),
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('display_order', 'name', 'created_at')
            ->defaultSort('display_order', 'name')
            ->get();

        return PaymentSourceResource::collection($paymentSources);
    }

    public function store(StorePaymentSourceRequest $request): PaymentSourceResource
    {
        $validated = $request->validated();
        $parentPaymentSourceId = $validated['parent_payment_source_id'] ?? null;

        if ($parentPaymentSourceId !== null) {
            $exists = PaymentSource::query()
                ->whereKey($parentPaymentSourceId)
                ->where('user_id', $request->user()->id)
                ->exists();

            abort_unless($exists, 422, 'Invalid parent payment source.');
        }

        $paymentSource = PaymentSource::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'currency_code' => $validated['currency_code'] ?? $request->user()->currency_code ?? 'BRL',
            'parent_payment_source_id' => $parentPaymentSourceId,
            'credit_limit_cents' => $validated['credit_limit'] ?? null,
            'statement_closing_day' => $validated['statement_closing_day'] ?? null,
            'statement_due_day' => $validated['statement_due_day'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        return new PaymentSourceResource($paymentSource);
    }
}
