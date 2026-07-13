<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    App\Jobs\ProcessBiometricPunchJob::dispatch(App\Models\BiometricAttendanceLog::latest()->first());
    echo "Success\n";
} catch(\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
}
