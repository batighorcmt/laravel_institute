<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicYearController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function index(School $school)
    {
        $this->authorizePrincipal($school);
        $years = AcademicYear::forSchool($school->id)->orderByDesc('is_current')->orderByDesc('start_date')->get();
        return view('principal.institute.academic-years.index', compact('school','years'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.academic-years.create', compact('school'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'name' => ['required','string','max:50'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after:start_date'],
            'is_current' => ['sometimes','boolean']
        ]);
        $data['school_id'] = $school->id;
        $data['is_current'] = $request->boolean('is_current');
        $year = AcademicYear::create($data);
        if ($year->is_current) {
            AcademicYear::forSchool($school->id)->where('id','!=',$year->id)->update(['is_current'=>false]);
        }
        return redirect()->route('principal.institute.academic-years.index',$school)->with('success','শিক্ষাবর্ষ যোগ হয়েছে');
    }

    public function edit(School $school, AcademicYear $academic_year)
    {
        $this->authorizePrincipal($school);
        abort_unless($academic_year->school_id===$school->id,404);
        return view('principal.institute.academic-years.edit', compact('school','academic_year'));
    }

    public function update(School $school, AcademicYear $academic_year, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($academic_year->school_id===$school->id,404);
        $data = $request->validate([
            'name' => ['required','string','max:50'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after:start_date'],
            'is_current' => ['sometimes','boolean']
        ]);
        $data['is_current'] = $request->boolean('is_current');
        $academic_year->update($data);
        if ($academic_year->is_current) {
            AcademicYear::forSchool($school->id)->where('id','!=',$academic_year->id)->update(['is_current'=>false]);
        }
        return redirect()->route('principal.institute.academic-years.index',$school)->with('success','শিক্ষাবর্ষ হালনাগাদ হয়েছে');
    }

    public function destroy(School $school, AcademicYear $academic_year)
    {
        $this->authorizePrincipal($school);
        abort_unless($academic_year->school_id===$school->id,404);
        if ($academic_year->is_current) {
            return back()->with('error','বর্তমান শিক্ষাবর্ষ মুছতে পারবেন না');
        }
        $academic_year->delete();
        return back()->with('success','শিক্ষাবর্ষ মুছে ফেলা হয়েছে');
    }

    public function setCurrent(School $school, AcademicYear $academic_year)
    {
        $this->authorizePrincipal($school);
        abort_unless($academic_year->school_id===$school->id,404);
        AcademicYear::forSchool($school->id)->update(['is_current'=>false]);
        $academic_year->update(['is_current'=>true]);
        return back()->with('success','বর্তমান শিক্ষাবর্ষ নির্ধারণ হয়েছে');
    }
}
