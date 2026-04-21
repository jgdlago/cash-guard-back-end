<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('direction', 20);
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestampsTz();

            $table->index(['user_id', 'is_active', 'display_order']);
            $table->index(['direction', 'is_active']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE categories
                ADD CONSTRAINT categories_kind_user_consistency
                CHECK (
                    (kind = 'system' AND user_id IS NULL) OR
                    (kind = 'custom' AND user_id IS NOT NULL)
                )
            SQL);

            DB::statement(
                'CREATE UNIQUE INDEX categories_system_slug_direction_unique ON categories (slug, direction) WHERE user_id IS NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX categories_user_slug_direction_unique ON categories (user_id, slug, direction) WHERE user_id IS NOT NULL'
            );
        } else {
            $tableName = 'categories';

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unique(['user_id', 'slug', 'direction']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
