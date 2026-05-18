<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interschool_players', function (Blueprint $table) {
            $table->string('attendance_days')->nullable()->after('is_captain');
        });

        Schema::table('interschool_seasons', function (Blueprint $table) {
            $table->date('age_date')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('interschool_players', function (Blueprint $table) {
            $table->dropColumn('attendance_days');
        });

        Schema::table('interschool_seasons', function (Blueprint $table) {
            $table->dropColumn('age_date');
        });
    }
};
