<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-ক</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tiro+Bangla&display=swap');
        body { font-family: 'Tiro Bangla', 'Kalpurush', serif; font-size: 14px; margin: 0; padding: 10px; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid black; padding: 4px; text-align: center; font-size: 13px; }
        th { font-weight: bold; }
        .header-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: bold; }
        .paragraph-text { text-align: justify; line-height: 1.6; font-size: 14px; margin-bottom: 10px; }
        @media print {
            @page { size: landscape; margin: 10mm; }
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

    <h2 class="text-center" style="margin: 0; padding: 0;">পরিশিষ্ট "ক"</h2>
    
    <div style="margin-top: 10px; margin-bottom: 10px;">
        <div style="font-weight: bold; font-size: 15px; margin-bottom: 5px;">
            ইভেন্টের নাম: {{ $seasonEvent->event->name }} {{ $seasonEvent->subEvent ? ' (' . $seasonEvent->subEvent->name . ')' : '' }}
        </div>
        <div class="header-row" style="font-size: 15px;">
            <div>বিদ্যালয়ের নাম: {{ $school->name_bn ?? $school->name_en }}</div>
            <div>উপজেলা: {{ $school->upazila ?? '................' }}</div>
            <div>জেলা: {{ $school->district ?? '................' }}</div>
            <div>EIIN: {{ en2bn($school->eiin ?? '................') }}</div>
        </div>
    </div>

    <div class="paragraph-text">
        আমি এই মর্মে প্রত্যয়ন করিতেছি যে, নিম্নলিখিত প্রতিযোগীগণ যাহারা আমার সম্মুখে স্বাক্ষর করিয়াছে তাহারা আমার বিদ্যালয়ের নিয়মিত শিক্ষার্থী। বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতির গঠনতন্ত্রের বিধি মোতাবেক তাহারা <strong>{{ $competitionName }}</strong> জাতীয় ক্রীড়া প্রতিযোগিতায় অংশগ্রহণের যোগ্য। আমি আরও প্রত্যয়ন করিতেছি যে, তাহাদের কেহই এক বছরের বেশি একই শ্রেণীতে অধ্যয়ন করে নাই কিংবা নিচের শ্রেণীতে অবনমিত হয় নাই। নিম্নে প্রদত্ত শিক্ষার্থীর তালিকা পুঙ্খানুপুঙ্খরূপে যাচাই করা হইয়াছে। যাহাতে জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতির শর্ত অনুযায়ী তাদের যোগ্যতা, বয়স ও অভিভাবকের সম্মতিপত্র ইত্যাদি সঠিক বলিয়া প্রতীয়মান হইয়াছে।
    </div>

    <table>
        <thead>
            <tr>
                <th>ক্রমিক<br>নং</th>
                <th>ছাত্র/ছাত্রীর নাম</th>
                <th>পিতার নাম</th>
                <th>মাতার নাম</th>
                <th>শ্রেণী</th>
                <th>রোল<br>নং</th>
                <th>জন্ম তারিখ</th>
                <th>ভর্তির তারিখ</th>
                <th>বর্তমান বয়স</th>
                <th>টিসির<br>মাধ্যমে<br>ভর্তি</th>
                <th>উপস্থিতির<br>দিনের<br>সংখ্যা</th>
                <th>প্রতিযোগীর স্বাক্ষর</th>
            </tr>
        </thead>
        <tbody>
            @foreach($seasonEvent->players as $index => $player)
            <tr>
                <td>{{ en2bn($index + 1) }}</td>
                <td>{{ $player->student->student_name_bn ?? $player->student->student_name_en }}</td>
                <td>{{ $player->student->father_name_bn ?? $player->student->father_name_en }}</td>
                <td>{{ $player->student->mother_name_bn ?? $player->student->mother_name_en }}</td>
                <td>{{ $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? '-') }}</td>
                <td>{{ en2bn($player->student->currentEnrollment->roll_no ?? '-') }}</td>
                @php
                    $dobRaw = $player->student->getRawOriginal('date_of_birth');
                    $admDateRaw = $player->student->getRawOriginal('admission_date');
                @endphp
                <td>{{ $dobRaw ? en2bn(\Carbon\Carbon::parse($dobRaw)->format('d/m/Y')) : '-' }}</td>
                <td>{{ $admDateRaw ? en2bn(\Carbon\Carbon::parse($admDateRaw)->format('d/m/Y')) : '-' }}</td>
                <td>
                    @if($dobRaw && isset($player->calculated_age))
                        {{ en2bn($player->calculated_age['years']) }} বছর, 
                        {{ en2bn($player->calculated_age['months']) }} মাস, 
                        {{ en2bn($player->calculated_age['days']) }} দিন
                    @else
                        -
                    @endif
                </td>
                <td></td>
                <td>{{ en2bn($player->attendance_days ?? 0) }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
