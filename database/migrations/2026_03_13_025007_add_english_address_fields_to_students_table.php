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
            $table->string('present_village_en')->nullable()->after('present_village');
            $table->string('present_post_office_en')->nullable()->after('present_post_office');
            $table->string('present_upazilla_en')->nullable()->after('present_upazilla');
            $table->string('present_district_en')->nullable()->after('present_district');
            
            $table->string('permanent_village_en')->nullable()->after('permanent_village');
            $table->string('permanent_post_office_en')->nullable()->after('permanent_post_office');
            $table->string('permanent_upazilla_en')->nullable()->after('permanent_upazilla');
            $table->string('permanent_district_en')->nullable()->after('permanent_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'present_village_en',
                'present_post_office_en',
                'present_upazilla_en',
                'present_district_en',
                'permanent_village_en',
                'permanent_post_office_en',
                'permanent_upazilla_en',
                'permanent_district_en',
            ]);
        });
    }
};
