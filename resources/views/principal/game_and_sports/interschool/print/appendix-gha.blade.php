<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-ঘ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; line-height: 1.6; }
        .text-center { text-align: center; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid black; }
        table { width: 100%; margin-top: 20px; }
        td { padding: 5px; }
        .signature-area { margin-top: 50px; display: flex; justify-content: space-between; }
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
        <h2 class="text-center">পরিশিষ্ট-ঘ</h2>
        <h3 class="text-center">ক্রীড়া এন্ট্রি ফরম-২ (বিশেষ)</h3>
        <p class="text-center"><strong>ইভেন্টের নাম:</strong> {{ $seasonEvent->event->name }} {{ $seasonEvent->subEvent ? ' - ' . $seasonEvent->subEvent->name : '' }}</p>
        
        <table>
            <tr>
                <td width="30%"><strong>১। প্রতিযোগীর নাম:</strong></td>
                <td>{{ $player->student->student_name_bn ?? $player->student->student_name_en }}</td>
            </tr>
            <tr>
                <td><strong>২। পিতার নাম:</strong></td>
                <td>{{ $player->student->father_name_bn ?? $player->student->father_name_en }}</td>
            </tr>
            <tr>
                <td><strong>৩। মাতার নাম:</strong></td>
                <td>{{ $player->student->mother_name_bn ?? $player->student->mother_name_en }}</td>
            </tr>
            <tr>
                <td><strong>৪। শ্রেণি ও রোল:</strong></td>
                <td>{{ $player->student->currentEnrollment->class->bangla_name ?: ($player->student->currentEnrollment->class->name ?? '-') }}, রোল: {{ en2bn($player->student->currentEnrollment->roll_no ?? '-') }}</td>
            </tr>
            <tr>
                <td><strong>৫। জন্ম তারিখ:</strong></td>
                <td>{{ $player->student->date_of_birth ? en2bn(\Carbon\Carbon::parse($player->student->date_of_birth)->format('d/m/Y')) : '-' }}</td>
            </tr>
            <tr>
                <td><strong>৬। বর্তমান বয়স:</strong></td>
                <td>{{ $player->student->date_of_birth ? en2bn(\Carbon\Carbon::parse($player->student->date_of_birth)->age) : '-' }} বছর</td>
            </tr>
            <tr>
                <td><strong>৭। গ্রুপ:</strong></td>
                <td>{{ $player->group_name ?? '................' }}</td>
            </tr>
            <tr>
                <td><strong>৮। উচ্চতা:</strong></td>
                <td>{{ en2bn($player->height ?? '................') }}</td>
            </tr>
        </table>

        <div class="signature-area">
            <div class="text-center">
                <p>.......................................</p>
                <p>প্রতিযোগীর স্বাক্ষর</p>
            </div>
            <div class="text-center">
                <p>.......................................</p>
                <p>প্রধান শিক্ষকের স্বাক্ষর ও সিল</p>
            </div>
        </div>
    </div>
</body>
</html>
