<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7); // YYYY-MM
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->unique(['month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_periods');
    }
};
