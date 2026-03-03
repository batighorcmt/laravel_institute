<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Http\Resources\NoticeResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        $user = $request->user();

        $query = Notice::published()->active()->orderByDesc('publish_at');
        
        if ($schoolId) {
            $query->forSchool($schoolId);
        }

        // Logic for different audiences
        if (!$user->isPrincipal($schoolId) && !$user->isSuperAdmin()) {
            $query->where(function($q) use ($user) {
                // Global notices
                $q->where('audience_type', 'all');

                // Teacher notices
                if ($user->teacher) {
                    $q->orWhere(function($qq) use ($user) {
                        $qq->where('audience_type', 'teachers')
                           ->where(function($qqq) use ($user) {
                               $qqq->doesntHave('targets')
                                   ->orWhereHas('targets', function($t) use ($user) {
                                       $t->where('targetable_type', \App\Models\Teacher::class)
                                         ->where('targetable_id', $user->teacher->id);
                                   });
                           });
                    });
                }

                // Student/Parent notices
                $student = $user->student; // Assuming parent user has student relation or similar
                if ($student) {
                    $q->orWhere(function($qq) use ($student) {
                        $qq->where('audience_type', 'students')
                           ->where(function($qqq) use ($student) {
                               $qqq->doesntHave('targets')
                                   ->orWhereHas('targets', function($t) use ($student) {
                                       $t->where(function($tt) use ($student) {
                                           $tt->where('targetable_type', \App\Models\Student::class)
                                              ->where('targetable_id', $student->id);
                                       })->orWhere(function($tt) use ($student) {
                                           $tt->where('targetable_type', \App\Models\SchoolClass::class)
                                              ->where('targetable_id', $student->class_id);
                                       })->orWhere(function($tt) use ($student) {
                                           $tt->where('targetable_type', \App\Models\Section::class)
                                              ->where('targetable_id', $student->currentEnrollment?->section_id);
                                       })->orWhere(function($tt) use ($student) {
                                           $tt->where('targetable_type', \App\Models\Group::class)
                                              ->where('targetable_id', $student->group_id);
                                       });
                                   });
                           });
                    });
                }
            });
        }

        $notices = $query->paginate(20);
        
        return NoticeResource::collection($notices);
    }

    public function show(Notice $notice)
    {
        return new NoticeResource($notice);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        if (! $user->isPrincipal($schoolId) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $validated = $request->validate([
            'title' => ['required','string','max:200'],
            'body' => ['required','string'],
            'audience_type' => ['required','in:all,teachers,students'],
            'publish_at' => ['nullable','date'],
            'expiry_at' => ['nullable','date','after:publish_at'],
            'reply_required' => ['nullable','boolean'],
            'status' => ['nullable','in:draft,published'],
            'targets' => ['nullable', 'array'], // array of ['id' => X, 'type' => 'Teacher|Student|Class|Section']
        ]);

        $notice = Notice::create([
            'school_id' => $schoolId,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience_type' => $validated['audience_type'],
            'reply_required' => $validated['reply_required'] ?? false,
            'publish_at' => $validated['publish_at'] ?? now(),
            'expiry_at' => $validated['expiry_at'] ?? null,
            'status' => $validated['status'] ?? 'published',
            'created_by' => $user->id,
        ]);

        // Handle targets
        if (!empty($validated['targets'])) {
            foreach ($validated['targets'] as $target) {
                $typeMap = [
                    'Teacher' => \App\Models\Teacher::class,
                    'Student' => \App\Models\Student::class,
                    'Class'   => \App\Models\SchoolClass::class,
                    'Section' => \App\Models\Section::class,
                    'Group'   => \App\Models\Group::class,
                ];

                if (isset($typeMap[$target['type']])) {
                    $notice->targets()->create([
                        'targetable_id' => $target['id'],
                        'targetable_type' => $typeMap[$target['type']],
                    ]);
                }
            }
        }

        return (new NoticeResource($notice))->additional(['message' => 'নোটিশ তৈরি সফল']);
    }

    public function update(Request $request, Notice $notice)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        if (! $user->isPrincipal($schoolId) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $validated = $request->validate([
            'title' => ['required','string','max:200'],
            'body' => ['required','string'],
            'audience_type' => ['required','in:all,teachers,students'],
            'publish_at' => ['nullable','date'],
            'expiry_at' => ['nullable','date','after:publish_at'],
            'reply_required' => ['nullable','boolean'],
            'status' => ['nullable','in:draft,published'],
            'targets' => ['nullable', 'array'],
        ]);

        $notice->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience_type' => $validated['audience_type'],
            'reply_required' => $validated['reply_required'] ?? false,
            'publish_at' => $validated['publish_at'] ?? now(),
            'expiry_at' => $validated['expiry_at'] ?? null,
            'status' => $validated['status'] ?? 'published',
        ]);

        // Refresh targets
        $notice->targets()->delete();
        if (!empty($validated['targets'])) {
            foreach ($validated['targets'] as $target) {
                $typeMap = [
                    'Teacher' => \App\Models\Teacher::class,
                    'Student' => \App\Models\Student::class,
                    'Class'   => \App\Models\SchoolClass::class,
                    'Section' => \App\Models\Section::class,
                    'Group'   => \App\Models\Group::class,
                ];

                if (isset($typeMap[$target['type']])) {
                    $notice->targets()->create([
                        'targetable_id' => $target['id'],
                        'targetable_type' => $typeMap[$target['type']],
                    ]);
                }
            }
        }

        return (new NoticeResource($notice))->additional(['message' => 'নোটিশ আপডেট সফল']);
    }

    public function destroy(Request $request, Notice $notice)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        if (! $user->isPrincipal($schoolId) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $notice->delete();
        return response()->json(['message' => 'নোটিশ মুছে ফেলা হয়েছে']);
    }

    public function stats(Notice $notice, Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        if (!$request->user()->isPrincipal($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $reads = $notice->reads()->pluck('user_id')->toArray();
        $replies = $notice->replies()->get()->keyBy(function($r) {
            return $r->student_id ? 'student_'.$r->student_id : 'parent_'.$r->parent_id;
        });

        $recipientList = collect();

        // 1. Handle Teachers
        if ($notice->audience_type === 'all' || $notice->audience_type === 'teachers') {
            $teacherQuery = \App\Models\Teacher::where('school_id', $schoolId)->where('status', 'active');
            
            if ($notice->audience_type === 'teachers' && $notice->targets()->where('targetable_type', \App\Models\Teacher::class)->exists()) {
                $targetIds = $notice->targets()->where('targetable_type', \App\Models\Teacher::class)->pluck('targetable_id');
                $teacherQuery->whereIn('id', $targetIds);
            }

            $teachers = $teacherQuery->get(['id', 'user_id', 'teacher_name_bn', 'teacher_name_en']);
            foreach ($teachers as $teacher) {
                $status = 'unread';
                if ($teacher->user_id && in_array($teacher->user_id, $reads)) $status = 'read';
                // Check for teacher reply if applicable (though currently repo handles voice via parent/student)
                
                $recipientList->push([
                    'id' => $teacher->id,
                    'type' => 'teacher',
                    'name' => $teacher->teacher_name_bn ?: $teacher->teacher_name_en,
                    'status' => $status,
                    'details' => 'শিক্ষক'
                ]);
            }
        }

        // 2. Handle Students/Parents
        if ($notice->audience_type === 'all' || $notice->audience_type === 'students') {
            $studentQuery = \App\Models\Student::where('school_id', $schoolId)
                ->where('status', 'active')
                ->with(['class', 'currentEnrollment.section']);

            if ($notice->audience_type === 'students' && $notice->targets()->exists()) {
                $studentQuery->where(function($q) use ($notice) {
                    $targets = $notice->targets;
                    
                    // Direct student targets
                    $studentIds = $targets->where('targetable_type', \App\Models\Student::class)->pluck('targetable_id');
                    if ($studentIds->isNotEmpty()) $q->orWhereIn('id', $studentIds);

                    // Class targets
                    $classIds = $targets->where('targetable_type', \App\Models\SchoolClass::class)->pluck('targetable_id');
                    if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);

                    // Section targets
                    $sectionIds = $targets->where('targetable_type', \App\Models\Section::class)->pluck('targetable_id');
                    if ($sectionIds->isNotEmpty()) {
                        $q->orWhereHas('currentEnrollment', function($sq) use ($sectionIds) {
                            $sq->whereIn('section_id', $sectionIds);
                        });
                    }
                });
            }

            $students = $studentQuery->get();
            foreach ($students as $student) {
                $status = 'unread';
                if ($student->user_id && in_array($student->user_id, $reads)) $status = 'read';
                
                $replyKey = 'student_' . $student->id;
                $voiceReply = null;
                if ($replies->has($replyKey)) {
                    $status = 'replied';
                    $voiceReply = [
                        'url' => $replies[$replyKey]->voice_url,
                        'duration' => $replies[$replyKey]->duration,
                    ];
                }

                $enroll = $student->currentEnrollment;
                $recipientList->push([
                    'id' => $student->id,
                    'type' => 'student',
                    'name' => $student->student_name_bn ?: $student->student_name_en,
                    'class_name' => $student->class?->class_name,
                    'section_name' => $enroll?->section?->section_name,
                    'roll' => $enroll?->roll_no,
                    'status' => $status,
                    'reply' => $voiceReply,
                    'details' => "ক্লাস: " . ($student->class?->class_name ?? 'N/A') . 
                                 ", শাখা: " . ($enroll?->section?->section_name ?? 'N/A') . 
                                 ", রোল: " . ($enroll?->roll_no ?? 'N/A')
                ]);
            }
        }

        return response()->json([
            'notice' => new NoticeResource($notice),
            'stats' => [
                'total_recipients' => $recipientList->count(),
                'read_count' => $recipientList->whereIn('status', ['read', 'replied'])->count(),
                'reply_count' => $recipientList->where('status', 'replied')->count(),
                'all' => $recipientList->values()
            ]
        ]);
    }
}

