<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\School;
use App\Models\User;

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
            ->when($q, fn($x) => $x->where('name','like',"%$q%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('principal.institute.groups.index', compact('school','items','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.groups.create', compact('school'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'name' => ['required','string','max:100'],
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
        return view('principal.institute.groups.edit', compact('school','group'));
    }

    public function update(School $school, Group $group, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($group->school_id === $school->id, 404);
        $data = $request->validate([
            'name' => ['required','string','max:100'],
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
