<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        if (Schema::hasColumn('leads', 'school_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            // Thêm trước khi có các migration "modify school_id"
            // (tránh crash nếu column chưa tồn tại).
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        if (!Schema::hasColumn('leads', 'school_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });
    }
};

