<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_source_id')->nullable()->constrained('payment_sources')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->foreignId('installment_plan_id')->nullable()->constrained('installment_plans')->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('posted');
            $table->bigInteger('amount_cents');
            $table->char('currency_code', 3)->default('BRL');
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->unsignedInteger('installment_number')->nullable();
            $table->unsignedInteger('total_installments')->nullable();
            $table->date('competence_month')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['payment_source_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['user_id', 'status', 'transaction_date']);
            $table->index(['installment_plan_id', 'installment_number']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE transactions
                ADD CONSTRAINT transactions_amount_non_zero
                CHECK (amount_cents <> 0)
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE transactions
                ADD CONSTRAINT transactions_installment_number_valid
                CHECK (
                    installment_number IS NULL OR
                    installment_number >= 1
                )
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE transactions
                ADD CONSTRAINT transactions_total_installments_valid
                CHECK (
                    total_installments IS NULL OR
                    total_installments >= 1
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
