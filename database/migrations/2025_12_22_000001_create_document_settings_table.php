<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('page'); // prottayon|certificate|testimonial
            $table->string('background_path')->nullable();
            $table->json('colors')->nullable(); // {"title":"#000","body":"#000"...}
            $table->timestamps();

            $table->unique(['school_id','page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
