<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Backfill missing app_id values with unique random IDs
        $rows = DB::table('admission_applications')->whereNull('app_id')->get(['id']);
        foreach ($rows as $row) {
            $new = null;
            do {
                $candidate = strtoupper(Str::random(8));
                $exists = DB::table('admission_applications')->where('app_id',$candidate)->exists();
                if (!$exists) { $new = $candidate; }
            } while(!$new);
            DB::table('admission_applications')->where('id',$row->id)->update(['app_id'=>$new]);
        }
    }

    public function down(): void
    {
        // Revert (set those that look like random 8-char uppercase back to null) - optional heuristic
        DB::table('admission_applications')
            ->whereRaw('app_id REGEXP "^[A-Z0-9]{8}$"')
            ->update(['app_id'=>null]);
    }
};
