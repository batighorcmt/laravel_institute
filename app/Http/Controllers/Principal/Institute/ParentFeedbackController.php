<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\ParentFeedback;
use App\Models\School;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class ParentFeedbackController extends Controller
{
    public function index(School $school, Request $request)
    {
        $status = $request->query('status');

        $feedbacks = ParentFeedback::with(['user', 'student'])
            ->where('school_id', $school->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('principal.institute.parent-feedback.index', compact('school', 'feedbacks', 'status'));
    }

    public function show(School $school, ParentFeedback $feedback)
    {
        abort_unless($feedback->school_id === $school->id, 404);

        if ($feedback->status === 'pending') {
            $feedback->update(['status' => 'read']);
        }

        return view('principal.institute.parent-feedback.show', compact('school', 'feedback'));
    }

    public function reply(School $school, ParentFeedback $feedback, Request $request, PushNotificationService $pushService)
    {
        abort_unless($feedback->school_id === $school->id, 404);

        $data = $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        $feedback->update([
            'reply' => $data['reply'],
            'status' => 'replied',
        ]);

        try {
            $pushService->sendFeedbackReplyNotification($feedback);
        } catch (\Throwable $e) {
            \Log::error('Feedback reply push failed: '.$e->getMessage());
        }

        return redirect()
            ->route('principal.institute.parent-feedback.index', $school)
            ->with('success', 'উত্তর পাঠানো হয়েছে।');
    }
}
