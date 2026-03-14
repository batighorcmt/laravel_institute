<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\School;
use App\Models\User;
use Illuminate\Validation\Rule;

class GroupController extends Controller
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
        $items = Group::forSchool($school->id)
            ->with('class')
            ->when($q, fn($x) => $x->where('name','like',"%$q%"))
            ->orderBy('class_id')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('principal.institute.groups.index', compact('school','items','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        $classList = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
        return view('principal.institute.groups.create', compact('school','classList'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'class_id' => ['required','exists:classes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('groups')->where(function ($query) use ($school, $request) {
                    return $query->where('school_id', $school->id)
                                 ->where('class_id', $request->input('class_id'));
                })
            ],
            'bangla_name' => ['required','string','max:150'],
            'status' => ['required','in:active,inactive'],
        ]);
        $data['school_id'] = $school->id;
        Group::create($data);
        return redirect()->route('principal.institute.groups.index', $school)->with('success','গ্রুপ যুক্ত হয়েছে');
    }

    public function edit(School $school, Group $group)
    {
        $this->authorizePrincipal($school);
        abort_unless($group->school_id === $school->id, 404);
        $classList = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
        return view('principal.institute.groups.edit', compact('school','group','classList'));
    }

    public function update(School $school, Group $group, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($group->school_id === $school->id, 404);
        $data = $request->validate([
            'class_id' => ['required','exists:classes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('groups')->where(function ($query) use ($school, $request) {
                    return $query->where('school_id', $school->id)
                                 ->where('class_id', $request->input('class_id'));
                })->ignore($group->id)
            ],
            'bangla_name' => ['required','string','max:150'],
            'status' => ['required','in:active,inactive'],
        ]);
        $group->update($data);
        return redirect()->route('principal.institute.groups.index', $school)->with('success','গ্রুপ আপডেট হয়েছে');
    }

    public function destroy(School $school, Group $group)
    {
        $this->authorizePrincipal($school);
        abort_unless($group->school_id === $school->id, 404);
        $group->delete();
        return redirect()->route('principal.institute.groups.index', $school)->with('success','গ্রুপ মুছে ফেলা হয়েছে');
    }
}
