<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 10)->default('pt-BR')->after('password');
            $table->string('timezone', 50)->default('America/Sao_Paulo')->after('locale');
            $table->char('currency_code', 3)->default('BRL')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['locale', 'timezone', 'currency_code']);
        });
    }
};
