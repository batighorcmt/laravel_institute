<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'birth_certificate_no')) {
                $table->string('birth_certificate_no', 50)->nullable()->after('board_registration_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'birth_certificate_no')) {
                $table->dropColumn('birth_certificate_no');
            }
        });
    }
};
