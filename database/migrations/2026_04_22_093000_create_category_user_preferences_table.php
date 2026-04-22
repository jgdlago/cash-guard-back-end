<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_user_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('display_order_override')->nullable();
            $table->timestampsTz();

            $table->unique(['user_id', 'category_id']);
            $table->index(['user_id', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_user_preferences');
    }
};
