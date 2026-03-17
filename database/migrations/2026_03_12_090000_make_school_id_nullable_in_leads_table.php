<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        // Dùng raw SQL để tránh phụ thuộc doctrine/dbal
        try {
            DB::statement('ALTER TABLE leads MODIFY school_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Nếu cột không tồn tại thì bỏ qua
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE leads MODIFY school_id BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // Bỏ qua nếu không revert được
        }
    }
};

