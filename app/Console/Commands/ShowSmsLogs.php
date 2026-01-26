<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsLog;
use Illuminate\Support\Facades\DB;

class ShowSmsLogs extends Command
{
    protected $signature = 'show:sms-logs {school_id} {limit=50}';
    protected $description = 'Show recent sms_logs for a school';

    public function handle()
    {
        $schoolId = (int)$this->argument('school_id');
        $limit = (int)$this->argument('limit');
        $count = DB::table('sms_logs')->where('school_id', $schoolId)->count();
        if ($count === 0) { $this->info('No sms logs found (count=0)'); return 0; }
        $this->info('Total sms_logs for school '.$schoolId.': '.$count);
        $rows = DB::table('sms_logs')->where('school_id', $schoolId)->orderByDesc('id')->limit($limit)->get();
        foreach ($rows as $r) {
            $this->line(implode(' | ', [ $r->id, $r->recipient_number ?? '-', $r->status ?? '-', $r->message_type ?? '-', $r->created_at ]));
        }
        return 0;
    }
}
