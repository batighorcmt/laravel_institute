<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
            $table->index('school_id');
            // Remove the global unique constraint if it exists to allow same-named categories in different schools
            $table->dropUnique(['name']);
            $table->unique(['school_id', 'name']);
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
            $table->index('school_id');
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
            $table->index('school_id');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
                $table->index('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name']);
            $table->unique(['name']);
            $table->dropColumn('school_id');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });
    }
};
