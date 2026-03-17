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
        Schema::table('leads', function (Blueprint $table) {
            $table->longText('conversation_json')->nullable()->after('status')->comment('Lịch sử hội thoại');
        });

        Schema::table('admission_profiles', function (Blueprint $table) {
            $table->text('province')->nullable()->after('id')->comment('Tỉnh/Thành phố');
            $table->year('graduation_year')->nullable()->after('province')->comment('Năm tốt nghiệp THPT');
            $table->string('academic_level')->nullable()->after('graduation_year')->comment('Học lực: Giỏi/Khá/Trung bình');
            $table->decimal('gpa', 4, 2)->nullable()->comment('Điểm trung bình THPT');
            $table->string('admission_file')->nullable()->comment('File hồ sơ xét tuyển');
            $table->string('admission_method')->nullable()->comment('Hình thức xét tuyển: học bạ / điểm thi / liên thông');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('conversation_json');
        });

        Schema::table('admission_profiles', function (Blueprint $table) {
            $table->dropColumn('province');
            $table->dropColumn('graduation_year');
            $table->dropColumn('academic_level');
            $table->dropColumn('gpa');
            $table->dropColumn('admission_file');
            $table->dropColumn('admission_method');
        });
    }
};
