<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\School;
use App\Models\User;

class ShiftController extends Controller
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
        $items = Shift::forSchool($school->id)
            ->when($q, fn($x) => $x->where('name','like',"%$q%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('principal.institute.shifts.index', compact('school','items','q'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.shifts.create', compact('school'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'start_time' => ['nullable','date_format:H:i'],
            'end_time' => ['nullable','date_format:H:i'],
            'status' => ['required','in:active,inactive'],
        ]);
        $data['school_id'] = $school->id;
        Shift::create($data);
        return redirect()->route('principal.institute.shifts.index', $school)->with('success','শিফট যুক্ত হয়েছে');
    }

    public function edit(School $school, Shift $shift)
    {
        $this->authorizePrincipal($school);
        abort_unless($shift->school_id === $school->id, 404);
        return view('principal.institute.shifts.edit', compact('school','shift'));
    }

    public function update(School $school, Shift $shift, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($shift->school_id === $school->id, 404);
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'start_time' => ['nullable','date_format:H:i'],
            'end_time' => ['nullable','date_format:H:i'],
            'status' => ['required','in:active,inactive'],
        ]);
        $shift->update($data);
        return redirect()->route('principal.institute.shifts.index', $school)->with('success','শিফট আপডেট হয়েছে');
    }

    public function destroy(School $school, Shift $shift)
    {
        $this->authorizePrincipal($school);
        abort_unless($shift->school_id === $school->id, 404);
        $shift->delete();
        return redirect()->route('principal.institute.shifts.index', $school)->with('success','শিফট মুছে ফেলা হয়েছে');
    }
}
