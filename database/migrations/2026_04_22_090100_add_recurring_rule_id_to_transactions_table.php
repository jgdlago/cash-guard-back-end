<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('recurring_rule_id')
                ->nullable()
                ->after('installment_plan_id')
                ->constrained('recurring_rules')
                ->nullOnDelete();

            $table->index(['recurring_rule_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recurring_rule_id');
        });
    }
};
