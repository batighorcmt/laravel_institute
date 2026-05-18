<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-খ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; line-height: 1.6; }
        .text-center { text-align: center; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .content { margin-top: 30px; font-size: 16px; }
        .signature-area { margin-top: 60px; display: flex; justify-content: space-between; }
        @media print {
            @page { size: portrait; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        function en2bn($number) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($en, $bn, $number);
        }
    @endphp
    <div class="container">
        <h2 class="text-center">পরিশিষ্ট-খ</h2>
        <h3 class="text-center">অবিভাবকের সম্মতিপত্র</h3>
        
        <div class="content">
            <p>আমি নিম্নস্বাক্ষরকারী এই মর্মে সম্মতি প্রদান করছি যে, আমার পুত্র/কন্যা 
            <strong>{{ $player->student->student_name_bn ?? $player->student->student_name_en }}</strong>, 
            শ্রেণি: <strong>{{ $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? '-') }}</strong>, 
            রোল: <strong>{{ en2bn($player->student->currentEnrollment->roll_no ?? '-') }}</strong> 
            আগামী আন্তঃস্কুল প্রতিযোগিতায় <strong>{{ $seasonEvent->event->name }} {{ $seasonEvent->subEvent ? ' - ' . $seasonEvent->subEvent->name : '' }}</strong> ইভেন্টে অংশগ্রহণের জন্য সম্পূর্ণ শারীরিক ও মানসিকভাবে উপযুক্ত। 
            খেলার সময় কোনরূপ দুর্ঘটনা বা শারীরিক ক্ষতি হলে বিদ্যালয় কর্তৃপক্ষ দায়ী থাকবে না।</p>
            
            <p><strong>শিক্ষার্থীর জন্ম তারিখ:</strong> {{ $player->student->date_of_birth ? en2bn(\Carbon\Carbon::parse($player->student->date_of_birth)->format('d/m/Y')) : '-' }}</p>
            <p><strong>বর্তমান বয়স:</strong> {{ $player->student->date_of_birth ? en2bn(\Carbon\Carbon::parse($player->student->date_of_birth)->age) : '-' }} বছর</p>
            <p><strong>উচ্চতা:</strong> {{ en2bn($player->height ?? '................') }}</p>
            <p><strong>ওজন:</strong> {{ en2bn($player->weight ?? '................') }}</p>
        </div>

        <div class="signature-area">
            <div>
                <p>.......................................</p>
                <p>শ্রেণি শিক্ষকের স্বাক্ষর</p>
            </div>
            <div>
                <p>.......................................</p>
                <p>অবিভাবকের স্বাক্ষর</p>
                <p>তারিখ: {{ date('d/m/Y') }}</p>
            </div>
        </div>
        
        <div class="signature-area" style="justify-content: center; margin-top: 40px;">
            <div class="text-center">
                <p>.......................................</p>
                <p>প্রধান শিক্ষকের স্বাক্ষর ও সিল</p>
            </div>
        </div>
    </div>
</body>
</html>
