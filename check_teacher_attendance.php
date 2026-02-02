<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking teacher attendance tables...\n";
$today = date('Y-m-d');

$tables = ['teacher_attendances', 'extra_class_attendances'];
foreach ($tables as $t) {
    echo "\nTable: {$t}\n";
    if (!Schema::hasTable($t)) {
        echo "  -> Table does not exist.\n";
        continue;
    }
    $cols = Schema::getColumnListing($t);
    echo "  Columns: " . implode(', ', $cols) . "\n";

    // Check if has school_id
    if (in_array('school_id', $cols)) {
        $rows = DB::table($t)
            ->select('school_id', DB::raw('count(*) as cnt'))
            ->whereDate('date', $today)
            ->groupBy('school_id')
            ->orderBy('cnt', 'desc')
            ->get();
        if ($rows->isEmpty()) {
            echo "  -> No rows for today grouped by school_id.\n";
        } else {
            foreach ($rows as $r) {
                echo "  -> school_id={$r->school_id}: {$r->cnt}\n";
            }
        }
    } elseif (in_array('user_id', $cols)) {
        $rows = DB::table($t)
            ->select('user_id', DB::raw('count(*) as cnt'))
            ->whereDate('date', $today)
            ->groupBy('user_id')
            ->orderBy('cnt','desc')
            ->limit(10)
            ->get();
        if ($rows->isEmpty()) {
            echo "  -> No rows for today grouped by user_id.\n";
        } else {
            foreach ($rows as $r) {
                echo "  -> user_id={$r->user_id}: {$r->cnt}\n";
            }
        }
    } else {
        $rows = DB::table($t)->whereDate('date',$today)->count();
        echo "  -> Total rows for today: {$rows}\n";
    }

    // status breakdown for today
    if (in_array('status', $cols)) {
        $s = DB::table($t)
            ->select('status', DB::raw('count(*) as cnt'))
            ->whereDate('date',$today)
            ->groupBy('status')
            ->get();
        if ($s->isEmpty()) echo "  -> No status rows for today.\n";
        else {
            echo "  -> Status breakdown:\n";
            foreach ($s as $row) echo "     - {$row->status}: {$row->cnt}\n";
        }
    }
}

// Also show total registered teachers per school
if (Schema::hasTable('teachers')) {
    echo "\nTeacher counts per school (top 10):\n";
    $tch = DB::table('teachers')
        ->select('school_id', DB::raw('count(*) as cnt'))
        ->groupBy('school_id')
        ->orderBy('cnt','desc')
        ->limit(10)
        ->get();
    foreach ($tch as $r) echo "  -> school_id={$r->school_id}: {$r->cnt}\n";
}

echo "\nDone.\n";