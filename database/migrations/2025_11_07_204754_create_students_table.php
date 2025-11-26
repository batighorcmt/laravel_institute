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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('student_id')->unique(); // Generated student ID
            $table->string('student_name_en'); // English name
            $table->string('student_name_bn')->nullable(); // Bengali name
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('father_name')->nullable(); // English/Latin script
            $table->string('mother_name')->nullable();
            $table->string('father_name_bn')->nullable(); // Bengali
            $table->string('mother_name_bn')->nullable(); // Bengali
            $table->string('guardian_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('photo')->nullable();
            $table->date('admission_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
