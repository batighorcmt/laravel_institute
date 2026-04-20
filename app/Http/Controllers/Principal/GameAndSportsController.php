<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class GameAndSportsController extends Controller
{
    public function index(Request $request, School $school)
    {
        $academicYears = AcademicYear::where('school_id', $school->id)->orderBy('name', 'desc')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        
        $sports = [
            'ক্রিকেট', 'ফুটবল', 'হকি', 'ভলিবল', 'অ্যাথলেটিকস', 'হ্যান্ডবল', 'কাবাডি', 
            'ব্যাডমিন্টন', 'সাঁতার', 'টেবিল টেনিস', 'দাবা', 'কারাত', 'জুডো', 
            'তায়কোয়ান্দো', 'বাস্কেটবল', 'খো খো'
        ];

        return view('principal.documents.game_and_sports.consent_index', compact('school', 'academicYears', 'classes', 'sports'));
    }

    public function print(Request $request, School $school)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'game_name' => 'required|string',
            'academic_year_id' => 'required',
        ]);

        $student = Student::with(['enrollments' => function($q) use ($validated) {
            $q->where('academic_year_id', $validated['academic_year_id']);
        }, 'enrollments.class', 'enrollments.section', 'enrollments.academicYear'])->findOrFail($validated['student_id']);

        $enrollment = $student->enrollments->first();
        
        return view('principal.documents.game_and_sports.consent_print', [
            'school' => $school,
            'student' => $student,
            'enrollment' => $enrollment,
            'game_name' => $validated['game_name']
        ]);
    }
}
