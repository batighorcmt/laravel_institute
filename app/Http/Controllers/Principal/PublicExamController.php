<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\School;
use App\Models\PublicExam;

class PublicExamController extends Controller
{
    public function index(Request $request, School $school)
    {
        $publicExams = $school->publicExams()->latest()->get();
        return view('principal.public_exams.index', compact('school', 'publicExams'));
    }

    public function create(Request $request, School $school)
    {
        return view('principal.public_exams.create', compact('school'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'short_name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $school->publicExams()->create($validated);

        return redirect()->route('principal.institute.public_exams.index', $school)->with('success', 'পাবলিক পরীক্ষা সফলভাবে যুক্ত করা হয়েছে।');
    }

    public function edit(Request $request, School $school, PublicExam $publicExam)
    {
        if ($publicExam->school_id !== $school->id) abort(404);
        return view('principal.public_exams.edit', compact('school', 'publicExam'));
    }

    public function update(Request $request, School $school, PublicExam $publicExam)
    {
        if ($publicExam->school_id !== $school->id) abort(404);

        $validated = $request->validate([
            'short_name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $publicExam->update($validated);

        return redirect()->route('principal.institute.public_exams.index', $school)->with('success', 'পাবলিক পরীক্ষা সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Request $request, School $school, PublicExam $publicExam)
    {
        if ($publicExam->school_id !== $school->id) abort(404);
        
        $publicExam->delete();

        return redirect()->route('principal.institute.public_exams.index', $school)->with('success', 'পাবলিক পরীক্ষা সফলভাবে মুছে ফেলা হয়েছে।');
    }
}
