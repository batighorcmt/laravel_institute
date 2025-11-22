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
        Schema::create('extra_class_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_class_id');
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('extra_class_id')->references('id')->on('extra_classes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            
            $table->unique(['extra_class_id', 'student_id', 'date'], 'extra_class_attendance_unique');
            $table->index(['extra_class_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_class_attendances');
    }
};
