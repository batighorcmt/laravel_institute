<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add religion to students if missing
        if (!Schema::hasColumn('students','religion')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('religion', 32)->nullable()->after('gender');
            });
        }

        // Add academic_year_id FK to student_enrollments
        if (!Schema::hasColumn('student_enrollments','academic_year_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('school_id');
            });
            // Populate academic_year_id from existing numeric academic_year via best match on name or start_date year
            $enrollments = DB::table('student_enrollments')->select('id','academic_year')->get();
            if ($enrollments->count()) {
                $years = DB::table('academic_years')->select('id','name','start_date')->get();
                foreach ($enrollments as $en) {
                    $targetYear = (int) $en->academic_year;
                    $match = $years->first(function($y) use ($targetYear) {
                        $nameInt = is_numeric($y->name) ? (int)$y->name : null;
                        $startYear = $y->start_date ? (int) date('Y', strtotime($y->start_date)) : null;
                        return $nameInt === $targetYear || $startYear === $targetYear;
                    });
                    if ($match) {
                        DB::table('student_enrollments')->where('id',$en->id)->update(['academic_year_id'=>$match->id]);
                    }
                }
            }
            // Drop old unique index first (MUST be done before dropping academic_year column)
            $indexExists = DB::select("SHOW INDEX FROM student_enrollments WHERE Key_name = 'uniq_roll_per_scope'");
            if (!empty($indexExists)) {
                DB::statement('ALTER TABLE student_enrollments DROP INDEX uniq_roll_per_scope');
            }
            // Drop old column academic_year
            if (Schema::hasColumn('student_enrollments','academic_year')) {
                Schema::table('student_enrollments', function (Blueprint $table) {
                    $table->dropColumn('academic_year');
                });
            }
            // Add foreign key & new unique index
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            });
            $newIndexExists = DB::select("SHOW INDEX FROM student_enrollments WHERE Key_name = 'uniq_roll_per_scope_new'");
            if (empty($newIndexExists)) {
                DB::statement('CREATE UNIQUE INDEX uniq_roll_per_scope_new ON student_enrollments (school_id, academic_year_id, class_id, section_id, group_id, roll_no)');
            }
        }
    }

    public function down(): void
    {
        // Recreate academic_year integer if rolling back
        if (!Schema::hasColumn('student_enrollments','academic_year')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unsignedInteger('academic_year')->nullable()->after('school_id');
            });
            // Populate from academic_year_id
            $enrollments = DB::table('student_enrollments')->select('id','academic_year_id')->get();
            $years = DB::table('academic_years')->select('id','name','start_date')->get();
            foreach ($enrollments as $en) {
                if ($en->academic_year_id) {
                    $yearRow = $years->firstWhere('id',$en->academic_year_id);
                    if ($yearRow) {
                        $val = null;
                        if (is_numeric($yearRow->name)) { $val = (int)$yearRow->name; }
                        elseif ($yearRow->start_date) { $val = (int) date('Y', strtotime($yearRow->start_date)); }
                        if ($val) { DB::table('student_enrollments')->where('id',$en->id)->update(['academic_year'=>$val]); }
                    }
                }
            }
            // Remove new unique index
            try { DB::statement('ALTER TABLE student_enrollments DROP INDEX uniq_roll_per_scope_new'); } catch (\Throwable $e) {}
        }
        // Drop FK & column academic_year_id
        if (Schema::hasColumn('student_enrollments','academic_year_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            });
        }
        // Restore old unique index
        try { DB::statement('CREATE UNIQUE INDEX uniq_roll_per_scope ON student_enrollments (school_id, academic_year, class_id, section_id, group_id, roll_no)'); } catch (\Throwable $e) {}

        // Remove religion column if exists
        if (Schema::hasColumn('students','religion')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('religion');
            });
        }
    }
};
