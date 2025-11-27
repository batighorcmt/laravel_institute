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
        $date = $request->get('date', Carbon::now()->toDateString());
        $cacheKey = 'notices:'.$schoolId.':'.$date;
        $notices = Cache::remember($cacheKey, 60, function() use ($schoolId,$date){
            $q = Notice::published()->whereDate('publish_at','<=',$date)->orderByDesc('publish_at');
            if ($schoolId) { $q->forSchool($schoolId); }
            return $q->limit(50)->get();
        });
        return NoticeResource::collection($notices)->additional([
            'message' => 'নোটিশ তালিকা',
            'date' => $date,
            'cached' => true,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isPrincipal($request->attributes->get('current_school_id')) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'শুধু প্রিন্সিপাল নোটিশ তৈরি করতে পারবেন'], 403);
        }
        $validated = $request->validate([
            'title' => ['required','string','max:200'],
            'body' => ['required','string'],
            'publish_at' => ['nullable','date'],
            'status' => ['nullable','in:draft,published'],
        ]);
        $notice = Notice::create([
            'school_id' => $request->attributes->get('current_school_id'),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'publish_at' => $validated['publish_at'] ?? now(),
            'status' => $validated['status'] ?? 'published',
            'created_by' => $user->id,
        ]);
        Cache::forget('notices:'.$request->attributes->get('current_school_id').':'.now()->toDateString());
        return (new NoticeResource($notice))->additional(['message' => 'নোটিশ তৈরি সফল']);
    }
}

