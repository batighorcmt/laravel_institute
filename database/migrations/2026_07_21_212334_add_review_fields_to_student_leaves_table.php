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
        Schema::table('student_leaves', function (Blueprint $table) {
            $table->string('title')->nullable()->after('type');
            $table->unsignedBigInteger('class_id')->nullable()->after('student_id');
            $table->unsignedBigInteger('section_id')->nullable()->after('class_id');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_leaves', function (Blueprint $table) {
            $table->dropColumn(['title', 'class_id', 'section_id', 'reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};
