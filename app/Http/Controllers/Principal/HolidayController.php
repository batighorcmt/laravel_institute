<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index(School $school)
    {
        $holidays = Holiday::where('school_id',$school->id)->orderByDesc('date')->get();
        $weekly = WeeklyHoliday::where('school_id',$school->id)->orderBy('day_number')->get();

        // Ensure weekly holidays rows exist (seed if missing)
        if ($weekly->count() === 0) {
            $names = [1=>'সোমবার',2=>'মঙ্গলবার',3=>'বুধবার',4=>'বৃহস্পতিবার',5=>'শুক্রবার',6=>'শনিবার',7=>'রবিবার'];
            foreach ($names as $num=>$name) {
                WeeklyHoliday::create(['school_id'=>$school->id,'day_number'=>$num,'day_name'=>$name,'status'=>'inactive']);
            }
            $weekly = WeeklyHoliday::where('school_id',$school->id)->orderBy('day_number')->get();
        }

        return view('principal.settings.holiday_management', compact('school','holidays','weekly'));
    }

    public function store(School $school, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            // Optional range end — when provided, a Holiday row is created for
            // every date from `date` through `date_end` (inclusive) instead of
            // just the one day, so e.g. a 4-day Eid break can be added in one go.
            'date_end' => 'nullable|date|after_or_equal:date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $start = Carbon::parse($data['date']);
        $end = isset($data['date_end']) ? Carbon::parse($data['date_end']) : $start->copy();

        if ($start->diffInDays($end) > 366) {
            return back()->with('error', 'একসাথে সর্বোচ্চ ৩৬৬ দিনের ছুটি যোগ করা যাবে। তারিখের পরিসর ছোট করুন।')->withInput();
        }

        $existingDates = Holiday::where('school_id', $school->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $created = 0;
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            if (in_array($cursor->toDateString(), $existingDates, true)) {
                continue;
            }
            Holiday::create([
                'school_id' => $school->id,
                'title' => $data['title'],
                'date' => $cursor->toDateString(),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);
            $created++;
        }

        $skipped = count($existingDates);
        if ($created === 0) {
            return back()->with('error', 'নির্বাচিত তারিখগুলোতে ইতিমধ্যেই ছুটি রয়েছে, নতুন কিছু যোগ করা হয়নি।')->withInput();
        }

        $message = $created === 1
            ? 'ছুটির দিন সফলভাবে যোগ করা হয়েছে'
            : "{$created} দিনের ছুটি সফলভাবে যোগ করা হয়েছে";
        if ($skipped > 0) {
            $message .= " ({$skipped}টি তারিখ আগে থেকেই ছুটি থাকায় বাদ দেওয়া হয়েছে)";
        }

        return back()->with('success', $message);
    }

    public function update(School $school, Holiday $holiday, Request $request)
    {
        abort_unless($holiday->school_id === $school->id, 404);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $exists = Holiday::where('school_id',$school->id)
            ->where('date',$data['date'])
            ->where('id','!=',$holiday->id)
            ->exists();
        if ($exists) { return back()->with('error','এই তারিখের জন্য ইতিমধ্যেই একটি ছুটি রয়েছে')->withInput(); }
        $holiday->update($data);
        return back()->with('success','ছুটির দিন সফলভাবে আপডেট করা হয়েছে');
    }

    public function destroy(School $school, Holiday $holiday)
    {
        abort_unless($holiday->school_id === $school->id, 404);
        $holiday->delete();
        return back()->with('success','ছুটির দিন সফলভাবে মুছে ফেলা হয়েছে');
    }

    public function updateWeekly(School $school, Request $request)
    {
        $ids = $request->input('weekly_holidays', []);
        // Set all inactive then activate selected
        WeeklyHoliday::where('school_id',$school->id)->update(['status'=>'inactive']);
        if (is_array($ids) && count($ids)>0) {
            WeeklyHoliday::where('school_id',$school->id)->whereIn('id',$ids)->update(['status'=>'active']);
        }
        return back()->with('success','সাপ্তাহিক ছুটির দিনগুলি সফলভাবে আপডেট করা হয়েছে');
    }
}
