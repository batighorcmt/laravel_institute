<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->name }} - রেজাল্ট শীট ({{ $class->name }})</title>
    <style>
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            color: #000;
        }
        @page {
            size: A4 portrait;
            margin: 15mm 10mm;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .school-address {
            font-size: 14px;
            margin: 0 0 10px 0;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            text-decoration: underline;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 200px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover {
            background-color: #0056b3;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">
        প্রিন্ট করুন
    </button>

    <div class="header">
        <h1 class="school-name">{{ $school->name }}</h1>
        <p class="school-address">{{ $school->address }}</p>
        <div class="report-title">রেজাল্ট শীট</div>
    </div>

    <div class="info-row">
        <div>
            <strong>পরীক্ষার নাম:</strong> {{ $exam->name }}<br>
            <strong>শ্রেণি:</strong> {{ $class->name }}
        </div>
        <div>
            <strong>শিক্ষাবর্ষ:</strong> {{ $exam->academicYear->name ?? 'N/A' }}<br>
            <strong>প্রিন্টের তারিখ:</strong> {{ now()->format('d/m/Y h:i A') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">ক্রমিক নং</th>
                <th width="12%">শিক্ষার্থী আইডি নং</th>
                <th width="10%">বর্তমান রোল নং</th>
                <th class="text-left">শিক্ষার্থীর নাম</th>
                <th width="12%">মোট প্রাপ্ত নাম্বার</th>
                <th width="10%">জিপিএ</th>
                <th width="15%">ফলাফল অবস্থা</th>
                <th width="10%">বর্তমান অবস্থান</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $result)
                @php
                    $student = clone $result->student;
                    $enrollment = $student->currentEnrollment;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $student->student_id }}</td>
                    <td>{{ $enrollment->roll_no ?? 'N/A' }}</td>
                    <td class="text-left">{{ $student->name_en }} ({{ $student->name_bn }})</td>
                    <td>{{ number_format($result->computed_total_marks, 0) }}</td>
                    <td>{{ number_format($result->computed_gpa, 2) }}</td>
                    <td>
                        @if($result->computed_status == 'অকৃতকার্য' || $result->computed_letter == 'F')
                            <span style="color: red; font-weight: bold;">
                                অকৃতকার্য ({{ $result->fail_count ?: '?' }})
                            </span>
                        @else
                            <span style="color: green; font-weight: bold;">
                                উত্তীর্ণ
                            </span>
                        @endif
                    </td>
                    <td>{{ $result->computed_status == 'উত্তীর্ণ' ? $loop->iteration : '-' }}</td>
                </tr>
            @endforeach
            
            @if($results->isEmpty())
                <tr>
                    <td colspan="8" style="padding: 20px;">এই পরীক্ষার কোনো ফলাফল পাওয়া যায়নি।</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            শ্রেণি শিক্ষকের স্বাক্ষর
        </div>
        <div class="signature-box">
            @if(isset($principalTeacher))
                {{ $principalTeacher->name }}<br>
                অধ্যক্ষ/প্রধান শিক্ষক
            @else
                অধ্যক্ষ/প্রধান শিক্ষকের স্বাক্ষর
            @endif
        </div>
    </div>

</body>
</html>
