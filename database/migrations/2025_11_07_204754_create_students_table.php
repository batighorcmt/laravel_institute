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
            $table->string('first_name'); // English/Latin script
            $table->string('last_name');
            $table->string('student_name_bn')->nullable(); // Full Bengali name
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('father_name'); // English/Latin script
            $table->string('mother_name');
            $table->string('father_name_bn')->nullable(); // Bengali
            $table->string('mother_name_bn')->nullable(); // Bengali
            $table->string('guardian_phone');
            $table->text('address');
            $table->string('blood_group')->nullable();
            $table->string('photo')->nullable();
            $table->date('admission_date');
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
