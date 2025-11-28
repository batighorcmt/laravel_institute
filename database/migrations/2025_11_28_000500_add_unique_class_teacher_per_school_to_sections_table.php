<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure columns exist before adding unique index
        if (Schema::hasTable('sections') &&
            Schema::hasColumn('sections', 'school_id') &&
            Schema::hasColumn('sections', 'class_teacher_id')) {
            Schema::table('sections', function (Blueprint $table) {
                // Enforce: a teacher can be class teacher of only one section per school
                // Multiple NULLs are allowed for class_teacher_id by MySQL unique index semantics
                $table->unique(['school_id', 'class_teacher_id'], 'sections_unique_class_teacher_per_school');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                // Drop the unique index if it exists
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('sections');
                if (array_key_exists('sections_unique_class_teacher_per_school', $indexes)) {
                    $table->dropUnique('sections_unique_class_teacher_per_school');
                } else {
                    // Fallback: try dropping by columns signature
                    try {
                        $table->dropUnique(['school_id', 'class_teacher_id']);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            });
        }
    }
};
