<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowQueue extends Command
{
    protected $signature = 'show:queue';
    protected $description = 'Show job queue counts (jobs and failed_jobs)';

    public function handle()
    {
        $jobs = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        $this->info('jobs: '.$jobs.' failed_jobs: '.$failed);
        return 0;
    }
}
