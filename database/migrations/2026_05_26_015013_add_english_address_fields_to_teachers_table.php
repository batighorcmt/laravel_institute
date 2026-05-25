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
            $table->string('present_post_office_en')->nullable()->after('present_post_office');
            $table->string('present_village_en')->nullable()->after('present_village');
            $table->string('permanent_post_office_en')->nullable()->after('permanent_post_office');
            $table->string('permanent_village_en')->nullable()->after('permanent_village');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'present_post_office_en',
                'present_village_en',
                'permanent_post_office_en',
                'permanent_village_en'
            ]);
        });
    }
};
