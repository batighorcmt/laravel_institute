<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\NoticeReply;
use Illuminate\Support\Facades\Storage;

class NoticeInteractionController extends Controller
{
    public function markAsRead(Notice $notice, Request $request)
    {
        $user = $request->user();
        
        NoticeRead::updateOrCreate(
            ['notice_id' => $notice->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json(['message' => 'নোটিশ পড়া হয়েছে']);
    }

    public function storeReply(Notice $notice, Request $request)
    {
        $user = $request->user();

        if (!$notice->reply_required) {
            return response()->json(['message' => 'এই নোটিশে রিপ্লাই প্রয়োজন নেই'], 400);
        }

        // Check if user already replied
        $existingReply = NoticeReply::where('notice_id', $notice->id)
                                    ->where('parent_id', $user->id)
                                    ->first();
        if ($existingReply) {
            return response()->json([
                'message' => 'আপনার রিপ্লাই ইতিমধ্যে পাঠানো হয়েছে',
                'reply_id' => $existingReply->id,
            ]);
        }

        $request->validate([
            // Accept all common audio MIME types including audio/mp4 (Android .m4a), audio/aac, etc.
            'voice' => ['required', 'file', 'mimetypes:audio/mp4,audio/x-m4a,audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/webm,audio/aac,application/octet-stream', 'max:5120'],
            'duration' => ['required', 'numeric', 'max:31'],
        ]);

        $student = $user->student;
        
        $path = $request->file('voice')->store('notices/replies', 'public');

        $reply = NoticeReply::create([
            'notice_id' => $notice->id,
            'student_id' => $student?->id,
            'parent_id' => $user->id,
            'voice_path' => $path,
            'duration' => $request->duration,
        ]);

        return response()->json([
            'message' => 'রিপ্লাই সফলভাবে পাঠানো হয়েছে',
            'reply_id' => $reply->id
        ]);
    }
}
