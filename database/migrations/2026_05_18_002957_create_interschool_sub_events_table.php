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
        Schema::create('interschool_sub_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interschool_event_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('interschool_event_id')->references('id')->on('interschool_events')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interschool_sub_events');
    }
};
