<?php

namespace Tests\Feature;

use App\Models\FinancialAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialAuditLogsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_authenticated_users_audit_logs(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        FinancialAuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => 'transaction',
            'auditable_id' => 10,
            'event' => 'transaction.created',
            'before' => null,
            'after' => ['amount_cents' => -1000],
            'context' => ['source' => 'api'],
        ]);

        FinancialAuditLog::create([
            'user_id' => $otherUser->id,
            'auditable_type' => 'transaction',
            'auditable_id' => 11,
            'event' => 'transaction.created',
            'before' => null,
            'after' => ['amount_cents' => -2000],
            'context' => ['source' => 'api'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/financial-audit-logs');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.auditable_id', 10);
    }
}
