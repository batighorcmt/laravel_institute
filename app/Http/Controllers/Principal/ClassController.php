<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;

class ClassController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var User $u */ 
        $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function index(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $q = $request->get('q');

        $items = SchoolClass::forSchool($school->id)
            ->when($q, function($x) use ($q){
                $x->where('name','like',"%$q%")
                  ->orWhere('numeric_value',(int)$q);
            })
            ->ordered()
            ->paginate(10)
            ->withQueryString();

        return view('principal.institute.classes.index', compact('school','items','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.classes.create', compact('school'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);

        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'numeric_value' => ['required','integer','min:1','max:20'],
            'capacity' => ['required','integer','min:1','max:200'],
            'status' => ['required','in:active,inactive'],
        ]);

        $data['school_id'] = $school->id;

        // Duplicate check
        $exists = SchoolClass::where('school_id', $school->id)
                             ->where('numeric_value', $data['numeric_value'])
                             ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'এই ক্লাসটি ইতিমধ্যেই যুক্ত আছে!');
        }

        SchoolClass::create($data);

        return redirect()->route('principal.institute.classes.index', $school)
                         ->with('success','ক্লাস যুক্ত হয়েছে');
    }

    public function edit(School $school, SchoolClass $class)
    {
        $this->authorizePrincipal($school);
        abort_unless($class->school_id === $school->id, 404);

        return view('principal.institute.classes.edit', compact('school','class'));
    }

    public function update(School $school, SchoolClass $class, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($class->school_id === $school->id, 404);

        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'numeric_value' => ['required','integer','min:1','max:20'],
            'capacity' => ['required','integer','min:1','max:200'],
            'status' => ['required','in:active,inactive'],
        ]);

        // Duplicate check excluding current class
        $exists = SchoolClass::where('school_id', $school->id)
                             ->where('numeric_value', $data['numeric_value'])
                             ->where('id', '!=', $class->id)
                             ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'এই ক্লাসটি ইতিমধ্যেই যুক্ত আছে!');
        }

        $class->update($data);

        return redirect()->route('principal.institute.classes.index', $school)
                         ->with('success','ক্লাস আপডেট হয়েছে');
    }

    public function destroy(School $school, SchoolClass $class)
    {
        $this->authorizePrincipal($school);
        abort_unless($class->school_id === $school->id, 404);

        $class->delete();

        return redirect()->route('principal.institute.classes.index', $school)
                         ->with('success','ক্লাস মুছে ফেলা হয়েছে');
    }
}
