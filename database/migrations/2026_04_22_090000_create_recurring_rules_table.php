<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_source_id')->nullable()->constrained('payment_sources')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('frequency', 20);
            $table->string('status_on_generate', 20)->default('posted');
            $table->bigInteger('amount_cents');
            $table->char('currency_code', 3)->default('BRL');
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->date('starts_on');
            $table->date('next_run_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_processed_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'is_active', 'next_run_on']);
            $table->index(['payment_source_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE recurring_rules
                ADD CONSTRAINT recurring_rules_amount_non_zero
                CHECK (amount_cents <> 0)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_rules');
    }
};
