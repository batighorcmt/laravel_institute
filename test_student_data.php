<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get first student
$student = DB::table('students')->first();

if (!$student) {
    echo "❌ No students found in database\n";
    exit(1);
}

echo "✅ Found student in database\n";
echo "Student ID: " . ($student->student_id ?? 'NULL') . "\n";
echo "Name (EN): " . ($student->student_name_en ?? 'NULL') . "\n";
echo "Name (BN): " . ($student->student_name_bn ?? 'NULL') . "\n";
echo "DOB: " . ($student->date_of_birth ?? 'NULL') . "\n";
echo "Religion: " . ($student->religion ?? 'NULL') . "\n";
echo "Gender: " . ($student->gender ?? 'NULL') . "\n";
echo "Blood Group: " . ($student->blood_group ?? 'NULL') . "\n";
echo "Father Name: " . ($student->father_name ?? 'NULL') . "\n";
echo "Mother Name: " . ($student->mother_name ?? 'NULL') . "\n";
echo "Guardian Phone: " . ($student->guardian_phone ?? 'NULL') . "\n";
echo "\n";

// Test StudentProfileResource
$studentModel = App\Models\Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->first();
if ($studentModel) {
    $studentModel->loadMissing(['class', 'optionalSubject', 'enrollments', 'teams']);
    
    // Set attendance stats
    $studentModel->setAttribute('attendance_stats', [
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'leave' => 0,
    ]);
    $studentModel->setAttribute('working_days', 0);
    
    $resource = new App\Http\Resources\StudentProfileResource($studentModel);
    $array = $resource->toArray(request());
    
    echo "📱 API Response (StudentProfileResource):\n";
    echo "Student ID: " . ($array['student_id'] ?? 'NULL') . "\n";
    echo "DOB: " . ($array['date_of_birth'] ?? 'NULL') . "\n";
    echo "Religion: " . ($array['religion'] ?? 'NULL') . "\n";
    echo "Gender: " . ($array['gender'] ?? 'NULL') . "\n";
    echo "Blood Group: " . ($array['blood_group'] ?? 'NULL') . "\n";
    echo "Father Name: " . ($array['father_name'] ?? 'NULL') . "\n";
    echo "Mother Name: " . ($array['mother_name'] ?? 'NULL') . "\n";
    echo "Guardian Phone: " . ($array['guardian_phone'] ?? 'NULL') . "\n";
    echo "Phone: " . ($array['phone'] ?? 'NULL') . "\n";
}
