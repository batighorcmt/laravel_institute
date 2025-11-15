<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_subjects')) {
            Schema::create('class_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('group_id')->nullable();
                $table->unsignedBigInteger('subject_id');
                $table->boolean('is_optional')->default(false);
                $table->smallInteger('order_no')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });

            // Add generated column to enforce uniqueness with nullable group
            try {
                DB::statement('ALTER TABLE class_subjects ADD COLUMN group_key BIGINT GENERATED ALWAYS AS (IFNULL(group_id, 0)) STORED');
                DB::statement('CREATE UNIQUE INDEX class_subjects_unique ON class_subjects (school_id, class_id, subject_id, group_key)');
            } catch (\Throwable $e) {
                // If generated columns not supported, fallback to non-unique (we will enforce in code)
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_subjects')) {
            try { DB::statement('DROP INDEX class_subjects_unique ON class_subjects'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE class_subjects DROP COLUMN group_key'); } catch (\Throwable $e) {}
            Schema::dropIfExists('class_subjects');
        }
    }
};
