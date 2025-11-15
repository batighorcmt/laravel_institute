<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
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
        $subjects = Subject::forSchool($school->id)
            ->when($q, fn($x) => $x->where('name','like',"%$q%")->orWhere('code','like',"%$q%"))
            // Sort by code ascending (NULLs last), then by name
            ->orderByRaw('code IS NULL, code ASC')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('principal.institute.subjects.index', compact('school','subjects','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.subjects.create', compact('school'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'name' => ['required','string','max:100', Rule::unique('subjects','name')->where(fn($q)=>$q->where('school_id',$school->id))],
            'code' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'status' => ['required','in:active,inactive'],
            // Creation time: only pick available parts; no mark/pass config required
            'has_creative' => ['sometimes','boolean'],
            'has_mcq' => ['sometimes','boolean'],
            'has_practical' => ['sometimes','boolean'],
        ]);
        // Normalize booleans (default false)
        $data['has_creative'] = $request->boolean('has_creative');
        $data['has_mcq'] = $request->boolean('has_mcq');
        $data['has_practical'] = $request->boolean('has_practical');
        // Initialize marks/pass fields as null for later editing
        $data['creative_full_mark'] = null;
        $data['creative_pass_mark'] = null;
        $data['mcq_full_mark'] = null;
        $data['mcq_pass_mark'] = null;
        $data['practical_full_mark'] = null;
        $data['practical_pass_mark'] = null;
        $data['pass_type'] = null;
        $data['overall_full_mark'] = null;
        $data['overall_pass_mark'] = null;
        $data['school_id'] = $school->id;
        Subject::create($data);
        return redirect()->route('principal.institute.subjects.index', $school)->with('success','বিষয় যুক্ত হয়েছে');
    }

    public function edit(School $school, Subject $subject)
    {
        $this->authorizePrincipal($school);
        abort_unless($subject->school_id === $school->id, 404);
        return view('principal.institute.subjects.edit', compact('school','subject'));
    }

    public function update(School $school, Subject $subject, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($subject->school_id === $school->id, 404);
        // Simplified update: ignore marks & pass logic. Only update identity + part presence.
        $data = $request->validate([
            'name' => ['required','string','max:100', Rule::unique('subjects','name')->ignore($subject->id)->where(fn($q)=>$q->where('school_id',$school->id))],
            'code' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'status' => ['required','in:active,inactive'],
            'has_creative' => ['sometimes','boolean'],
            'has_mcq' => ['sometimes','boolean'],
            'has_practical' => ['sometimes','boolean'],
        ]);
        $data['has_creative'] = $request->boolean('has_creative');
        $data['has_mcq'] = $request->boolean('has_mcq');
        $data['has_practical'] = $request->boolean('has_practical');
        $subject->update($data); // existing mark/pass fields remain untouched
        return redirect()->route('principal.institute.subjects.index', $school)->with('success','বিষয় আপডেট হয়েছে (মার্ক তথ্য অপরিবর্তিত)');
    }

    public function destroy(School $school, Subject $subject)
    {
        $this->authorizePrincipal($school);
        abort_unless($subject->school_id === $school->id, 404);
        $subject->delete();
        return redirect()->route('principal.institute.subjects.index', $school)->with('success','বিষয় মুছে ফেলা হয়েছে');
    }
}
