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
        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('present_division_id')->nullable()->after('signature');
            $table->unsignedBigInteger('present_district_id')->nullable()->after('present_division_id');
            $table->unsignedBigInteger('present_thana_id')->nullable()->after('present_district_id');
            $table->string('present_post_office')->nullable()->after('present_thana_id');
            $table->string('present_village')->nullable()->after('present_post_office');

            $table->unsignedBigInteger('permanent_division_id')->nullable()->after('present_village');
            $table->unsignedBigInteger('permanent_district_id')->nullable()->after('permanent_division_id');
            $table->unsignedBigInteger('permanent_thana_id')->nullable()->after('permanent_district_id');
            $table->string('permanent_post_office')->nullable()->after('permanent_thana_id');
            $table->string('permanent_village')->nullable()->after('permanent_post_office');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'present_division_id',
                'present_district_id',
                'present_thana_id',
                'present_post_office',
                'present_village',
                'permanent_division_id',
                'permanent_district_id',
                'permanent_thana_id',
                'permanent_post_office',
                'permanent_village',
            ]);
        });
    }
};
