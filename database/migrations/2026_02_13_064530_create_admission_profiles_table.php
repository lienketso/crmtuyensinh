<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admission_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained();
            $table->string('identification_number')->unique();
            $table->json('academic_records')->nullable(); // Lưu điểm số
            $table->string('document_status')->default('pending'); // pending, verified, rejected
            $table->text('admin_note')->nullable(); // Ghi chú của cán bộ khi duyệt
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_profiles');
    }
};
