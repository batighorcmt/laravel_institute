<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>প্রিন্ট আইডি কার্ড</title>
    <style>
        @font-face {
            font-family: 'Nikosh';
            src: url('{{ public_path("fonts/Nikosh.ttf") }}') format('truetype');
        }
        body {
            font-family: 'Nikosh', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        * {
            box-sizing: border-box;
        }
        .print-container {
            display: flex;
            flex-wrap: wrap;
            padding: 10mm;
            justify-content: flex-start;
        }
        .id-card {
            border: 0.1mm solid #000; /* কাটার দাগ */
            margin: 5mm 2mm; /* Increased vertical margin */
            border-radius: 3mm; /* কোণা রাউন্ড করার জন্য (Die-cut look) */
            position: relative;
            overflow: hidden;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #fff;
            page-break-inside: avoid;
        }
        .content-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            height: 100%;
        }
        .student-photo {
            border: 0.4mm solid #888; /* প্রিমিয়াম ডার্ক গ্রে বর্ডার */
            padding: 0.5mm; /* ফ্রেম এফেক্ট */
            background-color: #fff;
            margin-bottom: 2mm;
            object-fit: cover;
            display: block;
            border-radius: 1.5mm; /* ছবির কোণাও সামান্য রাউন্ড করা হলো */
        }
        .student-name {
            font-weight: bold;
            text-align: center;
            width: 100%;
            margin-bottom: 2mm;
            display: block;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 0.2mm 0;
        }
        .label {
            white-space: nowrap;
        }
        .value {
            padding-left: 2mm;
        }
        @media print {
            body { background: none; margin: 0; }
            .print-container { padding: 0; }
            .no-print { display: none; }
            .id-card { margin: 5mm 2mm; } /* Keep spacing between cards on page */
        }
    </style>
</head>
<body>
    @php
        $settingsArr = is_string($settings) ? json_decode($settings, true) : json_decode(json_encode($settings), true);
        $settings = is_array($settingsArr) ? $settingsArr : [];
        $fields = $settings['fields'] ?? ['class', 'roll', 'section'];
        $lang = $settings['language'] ?? 'bn';
        
        // Helper to get labels
        $labels = [
            'class' => $lang === 'en' ? 'Class' : 'শ্রেণি',
            'section' => $lang === 'en' ? 'Section' : 'শাখা',
            'group' => $lang === 'en' ? 'Group' : 'গ্রুপ',
            'roll' => $lang === 'en' ? 'Roll No.' : 'রোল',
            'reg_no' => $lang === 'en' ? 'Reg. No.' : 'রেজি নং',
            'session' => $lang === 'en' ? 'Session' : 'সেশন',
            'dob' => $lang === 'en' ? 'DOB' : 'জন্ম তারিখ',
            'blood_group' => $lang === 'en' ? 'Blood Group' : 'রক্তের গ্রুপ',
            'father_name' => $lang === 'en' ? 'Father' : 'পিতার নাম',
            'mother_name' => $lang === 'en' ? 'Mother' : 'মাতার নাম',
            'mobile' => $lang === 'en' ? 'Mobile' : 'মোবাইল',
            'student_id' => $lang === 'en' ? 'ID No.' : 'আইডি নং',
        ];

        // Helper for Bengali Numbers
        $enToBn = function($str) use ($lang) {
            if ($lang !== 'bn') return $str;
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($en, $bn, $str);
        };

        // Helper for Title Case (Capitalize Each Word)
        $titleCase = function($str) use ($lang) {
            if ($lang !== 'en' || !$str) return $str;
            return \Illuminate\Support\Str::title($str);
        };
    @endphp

    <div class="print-container">
        @foreach($students as $student)
            @php
                $enrollment = $student->enrollments->first();
                $data = [
                    'class' => $lang === 'bn' ? $enToBn($enrollment->class->bangla_name ?: $enrollment->class->name) : ($enrollment->class->name ?? '-'),
                    'section' => $lang === 'bn' ? $enToBn($enrollment->section->bangla_name ?: $enrollment->section->name) : ($enrollment->section->name ?? '-'),
                    'group' => $enrollment->group->name ?? '-',
                    'roll' => $enToBn($enrollment->roll_no ?? '-'),
                    'reg_no' => $enToBn($student->reg_no ?? '-'),
                    'session' => $enToBn($enrollment->academic_year->name ?? '-'),
                    'dob' => $enToBn($student->dob ?? '-'),
                    'blood_group' => $student->blood_group ?? '-',
                    'father_name' => $lang === 'en' ? $titleCase($student->father_name) : $student->father_name_bn,
                    'mother_name' => $lang === 'en' ? $titleCase($student->mother_name) : $student->mother_name_bn,
                    'mobile' => $enToBn($student->mobile ?? '-'),
                    'student_id' => $student->student_id ?? '-',
                ];

                $studentName = $lang === 'en' 
                    ? $titleCase($student->student_name_en ?: $student->student_name_bn)
                    : ($student->student_name_bn ?: $student->student_name_en);
            @endphp

            <div class="id-card" style="
                width: {{ $settings['card_width'] }}mm; 
                height: {{ $settings['card_height'] }}mm;
                padding-top: {{ $settings['content_padding_top'] }}mm;
                padding-left: {{ $settings['margin_left'] }}mm;
                padding-right: {{ $settings['margin_right'] }}mm;
                padding-bottom: {{ $settings['margin_bottom'] }}mm;
                background-image: {{ $settings['background_image'] ? 'url('.$settings['background_image'].')' : 'none' }};
            ">
                <div class="content-area">
                    <!-- ফটো -->
                    <img src="{{ $student->photo_url }}" 
                         class="student-photo"
                         style="width: {{ $settings['photo_width'] }}mm; height: {{ $settings['photo_height'] }}mm;">

                    <!-- নাম -->
                    <div class="student-name" style="font-size: {{ $settings['name_font_size'] }}pt; color: {{ $settings['name_color'] }};">
                        {{ $studentName }}
                    </div>

                    <!-- তথ্য তালিকা -->
                    <table class="info-table" style="
                        font-size: {{ $settings['details_font_size'] }}pt; 
                        color: {{ $settings['details_color'] }};
                        line-height: {{ $settings['row_spacing'] }};
                    ">
                        @foreach($fields as $f)
                            @if(isset($labels[$f]))
                                <tr style="{{ $f === 'student_id' ? 'font-size: '.$settings['id_no_font_size'].'pt; color: '.$settings['id_no_color'].'; font-weight: bold;' : '' }}">
                                    <td class="label" style="width: 35%;">{{ $labels[$f] }}:</td>
                                    <td class="value">{{ $data[$f] ?? '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>