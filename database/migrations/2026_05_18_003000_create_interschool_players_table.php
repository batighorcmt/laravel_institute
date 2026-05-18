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
        Schema::create('interschool_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interschool_season_event_id');
            $table->unsignedBigInteger('student_id');
            $table->string('group_name')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->foreign('interschool_season_event_id', 'ise_fk')->references('id')->on('interschool_season_events')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interschool_players');
    }
};
