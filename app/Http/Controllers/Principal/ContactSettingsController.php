<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use Illuminate\Http\Request;

class ContactSettingsController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.contact-settings', compact('school'));
    }

    public function data(School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);

        $messages = ContactMessage::where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'settings' => $settings,
            'messages' => $messages,
            'unread_count' => $messages->where('status', 'unread')->count(),
        ]);
    }

    public function updateSettings(Request $request, School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);

        $data = $request->validate([
            'contact_address' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:255',
            'contact_website' => 'nullable|string|max:255',
            'dshe_info_center' => 'nullable|string|max:255',
            'dshe_info_mobile' => 'nullable|string|max:255',
            'gro_name' => 'nullable|string|max:255',
            'gro_designation' => 'nullable|string|max:255',
            'gro_mobile' => 'nullable|string|max:255',
            'office_hours' => 'nullable|string|max:255',
            'map_embed_url' => 'nullable|string',
        ]);

        $settings->update($data);

        return response()->json([
            'message' => 'যোগাযোগ সেটিংস সংরক্ষণ করা হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }

    public function markMessageRead(School $school, ContactMessage $message)
    {
        abort_unless($message->school_id === $school->id, 404);

        $message->update(['status' => 'read']);

        return response()->json([
            'message' => 'পঠিত হিসেবে চিহ্নিত করা হয়েছে।',
        ]);
    }

    public function destroyMessage(School $school, ContactMessage $message)
    {
        abort_unless($message->school_id === $school->id, 404);

        $message->delete();

        return response()->json([
            'message' => 'বার্তা মুছে ফেলা হয়েছে।',
        ]);
    }
}
