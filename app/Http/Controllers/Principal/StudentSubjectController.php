<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\StudentSubject;
use App\Models\ClassSubject;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSubjectController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function edit(School $school, StudentEnrollment $enrollment)
    {
        $this->authorizePrincipal($school);
        abort_unless($enrollment->school_id === $school->id, 404);

        // Fetch mappings for this class; include group-specific and group-null (applies to all groups)
        // If the enrollment has no group assigned but the class uses groups, also
        // include mappings for any group defined for the class so students without
        // a group still see available subjects.
        $groupIds = Group::where('school_id', $school->id)->where('class_id', $enrollment->class_id)->pluck('id')->toArray();
        $mappings = ClassSubject::forSchool($school->id)
            ->where('class_id', $enrollment->class_id)
            ->where(function($q) use ($enrollment, $groupIds){
                $q->whereNull('group_id'); // subjects for all groups
                if ($enrollment->group_id) {
                    $q->orWhere('group_id', $enrollment->group_id);
                } else if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->with('subject')
            ->orderBy('order_no')
            ->get();

        $compulsoryFixed = $mappings->where('offered_mode','compulsory')->pluck('subject');
        $bothList = $mappings->where('offered_mode','both')->pluck('subject');
        $optionalOnly = $mappings->where('offered_mode','optional')->pluck('subject');

        $assigned = $enrollment->subjects()->pluck('subject_id')->toArray();
        $currentOptionalId = (int) $enrollment->subjects()->where('is_optional',true)->value('subject_id');
        $currentCompulsoryBothId = (int) $enrollment->subjects()
            ->whereIn('subject_id', $mappings->where('offered_mode','both')->pluck('subject_id')->all())
            ->where('is_optional', false)
            ->value('subject_id');

        $backUrl = url()->previous();
        if ($backUrl === url()->current()) {
            $backUrl = route('principal.institute.students.show', [$school, $enrollment->student_id]);
        }
        return view('principal.institute.students.subjects', [
            'school' => $school,
            'enrollment' => $enrollment->load(['student','class','section','group']),
            'compulsoryFixed' => $compulsoryFixed,
            'bothList' => $bothList,
            'optionalOnly' => $optionalOnly,
            'assigned' => $assigned,
            'currentOptionalId' => $currentOptionalId,
            'currentCompulsoryBothId' => $currentCompulsoryBothId,
            'backUrl' => $backUrl,
        ]);
    }

    public function update(School $school, StudentEnrollment $enrollment, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($enrollment->school_id === $school->id, 404);

        $data = $request->validate([
            'compulsory_both_id' => ['nullable','integer'],
            'optional_subject_id' => ['nullable','integer']
        ]);

        // Determine compulsory and optional mappings
        $groupIds = Group::where('school_id', $school->id)->where('class_id', $enrollment->class_id)->pluck('id')->toArray();
        $mappings = ClassSubject::forSchool($school->id)
            ->where('class_id', $enrollment->class_id)
            ->where(function($q) use ($enrollment, $groupIds){
                $q->whereNull('group_id');
                if ($enrollment->group_id) {
                    $q->orWhere('group_id', $enrollment->group_id);
                } else if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->with('subject')
            ->get();

        $compulsoryBase = $mappings->where('offered_mode','compulsory')->pluck('subject_id')->all();
        $bothIds = $mappings->where('offered_mode','both')->pluck('subject_id')->all();
        $optionalOnlyIds = $mappings->where('offered_mode','optional')->pluck('subject_id')->all();

        $selectedCompulsoryBoth = $data['compulsory_both_id'] ?? null;
        if ($selectedCompulsoryBoth && !in_array($selectedCompulsoryBoth, $bothIds)) {
            $selectedCompulsoryBoth = null;
        }

        $selectedOptional = $data['optional_subject_id'] ?? null;
        $optionalAllowed = array_values(array_unique(array_merge($bothIds, $optionalOnlyIds)));
        if ($selectedOptional && !in_array($selectedOptional, $optionalAllowed)) {
            $selectedOptional = null;
        }
        // Enforce distinctness
        if ($selectedCompulsoryBoth && $selectedOptional && (int)$selectedCompulsoryBoth === (int)$selectedOptional) {
            // If same, drop optional selection to honor rule
            $selectedOptional = null;
        }

        // Final subjects: compulsory base + selected BOTH-as-compulsory + selected optional
        $final = $compulsoryBase;
        if ($selectedCompulsoryBoth) { $final[] = (int)$selectedCompulsoryBoth; }
        if ($selectedOptional) { $final[] = (int)$selectedOptional; }
        $final = array_values(array_unique($final));

        // Sync: remove others
        $enrollment->subjects()->whereNotIn('subject_id', $final)->delete();
        // Upsert remaining with correct is_optional
        foreach ($final as $sid) {
            $enrollment->subjects()->updateOrCreate(
                ['student_enrollment_id'=>$enrollment->id, 'subject_id'=>$sid],
                ['is_optional' => ($selectedOptional && (int)$selectedOptional === (int)$sid), 'status'=>'active']
            );
        }

        // Save optional subject ID to student record for easy calculation
        $enrollment->student->update(['optional_subject_id' => $selectedOptional]);

        $redirectUrl = $request->input('redirect_to');
        if (empty($redirectUrl)) {
            $redirectUrl = route('principal.institute.students.show', [$school, $enrollment->student_id]);
        }

        return redirect($redirectUrl)->with('success','বিষয় সমূহ সংরক্ষিত হয়েছে');
    }
}
