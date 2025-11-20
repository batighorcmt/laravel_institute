<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Students table components
        Schema::table('students', function (Blueprint $table) {
            $table->string('present_village')->nullable()->after('present_address');
            $table->string('present_para_moholla')->nullable()->after('present_village');
            $table->string('present_post_office')->nullable()->after('present_para_moholla');
            $table->string('present_upazilla')->nullable()->after('present_post_office');
            $table->string('present_district')->nullable()->after('present_upazilla');
            $table->string('permanent_village')->nullable()->after('permanent_address');
            $table->string('permanent_para_moholla')->nullable()->after('permanent_village');
            $table->string('permanent_post_office')->nullable()->after('permanent_para_moholla');
            $table->string('permanent_upazilla')->nullable()->after('permanent_post_office');
            $table->string('permanent_district')->nullable()->after('permanent_upazilla');
        });

        // Admission applications table components
        if (Schema::hasTable('admission_applications')) {
            Schema::table('admission_applications', function (Blueprint $table) {
                $table->string('present_village')->nullable()->after('present_address');
                $table->string('present_para_moholla')->nullable()->after('present_village');
                $table->string('present_post_office')->nullable()->after('present_para_moholla');
                $table->string('present_upazilla')->nullable()->after('present_post_office');
                $table->string('present_district')->nullable()->after('present_upazilla');
                $table->string('permanent_village')->nullable()->after('permanent_address');
                $table->string('permanent_para_moholla')->nullable()->after('permanent_village');
                $table->string('permanent_post_office')->nullable()->after('permanent_para_moholla');
                $table->string('permanent_upazilla')->nullable()->after('permanent_post_office');
                $table->string('permanent_district')->nullable()->after('permanent_upazilla');
            });
        }
    }

    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'present_village','present_para_moholla','present_post_office','present_upazilla','present_district',
                'permanent_village','permanent_para_moholla','permanent_post_office','permanent_upazilla','permanent_district'
            ]);
        });
        if (Schema::hasTable('admission_applications')) {
            Schema::table('admission_applications', function (Blueprint $table) {
                $table->dropColumn([
                    'present_village','present_para_moholla','present_post_office','present_upazilla','present_district',
                    'permanent_village','permanent_para_moholla','permanent_post_office','permanent_upazilla','permanent_district'
                ]);
            });
        }
    }
};