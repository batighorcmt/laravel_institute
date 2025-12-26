<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_categories', 'active')) {
                $table->boolean('active')->default(true)->after('frequency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            if (Schema::hasColumn('fee_categories', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
