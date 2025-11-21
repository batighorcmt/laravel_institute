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
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('optional_subject_id')->nullable()->after('class_id');
            $table->foreign('optional_subject_id')->references('id')->on('subjects')->onDelete('set null');
            $table->index('optional_subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['optional_subject_id']);
            $table->dropIndex(['optional_subject_id']);
            $table->dropColumn('optional_subject_id');
        });
    }
};
