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
        Schema::create('public_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('short_name');
            $table->string('full_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_exams');
    }
};
