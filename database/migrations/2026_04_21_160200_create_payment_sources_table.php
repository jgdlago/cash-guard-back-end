<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name', 120);
            $table->char('currency_code', 3)->default('BRL');
            $table->foreignId('parent_payment_source_id')->nullable()->constrained('payment_sources')->nullOnDelete();
            $table->bigInteger('credit_limit_cents')->nullable();
            $table->unsignedTinyInteger('statement_closing_day')->nullable();
            $table->unsignedTinyInteger('statement_due_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestampsTz();

            $table->index(['user_id', 'is_active', 'display_order']);
            $table->index(['user_id', 'type']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE payment_sources
                ADD CONSTRAINT payment_sources_credit_limit_non_negative
                CHECK (credit_limit_cents IS NULL OR credit_limit_cents >= 0)
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE payment_sources
                ADD CONSTRAINT payment_sources_statement_closing_day_valid
                CHECK (
                    statement_closing_day IS NULL OR
                    statement_closing_day BETWEEN 1 AND 31
                )
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE payment_sources
                ADD CONSTRAINT payment_sources_statement_due_day_valid
                CHECK (
                    statement_due_day IS NULL OR
                    statement_due_day BETWEEN 1 AND 31
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_sources');
    }
};
