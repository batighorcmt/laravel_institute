<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Section;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function index(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $q = $request->get('q');
        $items = Section::forSchool($school->id)
            ->when($q, fn($x) => $x->where('sections.name','like',"%$q%"))
            ->ordered()
            ->with('class')
            ->paginate(10)
            ->withQueryString();
        return view('principal.institute.sections.index', compact('school','items','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
    $classList = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
    return view('principal.institute.sections.create', compact('school','classList'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'class_id' => ['required','exists:classes,id'],
            'name' => [
                'required','string','max:50',
                Rule::unique('sections','name')->where(fn($q) => $q->where('school_id',$school->id)->where('class_id',$request->input('class_id'))),
            ],
            'class_teacher_name' => ['nullable','string','max:100'],
            'status' => ['required','in:active,inactive'],
        ]);
    // Ensure selected class belongs to the same school
    abort_unless(SchoolClass::where('id',$data['class_id'])->where('school_id',$school->id)->exists(), 422);
    $data['school_id'] = $school->id;
        Section::create($data);
        return redirect()->route('principal.institute.sections.index', $school)->with('success','সেকশন যুক্ত হয়েছে');
    }

    public function edit(School $school, Section $section)
    {
        $this->authorizePrincipal($school);
        abort_unless($section->school_id === $school->id, 404);
    $classList = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
    return view('principal.institute.sections.edit', compact('school','section','classList'));
    }

    public function update(School $school, Section $section, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($section->school_id === $school->id, 404);
        $data = $request->validate([
            'class_id' => ['required','exists:classes,id'],
            'name' => [
                'required','string','max:50',
                Rule::unique('sections','name')
                    ->ignore($section->id)
                    ->where(fn($q) => $q->where('school_id',$school->id)->where('class_id',$request->input('class_id'))),
            ],
            'class_teacher_name' => ['nullable','string','max:100'],
            'status' => ['required','in:active,inactive'],
        ]);
    abort_unless(SchoolClass::where('id',$data['class_id'])->where('school_id',$school->id)->exists(), 422);
    $section->update($data);
        return redirect()->route('principal.institute.sections.index', $school)->with('success','সেকশন আপডেট হয়েছে');
    }

    public function destroy(School $school, Section $section)
    {
        $this->authorizePrincipal($school);
        abort_unless($section->school_id === $school->id, 404);
        $section->delete();
        return redirect()->route('principal.institute.sections.index', $school)->with('success','সেকশন মুছে ফেলা হয়েছে');
    }
}
