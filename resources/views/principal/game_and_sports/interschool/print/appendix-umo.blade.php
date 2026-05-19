<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>পরিশিষ্ট-ঙ</title>
    <link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
    <style>
        body { font-family: 'Nikosh', sans-serif; font-size: 15px; margin: 0; padding: 20px 40px; line-height: 1.5; }
        .text-center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 6px; text-align: center; font-size: 14px; }
        .signature-area { margin-top: 50px; display: flex; justify-content: flex-end; }
        @media print {
            @page { size: portrait; margin: 10mm; }
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

    <div class="text-center" style="font-weight: bold;">
        <div style="font-size: 18px;">পরিশিষ্ট "ঙ"</div>
        <div style="font-size: 16px;">বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি</div>
        <div style="font-size: 16px;">এন্ট্রি ফরম-২</div>
        <div style="font-size: 16px;">দলীয় খেলা</div>
    </div>
    
    <hr style="border: 1px solid black; margin-top: 10px; margin-bottom: 20px;">
    
    <div>
        <p>তারিখঃ ............................</p>
        <p>
            প্রতি<br>
            অবৈতনিক সম্পাদক<br>
            {{ $seasonEvent->event->name }} {{ $seasonEvent->subEvent ? '(' . $seasonEvent->subEvent->name . ')' : '' }} প্রতিযোগিতা<br>
            বাংলাদেশ জাতীয় স্কুল, মাদ্রাসা ও কারিগরি শিক্ষা ক্রীড়া সমিতি<br>
            উপজেলাঃ {{ $school->thana?->bn_name ?? ($school->thana?->name ?? ($school->upazila ?? '................')) }}, জেলাঃ {{ $school->district?->bn_name ?? ($school->district?->name ?? ($school->district ?? '................')) }}।
        </p>

        <p>জনাব,<br>
        অনুগ্রহপূর্বক আমাদের দলকে খেলায় অংশগ্রহণের জন্য এন্ট্রি করিবেন এর জন্য ২,০০০/- (কথায়ঃ দুই হাজার টাকা) টাকা জমা দেওয়া হইল।</p>

        <p>
            বিদ্যালয়ের নামঃ {{ $school->name_bn ?? $school->name_en }}<br>
            উপজেলাঃ {{ $school->thana?->bn_name ?? ($school->thana?->name ?? ($school->upazila ?? '................')) }}, জেলাঃ {{ $school->district?->bn_name ?? ($school->district?->name ?? ($school->district ?? '................')) }}।
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">ক্রমিক</th>
                <th width="50%">খেলোয়াড়ের নাম</th>
                <th width="40%">ছাত্র/ছাত্রীর স্বাক্ষর</th>
            </tr>
        </thead>
        <tbody>
            @foreach($seasonEvent->players as $index => $player)
            <tr>
                <td>{{ en2bn($index + 1) }}</td>
                <td style="text-align: left; padding-left: 10px;">{{ $player->student->student_name_bn ?? $player->student->student_name_en }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: justify;">
        আমি এই মর্মে প্রত্যয়ন করিতেছি যে, উল্লিখিত প্রতিযোগীগণ আমার বিদ্যালয়ের নিয়মিত ছাত্র/ছাত্রী। তাহারা আমার সম্মুখে স্বাক্ষর করিয়াছে এবং তাহাদের কেহই একই শ্রেণিতে এক বছরের অধিক অধ্যয়ন করে নাই কিংবা নীচের শ্রেণিতে অবনমিত হয় নাই।
    </div>

    <div class="signature-area">
        <div class="text-center">
            <p style="margin-bottom: 5px;">প্রধান শিক্ষক</p>
            <p style="margin: 0;">{{ $school->name_bn ?? $school->name_en }}</p>
            <p style="margin: 0;">উপজেলাঃ {{ $school->thana?->bn_name ?? ($school->thana?->name ?? ($school->upazila ?? '................')) }}, জেলাঃ {{ $school->district?->bn_name ?? ($school->district?->name ?? ($school->district ?? '................')) }}</p>
        </div>
    </div>
</body>
</html>
