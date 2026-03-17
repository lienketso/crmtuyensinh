<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE admission_profiles MODIFY identification_number VARCHAR(255) NULL UNIQUE');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE admission_profiles ALTER COLUMN identification_number DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE admission_profiles MODIFY identification_number VARCHAR(255) NOT NULL UNIQUE');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE admission_profiles ALTER COLUMN identification_number SET NOT NULL');
        }
    }
};
