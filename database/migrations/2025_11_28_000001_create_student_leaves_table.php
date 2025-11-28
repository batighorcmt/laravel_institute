<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->string('type')->nullable();
            $table->text('reason');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_leaves');
    }
};
