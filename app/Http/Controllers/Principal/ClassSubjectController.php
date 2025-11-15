<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
{
    private function detectGroupType(?string $name): string
    {
        if (!$name) return 'other';
        $n = mb_strtolower(trim($name));
        $scienceKeys = ['science','বিজ্ঞান'];
        $humKeys = ['humanities','humanity','human','মানবিক'];
        $busKeys = ['business','commerce','business studies','বাণিজ্য','ব্যবসায়','ব্যবসায়'];
        foreach ($scienceKeys as $k) if (mb_strpos($n, $k) !== false) return 'science';
        foreach ($humKeys as $k) if (mb_strpos($n, $k) !== false) return 'humanities';
        foreach ($busKeys as $k) if (mb_strpos($n, $k) !== false) return 'business';
        return 'other';
    }

    private function isSubjectAgriculture(Subject $s): bool
    {
        $code = strtolower((string)$s->code);
        $name = mb_strtolower((string)$s->name);
        return str_contains($code,'agr') || mb_strpos($name,'কৃষি')!==false || mb_strpos($name,'কৃষিশিক্ষা')!==false || str_contains($name,'agriculture');
    }

    private function isSubjectBiology(Subject $s): bool
    {
        $code = strtolower((string)$s->code);
        $name = mb_strtolower((string)$s->name);
        return str_contains($code,'bio') || mb_strpos($name,'জীব')!==false || str_contains($name,'biology');
    }

    private function isSubjectHigherMath(Subject $s): bool
    {
        $code = strtolower((string)$s->code);
        $name = mb_strtolower((string)$s->name);
        return str_contains($code,'hm') || str_contains($code,'hmath') || mb_strpos($name,'উচ্চতর')!==false || str_contains($name,'higher math');
    }

    private function optionalAllowedForGroup(Subject $subject, ?Group $group, SchoolClass $class): bool
    {
        // For classes 6-8 (no groups), any one optional allowed (handled elsewhere)
        if (!$class->usesGroups()) return true;
        if (!$group) return false; // Require explicit group to mark optional in 9-10
        $type = $this->detectGroupType($group->name);
        if ($type === 'science') {
            return $this->isSubjectBiology($subject) || $this->isSubjectHigherMath($subject) || $this->isSubjectAgriculture($subject);
        }
        if ($type === 'humanities' || $type === 'business') {
            return $this->isSubjectAgriculture($subject);
        }
        // Unknown group: restrict to agriculture only to be safe
        return $this->isSubjectAgriculture($subject);
    }
    protected function authorizePrincipal(School $school): void
    {
        /** @var User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || !$user->isPrincipal()) {
            abort(403,'Unauthorized');
        }
        // Ensure principal owns the school (simplified: principal's primary school id must match)
        if (method_exists($user,'primarySchool') && $user->primarySchool()?->id !== $school->id) {
            abort(403,'ভুল প্রতিষ্ঠান');
        }
    }

    public function index(School $school, SchoolClass $class)
    {
        $this->authorizePrincipal($school);
        if ($class->school_id !== $school->id) abort(404);

        $subjects = Subject::forSchool($school->id)->orderBy('name')->get();
        $groups = $class->usesGroups() ? Group::forSchool($school->id)->orderBy('name')->get() : collect();
        $mappings = ClassSubject::with('subject','group')
            ->where('school_id',$school->id)
            ->where('class_id',$class->id)
            ->orderByRaw('COALESCE(order_no, 9999)')
            ->get();

        return view('principal.institute.classes.subjects.index', compact('school','class','subjects','mappings','groups'));
    }

    public function store(School $school, SchoolClass $class, Request $request)
    {
        $this->authorizePrincipal($school);
        if ($class->school_id !== $school->id) abort(404);

        $data = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'group_id' => ['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'group_ids' => ['sometimes','array'],
            'group_ids.*' => [Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'is_optional' => ['sometimes','boolean'],
            'offered_mode' => ['nullable','in:compulsory,optional,both']
        ]);

        // Enforce no group for classes 6-8
        if (!$class->usesGroups()) {
            $data['group_id'] = null;
        }

        // Offered mode and optional semantics
        $offeredMode = $data['offered_mode'] ?? 'compulsory';
        $isOptional = in_array($offeredMode, ['optional','both'], true);
        
        // Determine target groups to map to
        $targetGroupIds = [];
        if (!$class->usesGroups()) {
            $targetGroupIds = [null];
        } else {
            $inputIds = collect($data['group_ids'] ?? [])
                ->filter()->map(fn($v)=>(int)$v)->unique()->values()->all();
            if (empty($inputIds) && !empty($data['group_id'])) {
                $inputIds = [(int)$data['group_id']];
            }
            if ($isOptional && empty($inputIds)) {
                // Optional selected but no group specified: expand to all groups
                $targetGroupIds = Group::forSchool($school->id)->pluck('id')->all();
            } else {
                // If empty and not optional => common (null)
                $targetGroupIds = empty($inputIds) ? [null] : $inputIds;
            }
        }

        $added=0; $skipped=0; $blocked=0;
        $subject = Subject::find($data['subject_id']);
        // Optional subject constraint: only one optional allowed for classes 6-8 (business rule)
        if ($isOptional && !$class->usesGroups()) {
            $alreadyOptional = ClassSubject::where('school_id',$school->id)
                ->where('class_id',$class->id)
                ->where('is_optional',true)
                ->exists();
            if ($alreadyOptional) {
                return back()->with('error','ইতোমধ্যে একটি অপশনাল বিষয় নির্ধারিত আছে');
            }
        }

        foreach ($targetGroupIds as $gid) {
            // uniqueness
            $exists = ClassSubject::where('school_id',$school->id)
                ->where('class_id',$class->id)
                ->where('subject_id',$data['subject_id'])
                ->when($gid, fn($q)=>$q->where('group_id',$gid))
                ->when(!$gid, fn($q)=>$q->whereNull('group_id'))
                ->exists();
            if ($exists) { $skipped++; continue; }

            // Optional rules per target group
            $finalOptional = $isOptional;
            if ($isOptional && $class->usesGroups()) {
                $group = $gid ? Group::find($gid) : null;
                if (!$this->optionalAllowedForGroup($subject,$group,$class)) {
                    $finalOptional = false; $blocked++;
                }
            }

            ClassSubject::create([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'group_id' => $gid,
                'subject_id' => $data['subject_id'],
                'is_optional' => $finalOptional,
                'offered_mode' => $finalOptional ? $offeredMode : ($offeredMode==='optional' ? 'compulsory' : $offeredMode),
                'order_no' => null,
                'status' => 'active'
            ]);
            $added++;
        }

        $msg = "Added: $added" . ($skipped?" | Skipped(existing): $skipped":"") . ($blocked?" | Blocked optional: $blocked":"");
        return back()->with('success', $msg ?: 'কোনও পরিবর্তন হয়নি');
    }

    public function toggleOptional(School $school, SchoolClass $class, ClassSubject $mapping)
    {
        abort(404); // toggle removed
    }

    public function destroy(School $school, SchoolClass $class, ClassSubject $mapping)
    {
        $this->authorizePrincipal($school);
        if ($mapping->school_id !== $school->id || $mapping->class_id !== $class->id) abort(404);
        $mapping->delete();
        return back()->with('success','ম্যাপিং মুছে ফেলা হয়েছে');
    }

    public function bulkStore(School $school, SchoolClass $class, Request $request)
    {
        $this->authorizePrincipal($school);
        if ($class->school_id !== $school->id) abort(404);
        $data = $request->validate([
            'subject_ids' => ['required','array','min:1'],
            'subject_ids.*' => [Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'group_id' => ['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'group_ids' => ['sometimes','array'],
            'group_ids.*' => [Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'is_optional' => ['sometimes','boolean'],
            'offered_mode' => ['nullable','in:compulsory,optional,both']
        ]);
        $offeredMode = $data['offered_mode'] ?? 'compulsory';
        $baseOptional = in_array($offeredMode, ['optional','both'], true);
        $isOptional = $baseOptional;
        // Determine target groups for mapping
        $targetGroupIds = [];
        if (!$class->usesGroups()) {
            $targetGroupIds = [null];
        } else {
            $inputIds = collect($data['group_ids'] ?? [])
                ->filter()->map(fn($v)=>(int)$v)->unique()->values()->all();
            if (empty($inputIds) && !empty($data['group_id'])) {
                $inputIds = [(int)$data['group_id']];
            }
            if ($isOptional && empty($inputIds)) {
                // Optional selected but no group specified: expand to all groups
                $targetGroupIds = Group::forSchool($school->id)->pluck('id')->all();
            } else {
                $targetGroupIds = empty($inputIds) ? [null] : $inputIds;
            }
        }
        $added=0; $skipped=0; $blocked=0;
        foreach ($data['subject_ids'] as $sid) {
            $subject = Subject::find($sid);
            if (!$subject) { $skipped++; continue; }
            foreach ($targetGroupIds as $gid) {
                // uniqueness
                $exists = ClassSubject::where('school_id',$school->id)->where('class_id',$class->id)
                    ->where('subject_id',$sid)
                    ->when($gid, fn($q)=>$q->where('group_id',$gid))
                    ->when(!$gid, fn($q)=>$q->whereNull('group_id'))
                    ->exists();
                if ($exists) { $skipped++; continue; }

                // Optional rules
                $finalOptional = $isOptional;
                if ($isOptional) {
                    if (!$class->usesGroups()) {
                        $alreadyOptional = ClassSubject::where('school_id',$school->id)->where('class_id',$class->id)->where('is_optional',true)->exists();
                        if ($alreadyOptional) { $finalOptional = false; $blocked++; }
                    } else {
                        $group = $gid ? Group::find($gid) : null;
                        if (!$this->optionalAllowedForGroup($subject,$group,$class)) { $finalOptional=false; $blocked++; }
                    }
                }

                ClassSubject::create([
                    'school_id'=>$school->id,
                    'class_id'=>$class->id,
                    'group_id'=>$gid,
                    'subject_id'=>$sid,
                    'is_optional'=>$finalOptional,
                    'offered_mode'=> $finalOptional ? $offeredMode : ($offeredMode==='optional' ? 'compulsory' : $offeredMode),
                    'order_no'=>null,
                    'status'=>'active'
                ]);
                $added++;
                if (!$class->usesGroups() && $finalOptional) {
                    // stop further optional additions for 6-8
                    $isOptional = false; // subsequent become non-optional
                }
            }
        }
        $msg = "Added: $added | Skipped(existing): $skipped" . ($blocked?" | Blocked optional: $blocked":"");
        return back()->with('success',$msg);
    }

    public function updateOrder(School $school, SchoolClass $class, Request $request)
    {
        $this->authorizePrincipal($school);
        if ($class->school_id !== $school->id) abort(404);
        $data = $request->validate([
            'order' => ['required','array'],
            'order.*' => ['integer']
        ]);
        $position = 1;
        foreach ($data['order'] as $id) {
            ClassSubject::where('school_id',$school->id)->where('class_id',$class->id)->where('id',$id)->update(['order_no'=>$position++]);
        }
        return response()->json(['status'=>'ok']);
    }

    public function edit(School $school, SchoolClass $class, ClassSubject $mapping)
    {
        $this->authorizePrincipal($school);
        if ($mapping->school_id !== $school->id || $mapping->class_id !== $class->id) abort(404);
        return view('principal.institute.classes.subjects.edit', compact('school','class','mapping'));
    }

    public function update(School $school, SchoolClass $class, ClassSubject $mapping, Request $request)
    {
        $this->authorizePrincipal($school);
        if ($mapping->school_id !== $school->id || $mapping->class_id !== $class->id) abort(404);
        $data = $request->validate([
            'offered_mode' => ['required','in:compulsory,optional,both']
        ]);
        // Validate optional rules if switching to optional/both
        $wantOptional = in_array($data['offered_mode'], ['optional','both'], true);
        if ($wantOptional) {
            if (!$class->usesGroups()) {
                // ensure single optional in 6-8
                $another = ClassSubject::where('school_id',$school->id)->where('class_id',$class->id)
                    ->where('is_optional',true)->where('id','!=',$mapping->id)->exists();
                if ($another) return back()->with('error','৬–৮ শ্রেণিতে আগে থেকেই একটি অপশনাল আছে');
            } else {
                $group = $mapping->group; $subject = $mapping->subject;
                if (!$group) return back()->with('error','৯–১০ এ Optional/Both করতে গ্রুপ থাকা আবশ্যক');
                if (!$this->optionalAllowedForGroup($subject,$group,$class)) return back()->with('error','এই গ্রুপের জন্য এই বিষয়টি অপশনাল করা যাবে না');
            }
        }
        $mapping->update([
            'offered_mode' => $data['offered_mode'],
            'is_optional' => $wantOptional // sync current flag to match mode for clarity
        ]);
        return redirect()->route('principal.institute.classes.subjects.index',[$school,$class])->with('success','হালনাগাদ হয়েছে');
    }
}
