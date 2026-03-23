<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\NoticeReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoticeInteractionController extends Controller
{
    /**
     * Mark notice as read.
     */
    public function markAsRead(Notice $notice, Request $request)
    {
        $user = $request->user();
        
        NoticeRead::updateOrCreate(
            ['notice_id' => $notice->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json(['message' => 'নোটিশটি পড়া হয়েছে হিসেবে চিহ্নিত করা হয়েছে']);
    }

    /**
     * Store a voice reply.
     */
    public function storeReply(Notice $notice, Request $request)
    {
        // For voice replies, we expect a multipart form data
        // containing 'voice' file.
        $request->validate([
            'voice' => 'required|file',
            'duration' => 'nullable',
            'student_id' => 'nullable'
        ]);

        $user = $request->user();
        
        // Store voice file in public storage
        $path = $request->file('voice')->store('notices/replies', 'public');

        NoticeReply::create([
            'notice_id' => $notice->id,
            'parent_id' => $user->id,
            'student_id' => $request->student_id,
            'voice_path' => $path,
            'duration' => $request->duration
        ]);

        return response()->json([
            'message' => 'রিপ্লাই সফলভাবে পাঠানো হয়েছে',
            'voice_url' => asset('storage/' . $path)
        ]);
    }
}