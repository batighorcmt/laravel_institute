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
        Schema::table('students', function (Blueprint $table) {
            $table->string('guardian_relation')->nullable()->after('guardian_phone');
            $table->string('guardian_name_en')->nullable()->after('guardian_relation');
            $table->string('guardian_name_bn')->nullable()->after('guardian_name_en');
            $table->text('present_address')->nullable()->after('address');
            $table->text('permanent_address')->nullable()->after('present_address');
            $table->string('previous_school')->nullable()->after('photo');
            $table->string('pass_year')->nullable()->after('previous_school');
            $table->string('previous_result')->nullable()->after('pass_year');
            $table->text('previous_remarks')->nullable()->after('previous_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'guardian_relation',
                'guardian_name_en',
                'guardian_name_bn',
                'present_address',
                'permanent_address',
                'previous_school',
                'pass_year',
                'previous_result',
                'previous_remarks'
            ]);
        });
    }
};
