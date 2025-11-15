<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->boolean('is_optional')->default(false);
            $table->enum('status',['active','inactive'])->default('active');
            $table->timestamps();
            $table->unique(['student_enrollment_id','subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
    }
};
