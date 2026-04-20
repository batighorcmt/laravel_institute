<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="utf-8">
    <title>অভিভাবকের অনুমতিপত্র - {{ $student->student_name_bn }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        @font-face {
            font-family: 'BengaliNumbers';
            src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2');
            unicode-range: U+09E6-09EF;
            /* Bengali digits ০-৯ */
        }

        body {
            font-family: 'BengaliNumbers', 'Hind Siliguri', sans-serif;
            font-size: 16px;
            color: #000;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm;
            margin: 0 auto;
            position: relative;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            text-decoration: underline;
            display: inline-block;
        }

        .date-line {
            text-align: right;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .form-row {
            margin-bottom: 12px;
            display: flex;
            align-items: baseline;
        }

        .dotted-line {
            flex-grow: 1;
            border-bottom: 1px dotted #333;
            margin-left: 5px;
            padding-left: 5px;
            font-weight: 600;
        }

        .content-body {
            text-align: justify;
            margin: 30px 0;
            line-height: 1.8;
            font-size: 17px;
        }

        .signature-section {
            margin-top: 60px;
        }

        .sig-row {
            margin-bottom: 10px;
        }

        .footer-note {
            margin-top: 40px;
            font-size: 14px;
            line-height: 1.5;
            text-align: justify;
            font-style: italic;
        }

        @media print {
            body {
                margin: 0;
            }

            .page {
                margin: 0;
                border: none;
                width: 100%;
                height: 100%;
            }

            .no-print {
                display: none;
            }
        }

        .print-btn-strip {
            background: #343a40;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
        }

        .btn-print {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        @media screen {
            body {
                background: #e0e0e0;
                padding-top: 60px;
            }

            .page {
                background: #fff;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
                margin-bottom: 30px;
            }
        }
    </style>
</head>

<body>

    @php
        function bn_num($number)
        {
            $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
            $bn = array('০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯');
            return str_replace($en, $bn, $number);
        }

        $relationEn = strtolower($student->guardian_relation);
        $relations = [
            'father' => 'পিতা',
            'mother' => 'মাতা',
            'brother' => 'ভাই',
            'sister' => 'বোন',
            'uncle' => 'চাচা/মামু',
            'aunt' => 'ফুফু/খালা',
            'grandfather' => 'দাদা/নানা',
            'grandmother' => 'দাদি/নানি',
            'legal guardian' => 'আইনানুগ অভিভাবক',
            'other' => 'অন্যান্য'
        ];
        $relation = $relations[$relationEn] ?? $student->guardian_relation;
    @endphp

    <div class="print-btn-strip no-print">
        <button class="btn-print" onclick="window.print()">প্রিন্ট করুন</button>
    </div>

    <div class="page">
        <div class="header">
            <div class="title">অভিভাবকের অনুমতিপত্র</div>
        </div>

        <div class="date-line">
            তারিখঃ <span style="border-bottom: 1px dotted #000; padding: 0 20px;">{{ bn_num(date('d/m/Y')) }}</span>
        </div>

        <div class="form-row">
            <span>এই মর্মে অনুমতি প্রদান করা যাচ্ছে যে, আমার পোষ্য</span>
            <div class="dotted-line">{{ $student->student_name_bn ?: $student->student_name_en }}</div>
        </div>

        <div class="form-row">
            <span>পিতার নামঃ</span>
            <div class="dotted-line">{{ $student->father_name_bn ?: $student->father_name }}</div>
        </div>

        <div class="form-row">
            <span>মাতার নামঃ</span>
            <div class="dotted-line">{{ $student->mother_name_bn ?: $student->mother_name }}</div>
        </div>

        <div class="form-row" style="gap: 15px;">
            <span style="white-space: nowrap;">স্টুডেন্ট আইডিঃ</span>
            <div class="dotted-line" style="flex-grow: 1;">{{ $student->student_id }}</div>
            <span style="white-space: nowrap;">জন্ম তারিখঃ</span>
            <div class="dotted-line" style="flex-grow: 1;">
                {{ $student->date_of_birth ? bn_num($student->date_of_birth->format('d/m/Y')) : '........' }}
            </div>
        </div>

        <div class="form-row" style="gap: 15px;">
            <span style="white-space: nowrap;">শ্রেণিঃ</span>
            <div class="dotted-line" style="flex-grow: 1;">
                {{ $enrollment?->class?->bangla_name ?: ($enrollment?->class?->name ?? '........') }}
            </div>
            <span style="white-space: nowrap;">শাখাঃ</span>
            <div class="dotted-line" style="flex-grow: 1;">
                {{ $enrollment?->section?->bangla_name ?: ($enrollment?->section?->name ?? '........') }}
            </div>
            <span style="white-space: nowrap;">রোল নংঃ</span>
            <div class="dotted-line" style="flex-grow: 1;">{{ $enrollment ? bn_num($enrollment->roll_no) : '........' }}
            </div>
        </div>

        <div class="content-body" style="text-indent: 0;">
            <span style="font-weight: bold;">{{ $school->name_bn ?: $school->name }}</span>-এ <span
                style="font-weight: bold;">{{ $game_name }}</span> খেলায় অংশগ্রহণ এবং প্রতিযোগিতার প্রয়োজনে সে জেলা,
            উপ-অঞ্চল, অঞ্চল ও জাতীয় পর্যায়ে অংশ গ্রহণ করিতে পারিবে আমি অভিভাবক হিসেবে সম্মতি জ্ঞাপন ও অনুমতি প্রদান
            করছি।
        </div>

        <div class="signature-section">
            <div class="sig-row">
                অভিভাবকের স্বাক্ষরঃ .......................................
            </div>
            <div class="sig-row">
                অভিভাবকের নামঃ <span
                    style="border-bottom: 1px dotted #000; min-width: 200px; display: inline-block; padding: 0 10px;">{{ $student->guardian_name_bn ?: $student->father_name_bn ?: '........................' }}</span>
                (বাবা/মা না থাকলে আইনানুগ অভিভাবক)
            </div>
            <div class="sig-row">
                <span>শিক্ষার্থীর সাথে সম্পর্কঃ</span>
                <span
                    style="border-bottom: 1px dotted #000; min-width: 150px; display: inline-block; padding: 0 10px;">{{ $relation ?: '................' }}</span>
                <span style="margin-left: 20px;">মোবাইল নংঃ</span>
                <span
                    style="border-bottom: 1px dotted #000; min-width: 200px; display: inline-block; padding: 0 10px;">{{ bn_num($student->guardian_phone) }}</span>
            </div>
        </div>

    </div>

</body>

</html>