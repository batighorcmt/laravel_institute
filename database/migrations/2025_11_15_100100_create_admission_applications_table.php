<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('applicant_name');
            $table->string('phone')->nullable();
            $table->string('class_name')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', ['pending','reviewed','accepted','rejected'])->default('pending');
            $table->timestamps();
            $table->index(['school_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};