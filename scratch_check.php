<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/principal/1/exams/9/admit-v2', 'GET')
);
// This is a bit complex because of middleware.
// Let's just try to boot and find the exam.
$app->boot();
use App\Models\Exam;
try {
    $exam = Exam::with('class')->find(9);
    echo "Exam found: " . ($exam ? $exam->name : 'No') . "\n";
    if ($exam) {
        echo "Class ID: " . $exam->class_id . "\n";
        echo "Class found: " . ($exam->class ? $exam->class->name : 'No') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
