<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('provider'); // e.g., sslcommerz
            $table->string('store_id')->nullable();
            $table->string('store_password')->nullable();
            $table->boolean('sandbox')->default(true);
            $table->boolean('active')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['school_id','provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_payment_settings');
    }
};