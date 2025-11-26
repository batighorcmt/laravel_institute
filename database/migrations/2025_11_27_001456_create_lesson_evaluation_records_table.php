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
        Schema::create('lesson_evaluation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_evaluation_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['completed', 'partial', 'not_done', 'absent'])->default('completed');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Unique constraint: একই evaluation এ একই student একবার
            $table->unique(['lesson_evaluation_id', 'student_id']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_evaluation_records');
    }
};
