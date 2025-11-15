<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes','numeric_value')) {
                $table->unsignedTinyInteger('numeric_value')->default(1)->after('name');
            }
        });
        // Create unique index on (school_id, numeric_value) if not exists (raw SQL for idempotency)
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'classes')
            ->where('index_name', 'classes_school_id_numeric_value_unique')
            ->exists();
        if (!$indexExists) {
            DB::statement('ALTER TABLE `classes` ADD UNIQUE INDEX `classes_school_id_numeric_value_unique` (`school_id`, `numeric_value`)');
        }
        // We will not drop the old `section` column here to avoid FK/index dependency issues.
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes','numeric_value')) {
                $table->dropUnique('classes_school_id_numeric_value_unique');
                $table->dropColumn('numeric_value');
            }
            if (!Schema::hasColumn('classes','section')) {
                $table->string('section')->nullable();
            }
            $table->unique(['school_id','name','section']);
        });
    }
};
