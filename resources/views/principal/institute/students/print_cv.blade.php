<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শিক্ষার্থী প্রোফাইল - {{ $student->student_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a56db;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .cv-title {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 20px;
            text-decoration: underline;
        }
        .profile-row {
            display: flex;
            margin-bottom: 20px;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            padding: 2px;
            margin-right: 30px;
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
            margin-bottom: 20px;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .info-table th {
            width: 35%;
            font-weight: bold;
            color: #555;
            background: #f9f9f9;
        }
        .section-title {
            background: #f0f0f0;
            padding: 8px 15px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-left: 4px solid #1a56db;
        }
        .grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            border-top: 1px solid #333;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            font-size: 14px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Institute Header -->
        <div class="header">
            @if($school->logo)
                {{-- Assuming logo path is handled correctly in the system --}}
            @endif
            <h1>{{ $school->name_bn ?: $school->name }}</h1>
            @if($school->name_bn && $school->name)
                <p><strong>{{ $school->name }}</strong></p>
            @endif
            <p>{{ $school->address_bn ?: $school->address }}</p>
            <p>Phone: {{ $school->phone }}, Email: {{ $school->email }}</p>
        </div>

        <div class="cv-title">Student Profile (CV)</div>

        <div class="profile-row">
            <div class="profile-photo">
                <img src="{{ $student->photo_url }}" alt="Student Photo">
            </div>
            <div class="student-basic-info">
                <table class="info-table">
                    <tr>
                        <th>শিক্ষার্থীর নাম (বাংলা)</th>
                        <td>{{ $student->student_name_bn ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Student Name (English)</th>
                        <td>{{ $student->student_name_en ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>শিক্ষার্থী আইডি (Student ID)</th>
                        <td><strong>{{ $student->student_id }}</strong></td>
                    </tr>
                    @if($activeEnrollment)
                    <tr>
                        <th>বর্তমান শ্রেণি (Current Class)</th>
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

        <div class="section-title">ব্যক্তিগত তথ্য (Personal Information)</div>
        <div class="grid-row">
            <table class="info-table">
                <tr>
                    <th>পিতার নাম (Father's Name)</th>
                    <td>{{ $student->father_name_bn ?: $student->father_name }}</td>
                </tr>
                <tr>
                    <th>মাতার নাম (Mother's Name)</th>
                    <td>{{ $student->mother_name_bn ?: $student->mother_name }}</td>
                </tr>
                <tr>
                    <th>জন্ম তারিখ (DOB)</th>
                    <td>{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '—' }}</td>
                </tr>
                <tr>
                    <th>লিঙ্গ (Gender)</th>
                    <td>{{ $student->gender == 'male' ? 'ছেলে (Male)' : 'মেয়ে (Female)' }}</td>
                </tr>
            </table>
            <table class="info-table">
                <tr>
                    <th>ধর্ম (Religion)</th>
                    <td>{{ $student->religion ?: '—' }}</td>
                </tr>
                <tr>
                    <th>রক্তের গ্রুপ (Blood Group)</th>
                    <td>{{ $student->blood_group ?: '—' }}</td>
                </tr>
                <tr>
                    <th>ভর্তির তারিখ (Admission Date)</th>
                    <td>{{ $student->admission_date ? $student->admission_date->format('d/m/Y') : '—' }}</td>
                </tr>
                <tr>
                    <th>অবস্থা (Status)</th>
                    <td>{{ ucfirst($student->status) }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">অভিভাবকের তথ্য (Guardian Information)</div>
        <table class="info-table">
            <tr>
                <th style="width: 25%">অভিভাবকের নাম</th>
                <td>{{ $student->guardian_name_bn ?: ($student->guardian_name_en ?: '—') }}</td>
                <th style="width: 25%">সম্পর্ক (Relation)</th>
                <td>{{ $student->guardian_relation ?: '—' }}</td>
            </tr>
            <tr>
                <th>ফোন নম্বর (Phone)</th>
                <td colspan="3">{{ $student->guardian_phone ?: '—' }}</td>
            </tr>
        </table>

        <div class="section-title">ঠিকানা (Address)</div>
        <div class="grid-row">
            <div>
                <p><strong>বর্তমান ঠিকানা (Present Address):</strong></p>
                <p>
                    {{ $student->present_village ? 'গ্রাম: '.$student->present_village.', ' : '' }}
                    {{ $student->present_para_moholla ? 'পাড়া/মহল্লা: '.$student->present_para_moholla.', ' : '' }}
                    {{ $student->present_post_office ? 'ডাকঘর: '.$student->present_post_office.', ' : '' }}<br>
                    {{ $student->present_upazilla ? 'উপজেলা: '.$student->present_upazilla.', ' : '' }}
                    {{ $student->present_district ? 'জেলা: '.$student->present_district : '' }}
                </p>
            </div>
            <div>
                <p><strong>স্থায়ী ঠিকানা (Permanent Address):</strong></p>
                <p>
                    {{ $student->permanent_village ? 'গ্রাম: '.$student->permanent_village.', ' : '' }}
                    {{ $student->permanent_para_moholla ? 'পাড়া/মহল্লা: '.$student->permanent_para_moholla.', ' : '' }}
                    {{ $student->permanent_post_office ? 'ডাকঘর: '.$student->permanent_post_office.', ' : '' }}<br>
                    {{ $student->permanent_upazilla ? 'উপজেলা: '.$student->permanent_upazilla.', ' : '' }}
                    {{ $student->permanent_district ? 'জেলা: '.$student->permanent_district : '' }}
                </p>
            </div>
        </div>

        @if($student->previous_school)
        <div class="section-title">পূর্ববর্তী বিদ্যালয়ের তথ্য (Previous School Info)</div>
        <table class="info-table">
            <tr>
                <th>বিদ্যালয়ের নাম</th>
                <td>{{ $student->previous_school }}</td>
            </tr>
            <tr>
                <th>পাশের বছর ও ফলাফল</th>
                <td>{{ $student->pass_year ?: '—' }} ({{ $student->previous_result ?: '—' }})</td>
            </tr>
        </table>
        @endif

        <div class="footer">
            <div class="signature">অফিস সহকারী</div>
            <div class="signature">প্রধান শিক্ষক</div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #1a56db; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Print This Page</button>
        </div>
    </div>
</body>
</html>
