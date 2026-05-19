<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-ঘ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; line-height: 1.6; font-size: 16px; color: #000; margin: 0; padding: 20px; }
        .text-center { text-align: center; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header-title { text-align: center; font-weight: normal; margin-bottom: 5px; font-size: 18px; }
        .header-subtitle { text-align: center; font-weight: normal; margin-top: 0; margin-bottom: 5px; font-size: 18px; }
        .divider { border-top: 1px solid #000; margin-bottom: 25px; margin-top: 10px; }
        
        .date-section { margin-bottom: 25px; }
        .address-section { margin-bottom: 30px; line-height: 1.4; }
        
        .content p { text-align: justify; margin-bottom: 20px; text-indent: 0; }
        .content-main { margin-bottom: 40px; }
        
        .signature-area { display: flex; justify-content: flex-end; margin-top: 70px; }
        .signature-box { text-align: center; line-height: 1.4; }
        @media print {
            @page { size: portrait; margin: 20mm; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        if (! function_exists('en2bn')) {
            function en2bn($number) {
                $en = ['0','1','2','3','4','5','6','7','8','9'];
                $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
                return str_replace($en, $bn, $number);
            }
        }
        
        $schoolName = $school->name_bn ?? $school->name_en ?? '';
        $upazila = $school->thana?->bn_name ?? ($school->thana?->name ?? ($school->upazila ?? ''));
        $district = $school->district?->bn_name ?? ($school->district?->name ?? ($school->district ?? ''));
        
        $studentName = $player->student->student_name_bn ?? $player->student->student_name_en ?? '';
        $fatherName = $player->student->father_name_bn ?? $player->student->father_name_en ?? '';
        $motherName = $player->student->mother_name_bn ?? $player->student->mother_name_en ?? '';
        $className = $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? '');
        
        $dobRaw = $player->student->getRawOriginal('date_of_birth');
        $dob = $dobRaw ? en2bn(\Carbon\Carbon::parse($dobRaw)->format('d/m/Y')) : '';
        
        $ageYears = '';
        $ageMonths = '';
        $ageDays = '';
        if($dobRaw) {
            if (isset($player->calculated_age)) {
                $ageYears = en2bn($player->calculated_age['years']);
                $ageMonths = en2bn($player->calculated_age['months']);
                $ageDays = en2bn($player->calculated_age['days']);
            } else {
                $ageDate = $seasonEvent->season->age_date ?? \Carbon\Carbon::now()->format('Y-m-d');
                $baseDate = \Carbon\Carbon::parse($ageDate);
                $diff = \Carbon\Carbon::parse($dobRaw)->diff($baseDate);
                $ageYears = en2bn($diff->y);
                $ageMonths = en2bn($diff->m);
                $ageDays = en2bn($diff->d);
            }
        }
        
        $groupName = $player->group_name ?? '';
        $height = en2bn($player->height ?? '');
        $eventName = $seasonEvent->event->name ?? '';
        
        $allPlayerEvents = \App\Models\InterschoolPlayer::where('student_id', $player->student_id)
            ->whereHas('seasonEvent', function ($query) use ($seasonEvent) {
                $query->where('interschool_season_id', $seasonEvent->interschool_season_id);
            })
            ->with(['seasonEvent.event', 'seasonEvent.subEvent'])
            ->get();
            
        $eventList = [];
        foreach ($allPlayerEvents as $pEvent) {
            $eName = $pEvent->seasonEvent->event->name ?? '';
            if ($pEvent->seasonEvent->subEvent) {
                $eName .= ' - ' . $pEvent->seasonEvent->subEvent->name;
            }
            if ($eName) {
                $eventList[] = $eName;
            }
        }
        $eventString = implode(', ', array_unique($eventList));
    @endphp

    <div class="container">
        <div class="header-title">পরিশিষ্ট “ঘ”</div>
        <div class="header-subtitle">বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি</div>
        <div class="header-title">এন্ট্রি ফরম-১</div>
        <div class="header-title" style="margin-bottom: 5px;">ব্যক্তিগত খেলা</div>
        <div class="divider"></div>
        
        <div class="date-section">
            তারিখঃ 
        </div>

        <div class="address-section">
            প্রতি<br>
            অবৈতনিক সম্পাদক<br>
            {{ $eventName }} প্রতিযোগিতা<br>
            বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি<br>
            উপজেলাঃ {{ $upazila }}, জেলাঃ {{ $district }}।
        </div>

        <div class="content content-main">
            <p style="margin-bottom: 5px;">জনাব,</p>
            <p style="margin-top: 0;">অনুগ্রহ পূর্বক {{ $schoolName }} এর {{ $className }} শ্রেণীর ছাত্র/ছাত্রী {{ $studentName }}, পিতাঃ {{ $fatherName }}, মাতাঃ {{ $motherName }}, জন্ম তারিখঃ {{ $dob }}, বর্তমান বয়সঃ {{ $ageYears }} বছর {{ $ageMonths }} মাস {{ $ageDays }} দিন। গ্রুপ {{ $groupName }}। উচ্চতা {{ $height }}।</p>
            
            <p><strong>প্রতিযোগিতার ইভেন্টসমূহঃ</strong> {{ $eventString }}</p>
            
            <p>প্রতিযোগি বিদ্যালয়ের একজন নিয়মিত ছাত্র/ছাত্রী। সে একই ক্লাসে এক বছরের বেশি অধ্যয়ন করে নাই কিংবা নিচের শ্রেণীতে অবনমিত হয় নাই। সে আমার সম্মুখে স্বাক্ষর করিয়াছে।</p>
        </div>

        <div class="signature-area">
            <div class="signature-box">
                প্রধান শিক্ষক<br>
                {{ $schoolName }}<br>
                উপজেলাঃ {{ $upazila }}, জেলাঃ {{ $district }}
            </div>
        </div>
    </div>
</body>
</html>
