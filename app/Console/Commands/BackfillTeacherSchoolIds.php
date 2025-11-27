<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class BackfillTeacherSchoolIds extends Command
{
    protected $signature = 'teacher:backfill-school-ids {--school= : Default school_id to apply where missing}';
    protected $description = 'Backfill missing school_id on user_school_roles for teacher role';

    public function handle(): int
    {
        $default = $this->option('school');
        if (! $default) {
            $this->error('You must provide --school=ID for the missing entries.');
            return Command::FAILURE;
        }
        if (! is_numeric($default)) {
            $this->error('--school must be numeric');
            return Command::FAILURE;
        }
        $schoolId = (int) $default;
        $teacherRoleId = Role::where('name', 'teacher')->value('id');
        if (! $teacherRoleId) {
            $this->error('Teacher role not found.');
            return Command::FAILURE;
        }
        $countMissing = DB::table('user_school_roles')
            ->whereNull('school_id')
            ->where('role_id', $teacherRoleId)
            ->count();
        if ($countMissing === 0) {
            $this->info('No missing school_id rows for teacher role.');
            return Command::SUCCESS;
        }
        $this->warn("Updating {$countMissing} row(s) with school_id={$schoolId}...");
        DB::table('user_school_roles')
            ->whereNull('school_id')
            ->where('role_id', $teacherRoleId)
            ->update(['school_id' => $schoolId]);
        $updated = DB::table('user_school_roles')
            ->where('school_id', $schoolId)
            ->where('role_id', $teacherRoleId)
            ->count();
        $this->info("Backfill complete. Teacher rows now referencing school_id={$schoolId}. Total teacher rows at that school: {$updated}");
        return Command::SUCCESS;
    }
}
