<?php

use App\Models\Module;
use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::query()->updateOrCreate(
            ['slug' => 'notices'],
            [
                'name' => 'Notices',
                'description' => 'School announcement and notices.',
                'status' => 'active',
            ]
        );

        School::query()->each(function (School $school) use ($module): void {
            $exists = DB::table('school_modules')
                ->where('school_id', $school->id)
                ->where('module_id', $module->id)
                ->exists();

            if ($exists) {
                DB::table('school_modules')
                    ->where('school_id', $school->id)
                    ->where('module_id', $module->id)
                    ->update(['is_enabled' => true, 'updated_at' => now()]);
            } else {
                DB::table('school_modules')->insert([
                    'school_id' => $school->id,
                    'module_id' => $module->id,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        $moduleId = Module::query()->where('slug', 'notices')->value('id');

        if (! $moduleId) {
            return;
        }

        DB::table('school_modules')
            ->where('module_id', $moduleId)
            ->update(['is_enabled' => false, 'updated_at' => now()]);
    }
};
