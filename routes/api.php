<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);

    // DEBUG: Test student data endpoint
    Route::get('/debug-student-data', function () {
        $student = \App\Models\Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->first();
        if (!$student) {
            return response()->json(['error' => 'No students found']);
        }
        
        return response()->json([
            'raw_data' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'date_of_birth' => $student->date_of_birth,
                'religion' => $student->religion,
                'father_name' => $student->father_name,
                'mother_name' => $student->mother_name,
                'blood_group' => $student->blood_group,
                'gender' => $student->gender,
                'guardian_phone' => $student->guardian_phone,
            ],
            'formatted_resource' => new \App\Http\Resources\StudentProfileResource($student),
        ]);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('teacher/students', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'index']);
        Route::get('teacher/students/{student}', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'show']);
        Route::get('teacher/students/meta', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'meta']);
    });
});
