<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionExam;
use App\Models\AdmissionExamSeatPlan;
use App\Models\AdmissionExamSeatRoom;
use App\Models\AdmissionExamSeatAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdmissionSeatPlanController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && $u->isPrincipal($school->id),403);
    }

    public function index(School $school)
    {
        $this->authorizePrincipal($school);
        $plans = AdmissionExamSeatPlan::where('school_id',$school->id)->with('exam')->orderByDesc('id')->paginate(20);
        $exams = AdmissionExam::where('school_id',$school->id)->orderByDesc('id')->get();
        return view('principal.institute.admissions.seat_plans.index', compact('school','plans','exams'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        $exams = AdmissionExam::where('school_id',$school->id)->orderByDesc('id')->get();
        return view('principal.institute.admissions.seat_plans.create', compact('school','exams'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'exam_id'=>['required', Rule::exists('admission_exams','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'name'=>['required','string','max:150'],
            'shift'=>['nullable','string','max:20'],
        ]);
        $data['school_id']=$school->id; $data['shift']=$data['shift'] ?? 'Morning';
        $plan = AdmissionExamSeatPlan::create($data);
        return redirect()->route('principal.institute.admissions.seat-plans.index',$school)->with('success','সীট প্ল্যান তৈরি হয়েছে');
    }

    public function edit(School $school, AdmissionExamSeatPlan $seatPlan)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id,404);
        $exams = AdmissionExam::where('school_id',$school->id)->orderByDesc('id')->get();
        return view('principal.institute.admissions.seat_plans.edit', compact('school','seatPlan','exams'));
    }

    public function update(School $school, AdmissionExamSeatPlan $seatPlan, Request $request)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id,404);
        $data = $request->validate([
            'exam_id'=>['required', Rule::exists('admission_exams','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'name'=>['required','string','max:150'],
            'shift'=>['nullable','string','max:20'],
            'status'=>['nullable', Rule::in(['active','inactive','completed'])]
        ]);
        $seatPlan->update($data);
        return redirect()->route('principal.institute.admissions.seat-plans.index',$school)->with('success','প্ল্যান আপডেট হয়েছে');
    }

    public function destroy(School $school, AdmissionExamSeatPlan $seatPlan)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id,404);
        // Cascades handled by FK constraints (rooms, allocations)
        $seatPlan->delete();
        return redirect()->route('principal.institute.admissions.seat-plans.index',$school)->with('success','প্ল্যান ও সংশ্লিষ্ট সকল রুম/বরাদ্দ মুছে গেছে');
    }

    public function rooms(School $school, AdmissionExamSeatPlan $seatPlan)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id,404);
        $seatPlan->load('rooms');
        return view('principal.institute.admissions.seat_plans.rooms', compact('school','seatPlan'));
    }

    public function storeRoom(School $school, AdmissionExamSeatPlan $seatPlan, Request $request)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id,404);
        $data = $request->validate([
            'room_no'=>['required','string','max:50'],
            'title'=>['nullable','string','max:255'],
            'columns_count'=>['nullable','integer','min:1','max:3'],
            'col1_benches'=>['nullable','integer','min:0'],
            'col2_benches'=>['nullable','integer','min:0'],
            'col3_benches'=>['nullable','integer','min:0'],
        ]);
        $data['columns_count'] = $data['columns_count'] ?? 3;
        if ($data['columns_count']<3) { $data['col3_benches']=0; }
        if ($data['columns_count']<2) { $data['col2_benches']=0; }
        $data['seat_plan_id']=$seatPlan->id;
        AdmissionExamSeatRoom::create($data);
        return back()->with('success','রুম যোগ হয়েছে');
    }

    public function deleteRoom(School $school, AdmissionExamSeatRoom $room)
    {
        $this->authorizePrincipal($school); abort_unless($room->seatPlan->school_id===$school->id,404);
        $room->delete();
        return back()->with('success','রুম মুছে ফেলা হয়েছে');
    }

    public function editRoom(School $school, AdmissionExamSeatRoom $room)
    {
        $this->authorizePrincipal($school); abort_unless($room->seatPlan->school_id===$school->id,404);
        $seatPlan = $room->seatPlan; $seatPlan->load('rooms');
        return view('principal.institute.admissions.seat_plans.room_edit', compact('school','seatPlan','room'));
    }

    public function updateRoom(School $school, AdmissionExamSeatRoom $room, Request $request)
    {
        $this->authorizePrincipal($school); abort_unless($room->seatPlan->school_id===$school->id,404);
        $data = $request->validate([
            'room_no'=>['required','string','max:50'],
            'title'=>['nullable','string','max:255'],
            'columns_count'=>['required','integer','min:1','max:3'],
            'col1_benches'=>['nullable','integer','min:0'],
            'col2_benches'=>['nullable','integer','min:0'],
            'col3_benches'=>['nullable','integer','min:0'],
        ]);
        if ($data['columns_count']<3) { $data['col3_benches']=0; }
        if ($data['columns_count']<2) { $data['col2_benches']=0; }
        $room->update($data);
        return redirect()->route('principal.institute.admissions.seat-plans.rooms', [$school,$room->seatPlan])->with('success','রুম আপডেট হয়েছে');
    }


    // Per-room allocation page
    public function allocateRoom(School $school, AdmissionExamSeatPlan $seatPlan, AdmissionExamSeatRoom $room)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id && $room->seat_plan_id===$seatPlan->id,404);
        $room->load('allocations');
        $seatPlan->load(['allocations','exam','school']);
        $allocatedIds = $seatPlan->allocations->pluck('application_id')->all();
        // Only show applications that are currently accepted (accepted_at not null, status == accepted)
        $availableApps = \App\Models\AdmissionApplication::where('school_id',$school->id)
            ->whereNotNull('accepted_at')
            ->where('status','accepted')
            ->whereNotNull('admission_roll_no')
            ->whereNotIn('id',$allocatedIds)
            ->orderBy('admission_roll_no')
            ->limit(500)
            ->get();
        // Map all allocated applications (even if later cancelled, they stay until manually unassigned)
        $appMap = \App\Models\AdmissionApplication::whereIn('id',$room->allocations->pluck('application_id')->all())
            ->get()->keyBy('id');
        return view('principal.institute.admissions.seat_plans.room_allocate', compact('school','seatPlan','room','availableApps','appMap'));
    }

    public function storeRoomAllocation(School $school, AdmissionExamSeatPlan $seatPlan, AdmissionExamSeatRoom $room, Request $request)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id && $room->seat_plan_id===$seatPlan->id,404);
        $data = $request->validate([
            'application_id'=>['required', Rule::exists('admission_applications','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'col_no'=>['required','integer','min:1'],
            'bench_no'=>['required','integer','min:1'],
            'position'=>['required', Rule::in(['L','R'])]
        ]);
        // Validate column and bench against room definition
        if ($data['col_no'] > $room->columns_count) {
            return back()->with('error','অবৈধ কলাম');
        }
        $benchField = 'col'.$data['col_no'].'_benches';
        if ($data['bench_no'] > ($room->$benchField ?? 0)) {
            return back()->with('error','অবৈধ বেঞ্চ');
        }
        // Prevent duplicate applicant allocation in entire seat plan
        if (AdmissionExamSeatAllocation::where('seat_plan_id',$seatPlan->id)->where('application_id',$data['application_id'])->exists()) {
            return back()->with('error','আবেদনকারী আগে বরাদ্দ হয়েছে');
        }
        // Prevent seat collision
        if (AdmissionExamSeatAllocation::where('seat_plan_id',$seatPlan->id)->where('room_id',$room->id)
            ->where('col_no',$data['col_no'])->where('bench_no',$data['bench_no'])->where('position',$data['position'])->exists()) {
            return back()->with('error','সীটটি পূর্ণ');
        }
        $data['seat_plan_id']=$seatPlan->id; $data['room_id']=$room->id;
        AdmissionExamSeatAllocation::create($data);
        return back()->with('success','সীট বরাদ্দ সফল');
    }

    public function deleteRoomAllocation(School $school, AdmissionExamSeatPlan $seatPlan, AdmissionExamSeatRoom $room, AdmissionExamSeatAllocation $allocation)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id && $room->seat_plan_id===$seatPlan->id && $allocation->seat_plan_id===$seatPlan->id && $allocation->room_id===$room->id,404);
        $allocation->delete();
        return back()->with('success','বরাদ্দ অপসারণ হয়েছে');
    }

    public function printRoom(School $school, AdmissionExamSeatPlan $seatPlan, AdmissionExamSeatRoom $room)
    {
        $this->authorizePrincipal($school); abort_unless($seatPlan->school_id===$school->id && $room->seat_plan_id===$seatPlan->id,404);
        $room->load('allocations');
        $seatPlan->load(['exam','school','allocations']);
        // Preload applications mapped by id for quick lookup
        $appMap = \App\Models\AdmissionApplication::whereIn('id',$room->allocations->pluck('application_id')->all())
            ->get()->keyBy('id');
        return view('principal.institute.admissions.seat_plans.room_print', compact('school','seatPlan','room','appMap'));
    }
}