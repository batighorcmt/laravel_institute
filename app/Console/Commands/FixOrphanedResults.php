<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOrphanedResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-orphaned-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix orphaned results records that reference non-existent students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orphaned results records...');

        $issues = [];

        // Check student_id
        $orphanedStudents = DB::table('results')
            ->leftJoin('students', 'results.student_id', '=', 'students.id')
            ->whereNull('students.id')
            ->select('results.id', 'results.student_id')
            ->get();

        if ($orphanedStudents->count() > 0) {
            $issues[] = ['type' => 'student', 'records' => $orphanedStudents];
        }

        // Check exam_id
        $orphanedExams = DB::table('results')
            ->leftJoin('exams', 'results.exam_id', '=', 'exams.id')
            ->whereNull('exams.id')
            ->select('results.id', 'results.exam_id')
            ->get();

        if ($orphanedExams->count() > 0) {
            $issues[] = ['type' => 'exam', 'records' => $orphanedExams];
        }

        // Check class_id
        $orphanedClasses = DB::table('results')
            ->leftJoin('classes', 'results.class_id', '=', 'classes.id')
            ->whereNull('classes.id')
            ->select('results.id', 'results.class_id')
            ->get();

        if ($orphanedClasses->count() > 0) {
            $issues[] = ['type' => 'class', 'records' => $orphanedClasses];
        }

        // Check section_id (nullable)
        $orphanedSections = DB::table('results')
            ->leftJoin('sections', 'results.section_id', '=', 'sections.id')
            ->whereNotNull('results.section_id')
            ->whereNull('sections.id')
            ->select('results.id', 'results.section_id')
            ->get();

        if ($orphanedSections->count() > 0) {
            $issues[] = ['type' => 'section', 'records' => $orphanedSections];
        }

        if (empty($issues)) {
            $this->info('No orphaned results found.');
            return;
        }

        foreach ($issues as $issue) {
            $type = $issue['type'];
            $records = $issue['records'];
            $count = $records->count();

            $this->warn("Found {$count} orphaned results records for {$type}:");

            foreach ($records as $record) {
                $fkId = $record->{"{$type}_id"};
                $this->line("Result ID: {$record->id}, {$type} ID: {$fkId}");
            }
        }

        if ($this->confirm('Do you want to delete all these orphaned records?')) {
            $allIds = collect($issues)->pluck('records')->flatten()->pluck('id')->unique();
            DB::table('results')->whereIn('id', $allIds)->delete();
            $totalDeleted = $allIds->count();
            $this->info("Deleted {$totalDeleted} orphaned results records.");
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
