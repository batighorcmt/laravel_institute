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
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_category_id')->nullable()->change();
            $table->foreignId('academic_year_id')->nullable()->after('student_id')->constrained('academic_years')->onDelete('set null');
            $table->string('payment_number')->unique()->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_category_id')->nullable(false)->change();
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn(['academic_year_id', 'payment_number']);
        });
    }
};
