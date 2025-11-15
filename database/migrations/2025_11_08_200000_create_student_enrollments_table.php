<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('school_id')->constrained()->onDelete('cascade');
                $table->unsignedInteger('academic_year'); // e.g. 2025
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedInteger('roll_no');
                $table->enum('status',['active','promoted','transferred','withdrawn'])->default('active');
                $table->timestamps();
                $table->unique(['school_id','academic_year','class_id','section_id','group_id','roll_no'],'uniq_roll_per_scope');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
