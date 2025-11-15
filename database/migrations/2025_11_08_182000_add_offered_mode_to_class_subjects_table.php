<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('class_subjects','offered_mode')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                $table->string('offered_mode', 20)->default('compulsory')->after('is_optional');
            });
            try {
                DB::statement("UPDATE class_subjects SET offered_mode = CASE WHEN is_optional = 1 THEN 'optional' ELSE 'compulsory' END");
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_subjects','offered_mode')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                $table->dropColumn('offered_mode');
            });
        }
    }
};
