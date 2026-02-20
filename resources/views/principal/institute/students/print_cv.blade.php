<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শিক্ষার্থী প্রোফাইল - {{ $student->student_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 15px; /* Increased from 13px/14px */
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header-container {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .school-logo {
            width: 85px;
            height: 85px;
            margin-right: 15px;
            object-fit: contain;
        }
        .header-content {
            flex-grow: 1;
            text-align: center;
        }
        .header-content h1 {
            margin: 0;
            font-size: 24px; /* Equal font size */
            color: #1a56db;
            line-height: 1.2;
        }
        .header-content p {
            margin: 2px 0;
            font-size: 14px;
        }
        .cv-title {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 8px;
            background: #f0f4ff;
            padding: 3px;
        }
        .profile-row {
            display: flex;
            margin-bottom: 8px;
        }
        .profile-photo {
            width: 110px;
            height: 120px;
            border: 2px solid #1a56db;
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
            margin-bottom: 5px;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 4px 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            width: 35%;
            font-weight: bold;
            color: #333;
            background: #f9f9f9;
        }
        .section-title {
            background: #1a56db;
            color: #white;
            padding: 3px 12px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            font-size: 15px;
            color: #fff;
        }
        .grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            border-top: 1px solid #333;
            width: 180px;
            text-align: center;
            padding-top: 3px;
            font-size: 14px;
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
            color: #444;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Institute Header -->
        <div class="header-container">
            @if($school->logo)
                <img src="{{ asset('storage/'.$school->logo) }}" alt="Logo" class="school-logo">
            @else
                <div class="school-logo" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0; border: 1px solid #ddd;">Logo</div>
            @endif
            <div class="header-content">
                <h1>{{ $school->name_bn }}</h1>
                <h1>{{ $school->name }}</h1>
                <p>{{ $school->address_bn ?: $school->address }}</p>
                <p>Phone: {{ $school->phone }} | Email: {{ $school->email }}</p>
            </div>
            <div style="width: 85px;"></div> {{-- Spacer to balance logo --}}
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
                        <td><strong>{{ $activeEnrollment->roll_no }}</strong></td>
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
                    <td>{{ $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '—' }}</td>
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
                    <td><strong>{{ $student->guardian_phone ?: '—' }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="section-title">ঠিকানা (Address)</div>
        <div class="grid-row">
            <div style="font-size: 14px;">
                <p><strong>বর্তমান ঠিকানা (Present Address):</strong></p>
                <p style="margin-left: 10px;">
                    {{ $student->present_village ? 'গ্রাম: '.$student->present_village.', ' : '' }}
                    {{ $student->present_para_moholla ? 'পাড়া: '.$student->present_para_moholla.', ' : '' }}
                    {{ $student->present_post_office ? 'ডাকঘর: '.$student->present_post_office : '' }}<br>
                    {{ $student->present_upazilla ? 'উপজেলা: '.$student->present_upazilla.', ' : '' }}
                    {{ $student->present_district ? 'জেলা: '.$student->present_district : '' }}
                </p>
            </div>
            <div style="font-size: 14px;">
                <p><strong>স্থায়ী ঠিকানা (Permanent Address):</strong></p>
                <p style="margin-left: 10px;">
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
            <button onclick="window.print()" style="padding: 10px 20px; background: #1a56db; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Print Profile</button>
        </div>
    </div>
</body>
</html>
