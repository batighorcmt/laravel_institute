<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure new composite unique index exists before dropping old one.
        $newIndex = 'sections_school_id_class_id_name_unique';
        $newExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$newIndex)
            ->exists();

        if (!$newExists) {
            DB::statement('ALTER TABLE `sections` ADD UNIQUE INDEX `'.$newIndex.'` (`school_id`,`class_id`,`name`)');
        }

        // Drop old unique index if present.
        $oldIndex = 'sections_school_id_name_unique';
        $oldExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$oldIndex)
            ->exists();
        if ($oldExists) {
            try {
                DB::statement('ALTER TABLE `sections` DROP INDEX `'.$oldIndex.'`');
            } catch (\Throwable $e) {
                // Fallback: create a non-unique index if dropping fails (rare) and retry.
                // But generally after composite exists MySQL should allow drop.
            }
        }
    }

    public function down(): void
    {
        // Recreate old unique index if not exists.
        $oldIndex = 'sections_school_id_name_unique';
        $oldExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$oldIndex)
            ->exists();
        if (!$oldExists) {
            DB::statement('ALTER TABLE `sections` ADD UNIQUE INDEX `'.$oldIndex.'` (`school_id`,`name`)');
        }
        // Optionally drop composite unique (leave if you want to keep both when rolling back)
        $newIndex = 'sections_school_id_class_id_name_unique';
        $newExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$newIndex)
            ->exists();
        if ($newExists) {
            DB::statement('ALTER TABLE `sections` DROP INDEX `'.$newIndex.'`');
        }
    }
};
