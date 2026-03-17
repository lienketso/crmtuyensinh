<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            // Mở rộng enum role để thêm super_admin
            try {
                DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','advisor') NOT NULL DEFAULT 'advisor'");
            } catch (\Throwable $e) {
                // Bỏ qua nếu thất bại (ví dụ schema khác), tránh làm hỏng migrate
            }

            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'school_id')) {
                    $table->foreignId('school_id')
                        ->nullable()
                        ->constrained('schools')
                        ->nullOnDelete()
                        ->after('role');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'school_id')) {
                    $table->dropConstrainedForeignId('school_id');
                }
            });

            try {
                DB::statement("ALTER TABLE users MODIFY role ENUM('admin','advisor') NOT NULL DEFAULT 'advisor'");
            } catch (\Throwable $e) {
                // Bỏ qua nếu không revert được
            }
        }
    }
};

