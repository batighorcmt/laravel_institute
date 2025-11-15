<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('teams')) {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable(); // e.g., club, tuition, extra-class
            $table->text('description')->nullable();
            $table->enum('status',['active','inactive'])->default('active');
            $table->timestamps();
            $table->unique(['school_id','name']);
        });
        }

        if (!Schema::hasTable('team_student')) {
        Schema::create('team_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('joined_at')->nullable();
            $table->enum('status',['active','inactive'])->default('active');
            $table->timestamps();
            $table->unique(['team_id','student_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_student');
        Schema::dropIfExists('teams');
    }
};
