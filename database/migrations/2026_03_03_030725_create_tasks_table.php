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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')
            ->constrained('leads')
            ->onDelete('cascade')
            ->comment('Lead liên quan');

            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Nhân viên được giao task');

            $table->string('title')
                ->comment('Tiêu đề task: Gọi lại, Gửi hồ sơ...');

            $table->text('description')->nullable()
                ->comment('Mô tả chi tiết');

            $table->dateTime('due_at')
                ->comment('Thời hạn hoàn thành');

            $table->string('status')->default('pending')
                ->comment('pending | done | overdue | cancelled');
                
            $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
