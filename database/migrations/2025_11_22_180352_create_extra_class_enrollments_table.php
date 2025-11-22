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
        Schema::create('extra_class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('assigned_section_id');
            $table->date('enrolled_date')->default(now());
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('extra_class_id')->references('id')->on('extra_classes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('assigned_section_id')->references('id')->on('sections')->onDelete('cascade');
            
            $table->unique(['extra_class_id', 'student_id']);
            $table->index(['extra_class_id', 'assigned_section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_class_enrollments');
    }
};
