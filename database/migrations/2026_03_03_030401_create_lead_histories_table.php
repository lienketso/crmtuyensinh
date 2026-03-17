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
        Schema::create('lead_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')
                ->constrained('leads')
                ->onDelete('cascade')
                ->comment('Lead liên quan');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->comment('Nhân viên thực hiện');
            $table->string('type')
                ->comment('Loại: call | sms | email | chat | note | meeting');
            $table->text('content')
                ->comment('Nội dung tương tác');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_histories');
    }
};
