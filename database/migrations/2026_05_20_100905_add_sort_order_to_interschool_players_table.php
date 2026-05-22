<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interschool_players', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_captain');
        });

        $eventIds = DB::table('interschool_players')
            ->distinct()
            ->pluck('interschool_season_event_id');

        foreach ($eventIds as $eventId) {
            $playerIds = DB::table('interschool_players')
                ->where('interschool_season_event_id', $eventId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($playerIds as $index => $playerId) {
                DB::table('interschool_players')
                    ->where('id', $playerId)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interschool_players', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
