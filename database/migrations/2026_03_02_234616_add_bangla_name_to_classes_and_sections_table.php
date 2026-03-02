<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('bangla_name', 100)->nullable()->after('name');
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->string('bangla_name', 100)->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('bangla_name');
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('bangla_name');
        });
    }
};
