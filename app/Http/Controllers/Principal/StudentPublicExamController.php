<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPublicExam;
use Illuminate\Http\Request;

class StudentPublicExamController extends Controller
{
    public function store(Request $request, School $school, Student $student)
    {
        if ($student->school_id !== $school->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'exam_name' => 'required|string|max:255',
            'board' => 'nullable|string|max:255',
            'roll_no' => 'nullable|string|max:255',
            'reg_no' => 'nullable|string|max:255',
            'exam_year' => 'nullable|string|max:10',
            'session' => 'nullable|string|max:20',
            'candidate_type' => 'nullable|string|max:255',
            'center_name' => 'nullable|string|max:255',
        ]);

        $validated['school_id'] = $school->id;
        $validated['student_id'] = $student->id;

        StudentPublicExam::create($validated);

        return back()->with('success', 'পাবলিক পরীক্ষার তথ্য সফলভাবে যুক্ত করা হয়েছে।');
    }

    public function update(Request $request, School $school, Student $student, StudentPublicExam $publicExam)
    {
        if ($student->school_id !== $school->id || $publicExam->student_id !== $student->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'exam_name' => 'required|string|max:255',
            'board' => 'nullable|string|max:255',
            'roll_no' => 'nullable|string|max:255',
            'reg_no' => 'nullable|string|max:255',
            'exam_year' => 'nullable|string|max:10',
            'session' => 'nullable|string|max:20',
            'candidate_type' => 'nullable|string|max:255',
            'center_name' => 'nullable|string|max:255',
        ]);

        $publicExam->update($validated);

        return back()->with('success', 'পাবলিক পরীক্ষার তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(School $school, Student $student, StudentPublicExam $publicExam)
    {
         if ($student->school_id !== $school->id || $publicExam->student_id !== $student->id) {
            abort(403, 'Unauthorized action.');
        }

        $publicExam->delete();

        return back()->with('success', 'পাবলিক পরীক্ষার তথ্য মুছে ফেলা হয়েছে।');
    }
}
