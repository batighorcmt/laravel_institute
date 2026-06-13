<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>রিপোর্ট কার্ড সামারি - {{ $school->name_bn ?? $school->name }}</title>
    <style>
        body { font-family: 'Kalpurush', Arial, sans-serif; font-size: 13pt; margin: 0; padding: 20px; color: #000000; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-4 { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; color: #000000; }
        th, td { border: 1px solid #000000; padding: 4px; text-align: center; vertical-align: middle; }
        th { background-color: #e5e5e5; font-size: 13pt; font-weight: bold; }
        .header-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        small { font-size: 11pt; color: #000000; }
        @media print {
            body { padding: 0; }
            button.print-btn { display: none; }
        }
        .print-btn {
            position: fixed; top: 20px; right: 20px;
            padding: 10px 20px; font-size: 16pt; background-color: #007bff;
            color: white; border: none; border-radius: 5px; cursor: pointer;
        }
    </style>
</head>
<body>

    @php
       $hasAnyExams = false;
       foreach($studentsData as $d) {
           if(count($d['exams']) > 0) {
               $hasAnyExams = true;
               break;
           }
       }
    @endphp

    <button class="print-btn" onclick="window.print()">প্রিন্ট করুন</button>

    <!-- School Header -->
    <div style="display: flex; align-items: center; border-bottom: 2px solid #000000; padding-bottom: 10px; margin-bottom: 15px;">
        <div style="flex: 0 0 80px;">
            @if($school->logo)
                <img src="{{ Storage::url($school->logo) }}" alt="Logo" style="width: 80px; height: 80px; object-fit: contain;">
            @else
                <div style="width: 80px; height: 80px; border: 1px solid #000000; border-radius: 5px;"></div>
            @endif
        </div>
        <div style="flex: 1; text-align: center;">
            <h1 style="margin: 0; font-size: 22pt; color: #000000; font-weight: bold;">{{ $school->name_bn ?? $school->name }}</h1>
            <div style="font-size: 13pt; color: #000000; margin-top: 5px;">{{ $school->address_bn ?? $school->address }}</div>
            <div style="font-size: 14pt; font-weight: bold; margin-top: 8px; color: #000000; border: 2px solid #000000; display: inline-block; padding: 4px 15px; border-radius: 20px;">রিপোর্ট কার্ড সামারি</div>
        </div>
        <div style="flex: 0 0 80px;"></div>
    </div>

    <div class="text-center mb-4" style="color: #000000;">
        <div style="margin-top: 5px; font-weight: bold;">
            শ্রেণি: {{ $class->name ?? '-' }} | 
            শাখা: {{ $section ? $section->name : 'সকল' }}
        </div>
        @if($startDate && $endDate)
        <div class="mt-2 font-weight-bold">
            তারিখ: {{ toBengaliNumber(\Carbon\Carbon::parse($startDate)->format('d/m/Y')) }} হতে {{ toBengaliNumber(\Carbon\Carbon::parse($endDate)->format('d/m/Y')) }}
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="4%">ক্রমিক</th>
                <th rowspan="2" width="6%">রোল নং</th>
                <th rowspan="2" width="16%" class="text-left">শিক্ষার্থীর নাম</th>
                <th colspan="3" width="12%">উপস্থিতি</th>
                <th colspan="2" width="28%">লেসন ইভ্যালুয়েশন</th>
                @if($hasAnyExams)
                <th colspan="2" width="34%">পরীক্ষার ফলাফল</th>
                @endif
            </tr>
            <tr>
                <th>মোট</th>
                <th>উপস্থিত</th>
                <th>অনুপস্থিত</th>
                <th>পড়া হয়েছে / আংশিক</th>
                <th>পড়া হয়নি / অনুপস্থিত</th>
                @if($hasAnyExams)
                <th>পরীক্ষার নাম</th>
                <th>ফলাফল (GPA)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($studentsData as $index => $data)
                @php
                    $stu = $data['student'];
                    $att = $data['attendanceSummary'];
                    $lesson = $data['lessonSummary'];
                    $exams = $data['exams'];
                    $examsData = $data['examsData'];
                    $examsCount = $hasAnyExams ? max(count($exams), 1) : 1;
                    
                    $totalLesson = $lesson['total'] > 0 ? $lesson['total'] : 1;
                    $positiveLesson = $lesson['completed'] + $lesson['partial'];
                    $negativeLesson = $lesson['not_done'] + $lesson['absent'];
                    $pct = function($val) use ($totalLesson) {
                        return toBengaliNumber(round(($val / $totalLesson) * 100, 1)) . '%';
                    };
                @endphp
                <tr>
                    <td rowspan="{{ $examsCount }}">{{ toBengaliNumber($index + 1) }}</td>
                    <td rowspan="{{ $examsCount }}">{{ toBengaliNumber($stu->currentEnrollment->roll_no ?? '-') }}</td>
                    <td rowspan="{{ $examsCount }}" class="text-left font-weight-bold">{{ $stu->student_name_bn ?? $stu->student_name_en }}</td>
                    
                    <td rowspan="{{ $examsCount }}">{{ toBengaliNumber($att['total_working_days']) }}</td>
                    <td rowspan="{{ $examsCount }}">{{ toBengaliNumber($att['present']) }}</td>
                    <td rowspan="{{ $examsCount }}">{{ toBengaliNumber($att['absent']) }}</td>
                    
                    <td rowspan="{{ $examsCount }}">
                        @if($lesson['total'] > 0)
                            {{ toBengaliNumber($lesson['completed']) }} / {{ toBengaliNumber($lesson['partial']) }} <br><small>({{ $pct($positiveLesson) }})</small>
                        @else - @endif
                    </td>
                    <td rowspan="{{ $examsCount }}">
                        @if($lesson['total'] > 0)
                            {{ toBengaliNumber($lesson['not_done']) }} / {{ toBengaliNumber($lesson['absent']) }} <br><small>({{ $pct($negativeLesson) }})</small>
                        @else - @endif
                    </td>
                    
                    @if($hasAnyExams)
                        @if(count($exams) > 0)
                            @php 
                                $firstExam = $exams->first(); 
                                $res = $examsData[$firstExam->id]['result'] ?? null; 
                            @endphp
                            <td>{{ $firstExam->name_bn ?? $firstExam->name }}</td>
                            <td>
                                @if($res)
                                    @if($res->result_status == 'fail')
                                        <span class="font-weight-bold">ফেল ({{ toBengaliNumber($res->failed_subjects_count) }} বিষয়ে)</span><br>
                                        <small>মোট নম্বর: {{ toBengaliNumber($res->total_marks) }}</small>
                                    @else
                                        <strong>{{ toBengaliNumber($res->gpa) }}</strong> ({{ $res->letter_grade ?? $res->grade }})<br>
                                        <small>মোট নম্বর: {{ toBengaliNumber($res->total_marks) }}</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @else
                            <td>-</td>
                            <td>-</td>
                        @endif
                    @endif
                </tr>
                @if($hasAnyExams && count($exams) > 1)
                    @foreach($exams->skip(1) as $ex)
                    @php $res = $examsData[$ex->id]['result'] ?? null; @endphp
                    <tr>
                        <td>{{ $ex->name_bn ?? $ex->name }}</td>
                        <td>
                            @if($res)
                                @if($res->result_status == 'fail')
                                    <span class="font-weight-bold">ফেল ({{ toBengaliNumber($res->failed_subjects_count) }} বিষয়ে)</span><br>
                                    <small>মোট নম্বর: {{ toBengaliNumber($res->total_marks) }}</small>
                                @else
                                    <strong>{{ toBengaliNumber($res->gpa) }}</strong> ({{ $res->letter_grade ?? $res->grade }})<br>
                                    <small>মোট নম্বর: {{ toBengaliNumber($res->total_marks) }}</small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="12">কোনো শিক্ষার্থীর তথ্য পাওয়া যায়নি।</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 60px; display: flex; justify-content: space-between; color: #000000; font-weight: bold; align-items: flex-end;">
        <div style="text-align: center; border-top: 2px solid #000000; width: 200px; padding-top: 5px;">শ্রেণি শিক্ষকের স্বাক্ষর</div>
        <div style="text-align: center; width: 200px;">
            @php
                $headTeacher = $school->teachers()->where('designation', 'like', '%Head%')
                    ->orWhere('designation', 'like', '%Principal%')
                    ->orWhere('designation', 'like', '%প্রধান%')->first();
            @endphp
            @if($headTeacher && $headTeacher->signature)
                <img src="{{ Storage::url($headTeacher->signature) }}" style="max-height: 40px; margin-bottom: 5px;" alt="Signature">
            @else
                <div style="height: 40px; margin-bottom: 5px;"></div>
            @endif
            <div style="border-top: 2px solid #000000; padding-top: 5px;">প্রধান শিক্ষকের স্বাক্ষর</div>
        </div>
    </div>

</body>
</html>
