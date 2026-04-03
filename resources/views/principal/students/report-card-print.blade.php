<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রিপোর্ট কার্ড - {{ $student->student_name_bn }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --secondary: #6b7280;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Hind Siliguri', sans-serif;
            background: #fff;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 11pt;
        }

        .print-container {
            width: 210mm; /* A4 width */
            margin: auto;
            padding: 15mm;
            background: #fff;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
            padding-bottom: 20px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 1px solid #ddd;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-info h1 {
            font-size: 20pt;
            margin: 0;
            color: #111827;
        }

        .student-info h3 {
            font-size: 11pt;
            margin: 2px 0 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 10pt;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f9fafb;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .summary-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-box {
            flex: 1;
            border: 1px solid #e5e7eb;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .box-label { font-size: 9pt; color: #6b7280; margin-bottom: 2px; }
        .box-value { font-size: 14pt; font-weight: bold; color: #111827; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            text-align: left;
            font-size: 9.5pt;
        }

        table thead th {
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
        }

        .exam-item {
            display: block;
            border: 1px solid #e5e7eb;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            color: inherit;
        }

        .date-range {
            font-size: 9pt;
            color: #6b7280;
            font-weight: normal;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f3f4f6; color: #374151; }

        @media print {
            body { padding: 0; }
            .print-container { width: 100%; padding: 0; margin: 0; }
            .no-print { display: none; }
            .exam-item { page-break-inside: avoid; }
            .section-title { page-break-after: avoid; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <!-- School Header -->
        <div style="display: flex; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 15px;">
            <div style="flex: 0 0 80px;">
                @if($school->logo)
                    <img src="{{ Storage::url($school->logo) }}" alt="Logo" style="width: 80px; height: 80px; object-fit: contain;">
                @else
                    <div style="width: 80px; height: 80px; background: #eee; border-radius: 5px;"></div>
                @endif
            </div>
            <div style="flex: 1; text-align: center;">
                <h1 style="margin: 0; font-size: 22pt; color: #111827; font-weight: bold;">{{ $school->name_bn ?? $school->name }}</h1>
                <div style="font-size: 11pt; color: #374151;">{{ $school->address }}</div>
                <div style="font-size: 14pt; font-weight: bold; margin-top: 5px; color: #4f46e5; background: #eef2ff; display: inline-block; padding: 4px 15px; border-radius: 20px;">রিপোর্ট কার্ড</div>
            </div>
            <div style="flex: 0 0 80px;"></div>
        </div>

        <!-- Student Info (Compact) -->
        <div style="display: flex; align-items: start; gap: 15px; margin-bottom: 20px; background: #f9fafb; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div class="avatar" style="width: 70px; height: 70px;">
                <img src="{{ $student->photo_url }}" alt="{{ $student->student_name_bn }}">
            </div>
            <div class="student-info" style="flex: 1;">
                <h1 style="font-size: 16pt; margin: 0 0 5px;">{{ $student->student_name_bn }}</h1>
                <div class="details" style="gap: 12px; row-gap: 5px;">
                    <div class="detail-item">আইডি: {{ toBengaliNumber($student->student_id) }}</div>
                    <div class="detail-item">শ্রেণি: {{ langField($student->currentEnrollment->class, 'name', 'bn') }}</div>
                    <div class="detail-item">শাখা: {{ langField($student->currentEnrollment->section, 'name', 'bn') }}</div>
                    <div class="detail-item">রোল: {{ toBengaliNumber($student->currentEnrollment->roll_no ?? 'N/A') }}</div>
                    @if($student->currentEnrollment->group)
                    <div class="detail-item">বিভাগ: {{ langField($student->currentEnrollment->group, 'name', 'bn') }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-calendar-check"></i> হাজিরা সারাংশ
            <span class="date-range ml-2">
                @if($startDate && $endDate)
                    ({{ toBengaliNumber($startDate->format('d/m/Y')) }} হতে {{ toBengaliNumber($endDate->format('d/m/Y')) }} পর্যন্ত)
                @else
                    (সকল তারিখের তথ্য)
                @endif
            </span>
        </div>
        <div class="summary-grid">
            <div class="summary-box">
                <div class="box-label">মোট কার্যদিবস</div>
                <div class="box-value">{{ toBengaliNumber($attendanceSummary['total_working_days']) }}</div>
            </div>
            <div class="summary-box">
                <div class="box-label">উপস্থিতি</div>
                <div class="box-value">{{ toBengaliNumber($attendanceSummary['present']) }}</div>
            </div>
            <div class="summary-box">
                <div class="box-label">অনুপস্থিতি</div>
                <div class="box-value">{{ toBengaliNumber($attendanceSummary['absent']) }}</div>
            </div>
            <div class="summary-box">
                <div class="box-label">উপস্থিতির হার</div>
                <div class="box-value">
                    {{ toBengaliNumber($attendanceSummary['total_working_days'] > 0 ? round(($attendanceSummary['present'] / $attendanceSummary['total_working_days']) * 100, 1) : 0) }}%
                </div>
            </div>
        </div>

        <div style="font-size: 11pt; font-weight: bold; margin: 15px 0 8px;">মাসিক হাজিরার বিস্তারিত পরিসংখ্যন</div>
        <table>
            <thead>
                <tr>
                    <th>মাস</th>
                    <th style="text-align: center;">মোট দিন</th>
                    <th style="text-align: center;">উপস্থিত</th>
                    <th style="text-align: center;">অনুপস্থিত</th>
                    <th style="text-align: center;">হার (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyAttendance as $month => $data)
                <tr>
                    <td><strong>{{ toBengaliMonth($month) }}</strong></td>
                    <td style="text-align: center;">{{ toBengaliNumber($data['total']) }}</td>
                    <td style="text-align: center;">{{ toBengaliNumber($data['present']) }}</td>
                    <td style="text-align: center;">{{ toBengaliNumber($data['absent']) }}</td>
                    <td style="text-align: center;">
                        {{ toBengaliNumber($data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0) }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">
            <i class="fas fa-chart-pie"></i> লেসন ইভেলুশন রিপোর্ট
            <span class="date-range ml-2">
                @if($startDate && $endDate)
                    ({{ $startDate->format('d/m/Y') }} হতে {{ $endDate->format('d/m/Y') }} পর্যন্ত)
                @else
                    (সকল তারিখের তথ্য)
                @endif
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>বিষয়</th>
                    <th style="text-align: center;">পড়া হয়েছে</th>
                    <th style="text-align: center;">আংশিক</th>
                    <th style="text-align: center;">হয়নি</th>
                    <th style="text-align: center;">অনুপস্থিত</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjectWiseEvaluation as $subject => $stats)
                <tr>
                    <td>{{ $subject }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($stats['completed']) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($stats['partial']) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($stats['not_done']) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($stats['absent']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">
            <i class="fas fa-file-invoice"></i> সম্পন্ন পরীক্ষা ও ফলাফল
        </div>
        @forelse($exams as $exam)
        @php 
            $examData = $examsData[$exam->id] ?? null;
            $studentResult = $examData['result'] ?? null;
            $finalSubjects = $examData['finalSubjects'] ?? collect();
            
            $hasCalculatedData = ($studentResult && $finalSubjects->isNotEmpty());

            $rawPossibleMarks = 0;
            if ($studentResult) {
                foreach($finalSubjects as $key => $fSub) {
                    $res = $studentResult->subject_results->get($key);
                    if ($res && empty($res['display_only'])) {
                        $rawPossibleMarks += ($fSub['total_full_mark'] ?? 0);
                    }
                }
            }

            // Quick Stats Logic
            $totalMarks = $studentResult ? $studentResult->computed_total_marks : ($exam->results->first() ? $exam->results->first()->total_marks : $exam->marks->sum('total_marks'));
            $possibleMarks = $studentResult ? $rawPossibleMarks : '--';
            $gpa = $studentResult ? $studentResult->computed_gpa : ($exam->results->first() ? $exam->results->first()->gpa : ($exam->marks->isNotEmpty() ? $exam->marks->avg('grade_point') : 0));
            $grade = $studentResult ? $studentResult->computed_letter : ($exam->results->first() ? $exam->results->first()->letter_grade : ($exam->marks->isNotEmpty() ? '...' : '--'));
            $statusStr = $studentResult ? ($studentResult->computed_letter == 'F' ? 'fail' : 'pass') : ($exam->results->first() ? ($exam->results->first()->result_status ?: ($exam->results->first()->letter_grade == 'F' ? 'fail' : 'pass')) : '');
            $classPosition = $studentResult ? $studentResult->class_position : ($exam->results->first() ? $exam->results->first()->merit_position : '?');
        @endphp
        <div class="exam-item" style="border: 2px solid #eee; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: start; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 10px;">
                <div>
                    <div style="font-weight: bold; font-size: 13pt; color: #111827;">{{ $exam->name_bn }}</div>
                    <div style="font-size: 9pt; color: #6b7280;">পরিক্ষার তারিখ: {{ $exam->start_date ? toBengaliNumber($exam->start_date->format('j M, Y')) : 'N/A' }}</div>
                </div>
                @if($statusStr)
                <div style="text-align: right;">
                    <span class="badge {{ $statusStr == 'pass' ? 'badge-success' : 'badge-danger' }}" style="font-size: 10pt; padding: 5px 15px; border: 1px solid #ddd;">
                        {{ $statusStr == 'pass' ? 'কৃতকার্য' : 'অকৃতকার্য' }}
                    </span>
                </div>
                @endif
            </div>
            
            @if($studentResult || $exam->marks->isNotEmpty())
            <table style="margin: 0; width: 100%; font-size: 10pt; background: #fff; border: 1px solid #eee;">
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 6px 10px; text-align: left; width: 40%;">বিষয়</th>
                    <th style="padding: 6px 10px; text-align: center;">প্রাপ্ত নম্বর</th>
                    <th style="padding: 6px 10px; text-align: center;">লেটার গ্রেড</th>
                    <th style="padding: 6px 10px; text-align: center;">জিপিএ (GPA)</th>
                </tr>
                @if($hasCalculatedData)
                    @php
                        $mainSubjects = collect();
                        $optionalSubject = null;
                        foreach($finalSubjects as $key => $fSub) {
                            $res = $studentResult->subject_results->get($key);
                            if (!$res) continue;
                            if (!empty($res['is_optional'])) { $optionalSubject = ['key' => $key, 'fSub' => $fSub, 'res' => $res]; }
                            else { $mainSubjects->push(['key' => $key, 'fSub' => $fSub, 'res' => $res]); }
                        }
                    @endphp

                    @foreach($mainSubjects as $item)
                        @php $fSub = $item['fSub']; $res = $item['res']; $isPart = !empty($res['display_only']); @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; {{ $isPart ? 'font-style: italic; background: #fafafa;' : '' }}">
                            <td style="padding: 6px 10px;">
                                @if($isPart) <span style="margin-left: 20px; color: #999;">↳</span> @endif
                                {{ $res['name'] ?? $fSub['name'] }}
                            </td>
                            <td style="padding: 6px 10px; text-align: center; font-weight: bold;">
                                {{ toBengaliNumber($res['total'] > 0 ? preg_replace('/\.00$/', '', number_format($res['total'], 2)) : '০') }}
                                @if(!$isPart) <small style="color: #999;">/ {{ toBengaliNumber($fSub['total_full_mark']) }}</small> @endif
                            </td>
                            <td style="padding: 6px 10px; text-align: center;">{{ !$isPart ? $res['grade'] : '-' }}</td>
                            <td style="padding: 6px 10px; text-align: center;">{{ !$isPart ? toBengaliNumber(preg_replace('/\.00$/', '', number_format($res['gpa'] ?? 0, 2))) : '-' }}</td>
                        </tr>
                    @endforeach

                    @if($optionalSubject)
                        @php $fSub = $optionalSubject['fSub']; $res = $optionalSubject['res']; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; background: #fefce8;">
                            <td style="padding: 6px 10px; font-weight: bold;">{{ $res['name'] ?? $fSub['name'] }} (ঐচ্ছিক)</td>
                            <td style="padding: 6px 10px; text-align: center; font-weight: bold;">
                                {{ toBengaliNumber($res['total'] > 0 ? preg_replace('/\.00$/', '', number_format($res['total'], 2)) : '০') }}
                                <small style="color: #999;">/ {{ toBengaliNumber($fSub['total_full_mark']) }}</small>
                            </td>
                            <td style="padding: 6px 10px; text-align: center;">{{ $res['grade'] }}</td>
                            <td style="padding: 6px 10px; text-align: center;">{{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($res['gpa'] ?? 0, 2))) }}</td>
                        </tr>
                    @endif
                @else
                    @foreach($exam->marks as $mark)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 6px 10px;">{{ $mark->subject->name_bn ?: $mark->subject->name }}</td>
                        <td style="padding: 6px 10px; text-align: center; font-weight: bold;">{{ number_format($mark->total_marks ?? 0, 2) }}</td>
                        <td style="padding: 6px 10px; text-align: center;">{{ $mark->letter_grade ?: 'N/R' }}</td>
                        <td style="padding: 6px 10px; text-align: center;">{{ number_format($mark->grade_point ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                @endif

                <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: bold;">
                    <td style="padding: 8px 10px;">পরীক্ষার ফলাফল সারাংশ</td>
                    <td style="padding: 8px 10px; text-align: center;">{{ toBengaliNumber($totalMarks) }} / {{ toBengaliNumber($possibleMarks) }}</td>
                    <td style="padding: 8px 10px; text-align: center;">GPA: {{ toBengaliNumber(preg_replace('/\.00$/', '', number_format($gpa, 2))) }} ({{ $grade }})</td>
                    <td style="padding: 8px 10px; text-align: center;">অবস্থান: {{ toBengaliNumber($classPosition) }}তম</td>
                </tr>
            </table>
            @else
            <div style="padding: 10px; text-align: center; color: #6b7280; font-style: italic;">ফলাফল প্রক্রিয়াকরণধীন...</div>
            @endif
        </div>
        @empty
        <div style="padding: 20px; text-align: center; color: #6b7280;">কোন নির্ধারিত পরীক্ষা সম্পন্ন হয়নি।</div>
        @endforelse

        <div style="margin-top: 60px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="text-align: center; width: 150px;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">শ্রেণি শিক্ষকের স্বাক্ষর</div>
            </div>
            <div style="text-align: center; width: 150px;">
                @php
                    $headTeacher = $school->teachers()->where('designation', 'like', '%Head%')
                        ->orWhere('designation', 'like', '%Principal%')
                        ->orWhere('designation', 'like', '%প্রধান%')->first();
                @endphp
                @if($headTeacher && $headTeacher->signature)
                    <img src="{{ Storage::url($headTeacher->signature) }}" style="max-height: 40px; margin-bottom: 5px;" alt="Signature">
                @endif
                <div style="border-top: 1px solid #000; padding-top: 5px;">প্রতিষ্ঠান প্রধানের স্বাক্ষর</div>
            </div>
        </div>
    </div>
</body>
</html>
