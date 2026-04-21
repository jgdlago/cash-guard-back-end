<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_source_id')->nullable()->constrained('payment_sources')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('description', 255);
            $table->unsignedInteger('total_installments');
            $table->bigInteger('total_amount_cents');
            $table->char('currency_code', 3)->default('BRL');
            $table->date('first_due_date');
            $table->timestampsTz();

            $table->index(['user_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE installment_plans
                ADD CONSTRAINT installment_plans_total_installments_positive
                CHECK (total_installments > 0)
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE installment_plans
                ADD CONSTRAINT installment_plans_total_amount_non_zero
                CHECK (total_amount_cents <> 0)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
