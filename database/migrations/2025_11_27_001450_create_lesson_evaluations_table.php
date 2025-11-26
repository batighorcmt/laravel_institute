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
        Schema::create('lesson_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('routine_entry_id')->nullable()->constrained('routine_entries')->onDelete('set null');
            $table->date('evaluation_date');
            $table->time('evaluation_time')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('completed');
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['school_id', 'teacher_id', 'evaluation_date']);
            $table->index(['class_id', 'section_id', 'evaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_evaluations');
    }
};
