<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_school_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_school_roles','designation')) {
                $table->string('designation',100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('user_school_roles','serial_number')) {
                $table->unsignedInteger('serial_number')->nullable()->after('designation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_school_roles', function (Blueprint $table) {
            if (Schema::hasColumn('user_school_roles','serial_number')) {
                $table->dropColumn('serial_number');
            }
            if (Schema::hasColumn('user_school_roles','designation')) {
                $table->dropColumn('designation');
            }
        });
    }
};