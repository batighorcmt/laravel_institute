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
        Schema::table('document_settings', function (Blueprint $table) {
            $table->json('memo_format')->nullable(); // Array of keywords like ['institution_code', 'academic_year', 'serial_no']
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_settings', function (Blueprint $table) {
            $table->dropColumn('memo_format');
        });
    }
};
