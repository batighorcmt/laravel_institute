<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateExams extends Command
{
    protected $signature = 'exams:clean-duplicates';
    protected $description = 'Remove duplicate exam entries keeping the oldest one';

    public function handle()
    {
        $this->info('Searching for duplicate exams...');

        // Find duplicates
        $duplicates = DB::select("
            SELECT school_id, academic_year_id, class_id, name, 
                   GROUP_CONCAT(id ORDER BY id) as ids,
                   COUNT(*) as count
            FROM exams 
            GROUP BY school_id, academic_year_id, class_id, name 
            HAVING count > 1
        ");

        if (empty($duplicates)) {
            $this->info('No duplicate exams found!');
            return 0;
        }

        $this->info('Found ' . count($duplicates) . ' sets of duplicates');

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $keepId = array_shift($ids); // Keep the first (oldest) record
            
            $this->line("Keeping exam ID {$keepId}, removing: " . implode(', ', $ids));
            
            // Delete the duplicates
            DB::table('exams')->whereIn('id', $ids)->delete();
        }

        $this->info('Duplicate exams cleaned successfully!');
        return 0;
    }
}
