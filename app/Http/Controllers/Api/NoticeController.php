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

        // Security: If not super admin, strictly limit to schools where the user is a principal|teacher|parent
        if (!$user->isSuperAdmin()) {
            if ($schoolId) {
                if (!$user->hasRole('principal', $schoolId) && !$user->hasRole('teacher', $schoolId) && !$user->hasRole('parent', $schoolId)) {
                    return response()->json(['message' => 'অননুমোদিত'], 403);
                }
            } else {
                // Determine all schools where the user has any active role
                $schoolId = $user->activeSchoolRoles()->pluck('school_id')->unique()->toArray();
                
                if (empty($schoolId)) {
                    return response()->json(['message' => 'আপনার কোনো প্রতিষ্ঠানের সাথে সংযোগ পাওয়া যায়নি।'], 403);
                }
            }
        }

        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                if (is_array($schoolId)) {
                    $q->whereIn('school_id', $schoolId);
                } else {
                    $q->where('school_id', $schoolId);
                }
            });
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

        if (!$schoolId && !$user->isSuperAdmin()) {
            $schoolId = $user->primarySchool()?->id;
        }

        if (!$schoolId || (!$user->isPrincipal($schoolId) && !$user->isSuperAdmin())) {
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
        if ($request->has('targets') && is_array($request->input('targets'))) {
            foreach ($request->input('targets') as $target) {
                if (empty($target['id']) || empty($target['type'])) continue;

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
        
        // Send Push Notifications
        try {
            app(\App\Services\PushNotificationService::class)->sendNoticeNotification($notice);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Notice Push Error: ' . $e->getMessage());
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

        // Security: Prevent deleting notices from other schools
        if ($notice->school_id && $notice->school_id != $schoolId && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $notice->delete();
        return response()->json(['message' => 'নোটিশ মুছে ফেলা হয়েছে']);
    }

    public function stats(Notice $notice, Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;

        if (!$user->isPrincipal($schoolId) && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        // Security: Prevent viewing stats for notices from other schools
        if ($notice->school_id && $notice->school_id != $schoolId && !$user->isSuperAdmin()) {
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

            $teachers = $teacherQuery->get(['id', 'user_id', 'photo', 'first_name_bn', 'last_name_bn', 'first_name', 'last_name']);
            foreach ($teachers as $teacher) {
                $status = 'unread';
                if ($teacher->user_id && in_array($teacher->user_id, $reads)) $status = 'read';
                
                // For teachers, we check if they replied using their user_id (parent_id in NoticeReply)
                $replyKey = 'parent_' . $teacher->user_id;
                $voiceReply = null;
                if ($teacher->user_id && $replies->has($replyKey)) {
                    $status = 'replied';
                    $voiceReply = [
                        'url' => $replies[$replyKey]->voice_url,
                        'duration' => $replies[$replyKey]->duration,
                    ];
                }

                $nameBn = trim(($teacher->first_name_bn ?? '') . ' ' . ($teacher->last_name_bn ?? ''));
                $nameEn = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? ''));

                $recipientList->push([
                    'id' => $teacher->id,
                    'type' => 'teacher',
                    'name' => $nameBn ?: ($nameEn ?: 'N/A'),
                    'photo_url' => $teacher->photo_url,
                    'status' => $status,
                    'reply' => $voiceReply,
                    'details' => 'শিক্ষক'
                ]);
            }
        }

        // 2. Handle Students
        if ($notice->audience_type === 'all' || $notice->audience_type === 'students') {
            $studentQuery = \App\Models\Student::where('school_id', $schoolId)
                ->where('status', 'active')
                ->with(['currentEnrollment.class', 'currentEnrollment.section']);

            if ($notice->audience_type === 'students' && $notice->targets()->exists()) {
                $studentQuery->where(function($q) use ($notice) {
                    $targets = $notice->targets;
                    $studentIds = $targets->where('targetable_type', \App\Models\Student::class)->pluck('targetable_id');
                    if ($studentIds->isNotEmpty()) $q->orWhereIn('id', $studentIds);

                    $classIds = $targets->where('targetable_type', \App\Models\SchoolClass::class)->pluck('targetable_id');
                    if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);

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
                $className = $enroll?->class?->name ?? $student->class?->name;
                $sectionName = $enroll?->section?->name;

                $recipientList->push([
                    'id' => $student->id,
                    'type' => 'student',
                    'name' => $student->student_name_bn ?: ($student->student_name_en ?: 'N/A'),
                    'photo_url' => $student->photo_url,
                    'class_name' => $className,
                    'section_name' => $sectionName,
                    'roll' => $enroll?->roll_no,
                    'status' => $status,
                    'reply' => $voiceReply,
                    'details' => "ক্লাস: " . ($className ?? 'N/A') . 
                                 ", শাখা: " . ($sectionName ?? 'N/A') . 
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

