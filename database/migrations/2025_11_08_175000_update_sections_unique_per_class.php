<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old unique (school_id,name) then add new (school_id,class_id,name)
        // Need raw SQL because Laravel doesn't expose index name inference easily when columns changed.
        // Keep old unique index to avoid constraint dependency issues; create new composite in addition.
        // Ensure class_id column exists before creating composite unique index
        if (!Schema::hasColumn('sections','class_id')) {
            Schema::table('sections', function (Blueprint $table){
                $table->foreignId('class_id')->nullable()->after('school_id')->constrained('classes')->onDelete('cascade');
            });
        }
        // Add new composite unique only if not exists
        $newIndex = 'sections_school_id_class_id_name_unique';
        $existsNew = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$newIndex)
            ->exists();
        if (!$existsNew) {
            DB::statement('ALTER TABLE `sections` ADD UNIQUE INDEX `'.$newIndex.'` (`school_id`,`class_id`,`name`)');
        }
    }

    public function down(): void
    {
        // Revert to previous unique (school_id,name)
        // Down only drops the new composite index if present
        $newIndex = 'sections_school_id_class_id_name_unique';
        $existsNew = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name','sections')
            ->where('index_name',$newIndex)
            ->exists();
        if ($existsNew) {
            DB::statement('ALTER TABLE `sections` DROP INDEX `'.$newIndex.'`');
        }
    }
};
