<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-গ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; line-height: 1.6; font-size: 16px; color: #000; margin: 0; padding: 20px; }
        .text-center { text-align: center; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header-title { text-align: center; font-weight: normal; margin-bottom: 5px; font-size: 18px; }
        .header-subtitle { text-align: center; font-weight: normal; margin-top: 0; margin-bottom: 40px; font-size: 18px; }
        
        .date-section { margin-bottom: 25px; }
        .address-section { margin-bottom: 30px; line-height: 1.4; }
        
        .school-info { margin-bottom: 30px; line-height: 1.4; }
        
        .content p { text-align: justify; margin-bottom: 5px; text-indent: 0; }
        .content-main { margin-bottom: 40px; }
        
        .student-info { margin-bottom: 80px; }
        .student-row { margin-bottom: 5px; }
        .student-row-flex { display: flex; justify-content: space-between; margin-bottom: 5px; }
        
        .signature-area { display: flex; justify-content: flex-end; margin-top: 50px; }
        .signature-box { text-align: center; line-height: 1.4; }
        @media print {
            @page { size: portrait; margin: 20mm; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $schoolName = $school->name_bn ?? $school->name_en ?? '';
        $upazila = $school->thana?->bn_name ?? ($school->thana?->name ?? ($school->upazila ?? ''));
        $district = $school->district?->bn_name ?? ($school->district?->name ?? ($school->district ?? ''));
        $postOffice = $school->post_office ?? '';
        
        $studentName = $player->student->student_name_bn ?? $player->student->student_name_en ?? '';
        $fatherName = $player->student->father_name_bn ?? $player->student->father_name_en ?? '';
        $className = $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? '');
    @endphp

    <div class="container">
        <div class="header-title">পরিশিষ্ট “গ”</div>
        <div class="header-subtitle">বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি</div>
        
        <div class="date-section">
            তারিখঃ 
        </div>

        <div class="address-section">
            সম্পাদক<br>
            উপজেলা/থানা/জোনাল কমিটি
        </div>
        
        <div class="school-info">
            প্রতিষ্ঠানের নামঃ {{ $schoolName }}<br>
            ডাকঘরঃ {{ $postOffice }}, উপজেলাঃ {{ $upazila }}, জেলাঃ {{ $district }}।
        </div>

        <div class="content content-main">
            <p>জনাব</p>
            <p style="margin-top: 0;">প্রত্যয়ন যাইতেছে যে, বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি কর্তৃক আয়োজিত সাঁতার/ ফুটবল/ কাবাডি/ হ্যান্ডবল/ দাবা/ ক্রিকেট/ বাস্কেটবল/ ভলিবল/ হকি/ অ্যাথলেটিক্স/ ব্যাডমিন্টন/ টেবিলটেনিস/ সাইক্লিং প্রতিযোগিতায় অংশগ্রহণের জন্য নিম্নলিখিত শিক্ষার্থীর অভিভাবক সম্মতিপত্র বিদ্যালয়ের প্রধান শিক্ষকের অফিসে নথিয়ুক্ত করা আছে। খেলায় অংশগ্রহণের জন্য ব্যাক্তিগত ফটো/গ্রুপ ফটো সংযুক্ত করা হইয়াছে।</p>
        </div>
        
        <div class="student-info">
            <div class="student-row">
                ক্রমিক নংঃ ------------------------শিক্ষার্থীর নামঃ {{ $studentName }}
            </div>
            <div class="student-row-flex">
                <div>পিতার নামঃ {{ $fatherName }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;শ্রেণিঃ {{ $className }}</div>
            </div>
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
