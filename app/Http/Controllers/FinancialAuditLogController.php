<?php

namespace App\Http\Controllers;

use App\Http\Resources\FinancialAuditLogResource;
use App\Models\FinancialAuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FinancialAuditLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $logs = QueryBuilder::for(
            FinancialAuditLog::query()->where('user_id', request()->user()->id)
        )
            ->allowedFilters([
                AllowedFilter::exact('event'),
                AllowedFilter::exact('auditable_type'),
                AllowedFilter::exact('auditable_id'),
                AllowedFilter::callback('from', function ($query, $value): void {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('to', function ($query, $value): void {
                    $query->whereDate('created_at', '<=', $value);
                }),
            ])
            ->allowedSorts(['created_at', 'event', 'auditable_type'])
            ->defaultSort('-created_at')
            ->paginate(25);

        return FinancialAuditLogResource::collection($logs);
    }
}
