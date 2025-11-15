<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            // 1=Monday ... 7=Sunday (ISO-8601)
            $table->unsignedTinyInteger('day_number');
            $table->string('day_name');
            $table->enum('status', ['active','inactive'])->default('inactive');
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->unique(['school_id','day_number']);
            $table->index(['school_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_holidays');
    }
};
