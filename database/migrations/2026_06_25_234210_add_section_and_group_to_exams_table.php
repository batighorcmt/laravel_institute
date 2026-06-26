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
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropForeign(['group_id']);
            $table->dropColumn(['section_id', 'group_id']);
        });
    }
};
