<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('school_frontend_settings', 'homepage_content')) {
                $table->json('homepage_content')->nullable()->after('committee_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            if (Schema::hasColumn('school_frontend_settings', 'homepage_content')) {
                $table->dropColumn('homepage_content');
            }
        });
    }
};
