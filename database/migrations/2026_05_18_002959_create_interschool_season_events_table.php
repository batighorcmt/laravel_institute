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
        Schema::create('interschool_season_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interschool_season_id');
            $table->unsignedBigInteger('interschool_event_id');
            $table->unsignedBigInteger('interschool_sub_event_id')->nullable();
            $table->string('age_group')->nullable();
            $table->timestamps();

            $table->foreign('interschool_season_id')->references('id')->on('interschool_seasons')->onDelete('cascade');
            $table->foreign('interschool_event_id')->references('id')->on('interschool_events')->onDelete('cascade');
            $table->foreign('interschool_sub_event_id')->references('id')->on('interschool_sub_events')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interschool_season_events');
    }
};
