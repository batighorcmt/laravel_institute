<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শিক্ষার্থী প্রোফাইল - {{ $student->student_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 13px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #1a56db;
        }
        .header p {
            margin: 2px 0;
            font-size: 12px;
        }
        .cv-title {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        .profile-row {
            display: flex;
            margin-bottom: 10px;
        }
        .profile-photo {
            width: 100px;
            height: 110px;
            border: 1px solid #ddd;
            padding: 2px;
            margin-right: 20px;
        }
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-basic-info {
            flex-grow: 1;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
        }
        .info-table th {
            width: 35%;
            font-weight: bold;
            color: #555;
            background: #fdfdfd;
        }
        .section-title {
            background: #f5f5f5;
            padding: 4px 12px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            border-left: 3px solid #1a56db;
            font-size: 13px;
        }
        .grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            border-top: 1px solid #333;
            width: 160px;
            text-align: center;
            padding-top: 3px;
            font-size: 12px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                -webkit-print-color-adjust: exact;
                margin: 0;
            }
            .container {
                width: 100%;
            }
        }
        .name-pair {
            display: block;
        }
        .en-name {
            font-style: italic;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Institute Header -->
        <div class="header">
            <h1>{{ $school->name_bn ?: $school->name }}</h1>
            @if($school->name_bn && $school->name)
                <p><strong>{{ $school->name }}</strong></p>
            @endif
            <p>{{ $school->address_bn ?: $school->address }}</p>
            <p>Phone: {{ $school->phone }} | Email: {{ $school->email }}</p>
        </div>

        <div class="cv-title">Student Profile (CV)</div>

        <div class="profile-row">
            <div class="profile-photo">
                <img src="{{ $student->photo_url }}" alt="Student Photo">
            </div>
            <div class="student-basic-info">
                <table class="info-table">
                    <tr>
                        <th>শিক্ষার্থীর নাম</th>
                        <td>
                            <span class="name-pair"><strong>{{ $student->student_name_bn }}</strong></span>
                            @if($student->student_name_en) <span class="en-name">{{ $student->student_name_en }}</span> @endif
                        </td>
                    </tr>
                    <tr>
                        <th>শিক্ষার্থী আইডি (ID)</th>
                        <td><strong>{{ $student->student_id }}</strong></td>
                    </tr>
                    @if($activeEnrollment)
                    <tr>
                        <th>শ্রেণি ও শাখা (Class/Sec)</th>
                        <td>{{ $activeEnrollment->class?->name }} ({{ $activeEnrollment->section?->name }})</td>
                    </tr>
                    <tr>
                        <th>রোল নম্বর (Roll No)</th>
                        <td>{{ $activeEnrollment->roll_no }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="section-title">পিতা ও মাতার তথ্য (Parental Information)</div>
        <div class="grid-row">
            <table class="info-table">
                <tr>
                    <th>পিতার নাম (Father)</th>
                    <td>
                        <span class="name-pair">{{ $student->father_name_bn }}</span>
                        @if($student->father_name) <span class="en-name">{{ $student->father_name }}</span> @endif
                    </td>
                </tr>
                <tr>
                    <th>মাতার নাম (Mother)</th>
                    <td>
                        <span class="name-pair">{{ $student->mother_name_bn }}</span>
                        @if($student->mother_name) <span class="en-name">{{ $student->mother_name }}</span> @endif
                    </td>
                </tr>
            </table>
            <table class="info-table">
                <tr>
                    <th>জন্ম তারিখ (DOB)</th>
                    <td>{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '—' }}</td>
                </tr>
                <tr>
                    <th>লিঙ্গ (Gender)</th>
                    <td>{{ $student->gender == 'male' ? 'ছেলে' : 'মেয়ে' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">ব্যক্তিগত ও অভিভাবকের তথ্য (Personal & Guardian)</div>
        <div class="grid-row">
            <table class="info-table">
                <tr>
                    <th>ধর্ম (Religion)</th>
                    <td>{{ $student->religion ?: '—' }}</td>
                </tr>
                <tr>
                    <th>রক্ত (Blood Group)</th>
                    <td>{{ $student->blood_group ?: '—' }}</td>
                </tr>
            </table>
            <table class="info-table">
                <tr>
                    <th>অভিভাবক (Guardian)</th>
                    <td>
                        <span class="name-pair">{{ $student->guardian_name_bn ?: '—' }}</span>
                        @if($student->guardian_name_en) <span class="en-name">{{ $student->guardian_name_en }}</span> @endif
                        <small>({{ $student->guardian_relation }})</small>
                    </td>
                </tr>
                <tr>
                    <th>ফোন নম্বর (Phone)</th>
                    <td>{{ $student->guardian_phone ?: '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">ঠিকানা (Address)</div>
        <div class="grid-row">
            <div style="font-size: 12px;">
                <p><strong>বর্তমান ঠিকানা (Present Address):</strong></p>
                <p>
                    {{ $student->present_village ? 'গ্রাম: '.$student->present_village.', ' : '' }}
                    {{ $student->present_para_moholla ? 'পাড়া: '.$student->present_para_moholla.', ' : '' }}
                    {{ $student->present_post_office ? 'ডাকঘর: '.$student->present_post_office : '' }}<br>
                    {{ $student->present_upazilla ? 'উপজেলা: '.$student->present_upazilla.', ' : '' }}
                    {{ $student->present_district ? 'জেলা: '.$student->present_district : '' }}
                </p>
            </div>
            <div style="font-size: 12px;">
                <p><strong>স্থায়ী ঠিকানা (Permanent Address):</strong></p>
                <p>
                    {{ $student->permanent_village ? 'গ্রাম: '.$student->permanent_village.', ' : '' }}
                    {{ $student->permanent_para_moholla ? 'পাড়া: '.$student->permanent_para_moholla.', ' : '' }}
                    {{ $student->permanent_post_office ? 'ডাকঘর: '.$student->permanent_post_office : '' }}<br>
                    {{ $student->permanent_upazilla ? 'উপজেলা: '.$student->permanent_upazilla.', ' : '' }}
                    {{ $student->permanent_district ? 'জেলা: '.$student->permanent_district : '' }}
                </p>
            </div>
        </div>

        @if($student->previous_school)
        <div class="section-title">পূর্ববর্তী বিদ্যালয় (Previous School)</div>
        <table class="info-table">
            <tr>
                <th style="width: 20%">বিদ্যালয়</th>
                <td>{{ $student->previous_school }}</td>
                <th style="width: 20%">ফলাফল</th>
                <td>{{ $student->pass_year ?: '—' }} ({{ $student->previous_result ?: '—' }})</td>
            </tr>
        </table>
        @endif

        <div class="footer">
            <div class="signature">অফিস সহকারী</div>
            <div class="signature">অধ্যক্ষ/প্রধান শিক্ষক</div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #1a56db; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Print Profile</button>
        </div>
    </div>
</body>
</html>
