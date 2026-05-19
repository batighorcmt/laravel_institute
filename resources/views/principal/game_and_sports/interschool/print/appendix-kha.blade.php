<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-খ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; line-height: 1.6; font-size: 16px; color: #000; margin: 0; padding: 20px; }
        .text-center { text-align: center; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header-title { text-align: center; font-weight: normal; margin-bottom: 5px; font-size: 18px; }
        .header-subtitle { text-align: center; font-weight: normal; margin-top: 0; margin-bottom: 40px; font-size: 18px; }
        .date-section { margin-bottom: 30px; }
        .content p { text-align: justify; margin-bottom: 20px; }
        .signature-area { margin-top: 60px; display: flex; justify-content: flex-end; }
        .signature-box { text-align: center; }
        .attested-area { margin-top: 40px; }
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
        
        $studentName = $player->student->student_name_bn ?? $player->student->student_name_en ?? null;
        $className = $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? null);
        
        $dobRaw = $player->student->getRawOriginal('date_of_birth');
        $ageYears = null;
        $ageMonths = null;
        $ageDays = null;
        if($dobRaw) {
            if (isset($player->calculated_age)) {
                $ageYears = en2bn($player->calculated_age['years']);
                $ageMonths = en2bn($player->calculated_age['months']);
                $ageDays = en2bn($player->calculated_age['days']);
            } else {
                // Fallback age calculation if controller doesn't provide it
                $ageDate = $seasonEvent->season->age_date ?? \Carbon\Carbon::now()->format('Y-m-d');
                $baseDate = \Carbon\Carbon::parse($ageDate);
                $diff = \Carbon\Carbon::parse($dobRaw)->diff($baseDate);
                $ageYears = en2bn($diff->y);
                $ageMonths = en2bn($diff->m);
                $ageDays = en2bn($diff->d);
            }
        }
    @endphp

    <div class="container">
        <div class="header-title">পরিশিষ্ট “খ”</div>
        <div class="header-subtitle">বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি</div>
        
        <div class="date-section">
            তারিখঃ.................................
        </div>

        <div class="content">
            <p>বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতির লক্ষ্য ও উদ্দেশ্যের সাথে একমত হইয়া আমি সম্মতি জ্ঞাপন করিতেছি যে, আমার পোষ্য {!! $studentName ? '<strong>'.$studentName.'</strong>' : '..............................................................' !!} ।</p>
            
            <p>{!! $className ? '<strong>'.$className.'</strong>' : '..............................' !!} শ্রেণীর শিক্ষার্থী যাহার বয়স {!! $ageYears ? '<strong>'.$ageYears.'</strong>' : '..............' !!} বছর {!! $ageMonths ? '<strong>'.$ageMonths.'</strong>' : '..................' !!} মাস {!! $ageDays ? '<strong>'.$ageDays.'</strong>' : '...............' !!} দিন। সে ২০{!! '<strong>'.en2bn(date('y')).'</strong>' !!} সালে সাঁতার/ ফুটবল/ কাবাডি/ ক্রিকেট/ বাস্কেটবল/ ভলিবল/ হকি/ অ্যাথলেটিক্স/ ব্যাডমিন্টন/ টেবিলটেনিস/ সাইক্লিং প্রতিযোগিতায় অংশগ্রহন করিবে। এমনকি উপযুক্ত হইলে সে জেলা, উপ-অঞ্চল, অঞ্চল ও জাতীয় পর্যায়ে অংশ গ্রহণ করিতে পারিবে। আমি অভিভাবক হিসেবে সম্মতি জ্ঞাপন করিলাম।</p>
        </div>

        <div class="signature-area">
            <div class="signature-box">
                <p style="margin: 0 0 5px 0;">নাম ও স্বাক্ষর</p>
                <p style="margin: 0 0 5px 0;">.........................................</p>
                <p style="margin: 0;">পিতা/ অভিভাবক</p>
            </div>
        </div>
        
        <div class="attested-area">
            <p style="margin: 0 0 40px 0;">সত্যায়িত</p>
            <p style="margin: 0 0 5px 0;">অধ্যক্ষ/ প্রধান শিক্ষক/ সুপারের স্বাক্ষর</p>
            <p style="margin: 0;">{{ $school->name_bn ?? ($school->name_en ?? 'বিদ্যালয়/ মাদ্রাসা') }}</p>
        </div>
    </div>
</body>
</html>
