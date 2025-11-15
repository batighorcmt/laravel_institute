<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('title');
            $table->date('date');
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->unique(['school_id','date']);
            $table->index(['school_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
