<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsLog;
use Illuminate\Support\Facades\DB;

class InspectAttendanceLogs extends Command
{
    protected $signature = 'inspect:attendance-logs {school_id} {limit=200}';
    protected $description = 'Show recent sms_logs for class attendance for a school';

    public function handle()
    {
        $schoolId = (int) $this->argument('school_id');
        $limit = (int) $this->argument('limit');
        $count = DB::table('sms_logs')->where('school_id', $schoolId)->where('recipient_category', 'class attendance')->count();
        if ($count === 0) { $this->info('No class attendance sms logs found (count=0)'); return 0; }
        $this->info('Total class attendance sms_logs for school '.$schoolId.': '.$count);
        $rows = DB::table('sms_logs')->where('school_id', $schoolId)->where('recipient_category', 'class attendance')->orderByDesc('id')->limit($limit)->get();
        foreach ($rows as $r) {
            $this->line(implode(' | ', [ $r->id, $r->recipient_number ?? '-', $r->status ?? '-', $r->recipient_category ?? '-', $r->created_at ]));
        }
        return 0;
    }
}
