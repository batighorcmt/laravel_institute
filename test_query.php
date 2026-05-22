<?php
// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::where('photo', 'like', '%FjQx5O9HwqDS08LosU5svzD6ISO8WQxOeM9ZL8qn%')->first();
if ($student) {
    echo "Found student: " . $student->id . " -> " . $student->student_name_bn . " (Photo: " . $student->photo . ")\n";
    $enroll = \App\Models\StudentEnrollment::where('student_id', $student->id)->first();
    if ($enroll) {
        echo "Enrollment ID: " . $enroll->id . "\n";
        $request = Request::create('/meta/students', 'GET', [
            'student_id' => $student->id,
        ]);
        $school = \App\Models\School::find($enroll->school_id);
        Auth::login(\App\Models\User::first());
        $controller = new \App\Http\Controllers\Principal\MetaController();
        $response = $controller->students($school, $request);
        echo "Response:\n";
        echo json_encode(json_decode($response->getContent(), true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n";
    } else {
        echo "No enrollment found for student!\n";
    }
} else {
    echo "No student found with that photo\n";
}
