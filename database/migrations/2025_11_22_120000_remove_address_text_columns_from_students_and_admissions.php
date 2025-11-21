<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Remove old single-field address columns from students
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'present_address')) {
                $table->dropColumn('present_address');
            }
            if (Schema::hasColumn('students', 'permanent_address')) {
                $table->dropColumn('permanent_address');
            }
            if (Schema::hasColumn('students', 'address')) {
                $table->dropColumn('address');
            }
        });

        // Remove old single-field address columns from admission_applications
        if (Schema::hasTable('admission_applications')) {
            Schema::table('admission_applications', function (Blueprint $table) {
                if (Schema::hasColumn('admission_applications', 'present_address')) {
                    $table->dropColumn('present_address');
                }
                if (Schema::hasColumn('admission_applications', 'permanent_address')) {
                    $table->dropColumn('permanent_address');
                }
            });
        }
    }

    public function down(): void {
        // Restore old address columns if needed to rollback
        Schema::table('students', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
        });

        if (Schema::hasTable('admission_applications')) {
            Schema::table('admission_applications', function (Blueprint $table) {
                $table->string('present_address')->nullable();
                $table->string('permanent_address')->nullable();
            });
        }
    }
};
