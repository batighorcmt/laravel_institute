<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\Teacher;
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

        $teachers = Teacher::where('school_id', $school->id)
            ->with('designationRef:id,name_en,name_bn')
            ->orderByRaw('COALESCE(serial_number, 999999)')
            ->orderBy('id')
            ->get()
            ->map(fn (Teacher $t) => [
                'id' => $t->id,
                'name' => $t->full_name_bn ?: $t->full_name,
                'designation' => $t->designationRef ? ($t->designationRef->name_bn ?: $t->designationRef->name_en) : ($t->designation ?: ''),
                'phone' => $t->phone,
            ])
            ->values();

        return response()->json([
            'settings' => [
                'contact_address' => $settings->contact_address ?: $school->address_bn,
                'contact_phone' => $settings->contact_phone ?: $school->phone,
                'contact_mobile' => $settings->contact_mobile ?: $school->mobile,
                'contact_email' => $settings->contact_email ?: $school->email,
                'contact_website' => $settings->contact_website ?: $school->website,
                'contact_email_secondary' => $settings->contact_email_secondary,
                'dshe_info_center' => $settings->dshe_info_center,
                'dshe_info_mobile' => $settings->dshe_info_mobile,
                'gro_teacher_id' => $settings->gro_teacher_id,
                'gro_name' => $settings->gro_name,
                'gro_designation' => $settings->gro_designation,
                'gro_mobile' => $settings->gro_mobile,
                'office_hours' => $settings->office_hours,
                'map_embed_url' => $settings->map_embed_url,
            ],
            'teachers' => $teachers,
            'messages' => $messages,
            'unread_count' => $messages->where('status', 'unread')->count(),
        ]);
    }

    public function updateSettings(Request $request, School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);

        $data = $request->validate([
            'contact_address' => 'nullable|string|max:500',
            'contact_phone' => 'nullable|string|max:50',
            'contact_mobile' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'contact_website' => 'nullable|string|max:255',
            'contact_email_secondary' => 'nullable|email|max:255',
            'dshe_info_center' => 'nullable|string|max:255',
            'dshe_info_mobile' => 'nullable|string|max:255',
            'gro_teacher_id' => 'nullable|exists:teachers,id',
            'office_hours' => 'nullable|string|max:255',
            'map_embed_url' => 'nullable|string',
        ]);

        $groTeacher = ! empty($data['gro_teacher_id'])
            ? Teacher::where('school_id', $school->id)->find($data['gro_teacher_id'])
            : null;

        $settings->update([
            'contact_address' => $data['contact_address'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_mobile' => $data['contact_mobile'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_website' => $data['contact_website'] ?? null,
            'contact_email_secondary' => $data['contact_email_secondary'] ?? null,
            'dshe_info_center' => $data['dshe_info_center'] ?? null,
            'dshe_info_mobile' => $data['dshe_info_mobile'] ?? null,
            'office_hours' => $data['office_hours'] ?? null,
            'map_embed_url' => $data['map_embed_url'] ?? null,
            'gro_teacher_id' => $groTeacher?->id,
            'gro_name' => $groTeacher ? ($groTeacher->full_name_bn ?: $groTeacher->full_name) : null,
            'gro_designation' => $groTeacher ? ($groTeacher->designationRef?->name_bn ?: $groTeacher->designationRef?->name_en ?: $groTeacher->designation) : null,
            'gro_mobile' => $groTeacher?->phone,
        ]);

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
