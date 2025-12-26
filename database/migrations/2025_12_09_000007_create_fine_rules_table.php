<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fine_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_category_id')->nullable();
            $table->enum('type', ['per_day', 'fixed'])->default('per_day');
            $table->decimal('rate', 12, 2);
            $table->decimal('max_cap', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['fee_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_rules');
    }
};
