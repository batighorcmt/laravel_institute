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
        Schema::table('teacher_deposits', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_category_id')->nullable()->after('amount');
            $table->string('month', 20)->nullable()->after('fee_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_deposits', function (Blueprint $table) {
            $table->dropColumn(['fee_category_id', 'month']);
        });
    }
};
