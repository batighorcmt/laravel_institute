<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (! Schema::hasColumn('notices', 'show_on_frontend_marquee')) {
                $table->boolean('show_on_frontend_marquee')->default(false)->after('attachment_path');
            }
            if (! Schema::hasColumn('notices', 'show_on_frontend_board')) {
                $table->boolean('show_on_frontend_board')->default(true)->after('show_on_frontend_marquee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (Schema::hasColumn('notices', 'show_on_frontend_board')) {
                $table->dropColumn('show_on_frontend_board');
            }
            if (Schema::hasColumn('notices', 'show_on_frontend_marquee')) {
                $table->dropColumn('show_on_frontend_marquee');
            }
        });
    }
};
