<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রবেশপত্র - {{ $exam->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .admit-card {
            page-break-after: always;
            border: 3px solid #333;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .admit-card:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: bold;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 18pt;
            font-weight: bold;
        }

        .header p {
            margin: 2px 0;
            font-size: 11pt;
        }

        .admit-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 15px;
            border: 2px solid #333;
        }

        .student-info {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }

        .info-value {
            flex-grow: 1;
            border-bottom: 1px dotted #333;
        }

        .student-photo {
            position: absolute;
            top: 120px;
            right: 30px;
            width: 100px;
            height: 120px;
            border: 2px solid #333;
        }

        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .exam-routine {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .exam-routine th,
        .exam-routine td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        .exam-routine th {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        .instructions {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
        }

        .instructions h4 {
            margin: 0 0 5px 0;
            font-size: 12pt;
        }

        .instructions ul {
            margin: 5px 0;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 3px;
            font-size: 10pt;
        }

        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 150px;
            margin-top: 40px;
        }

        @media print {
            .admit-card {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    @foreach($students as $student)
        @php
            $seatAllocation = $seatAllocations->where('student_id', $student->id)->first();
        @endphp
        <div class="admit-card">
            <div class="header">
                <h1>{{ $school->school_name }}</h1>
                <p>{{ $school->address }}</p>
                <p>ফোনঃ {{ $school->phone ?? '' }} | ইমেইলঃ {{ $school->email ?? '' }}</p>
            </div>

            <div class="admit-title">
                প্রবেশপত্র (ADMIT CARD)<br>
                {{ $exam->name_bn ?? $exam->name }}
            </div>

            @if($student->photo_url)
                <div class="student-photo">
                    <img src="{{ asset($student->photo_url) }}" alt="Student Photo">
                </div>
            @endif

            <div class="student-info">
                <div class="info-row">
                    <div class="info-label">শিক্ষার্থীর নামঃ</div>
                    <div class="info-value">{{ $student->student_name_bn ?? $student->student_name_en }}</div>
                </div>

                <div class="info-row">
                    <div class="info-label">পিতার নামঃ</div>
                    <div class="info-value">{{ $student->father_name_bn ?? $student->father_name }}</div>
                </div>

                <div class="info-row">
                    <div class="info-label">মাতার নামঃ</div>
                    <div class="info-value">{{ $student->mother_name_bn ?? $student->mother_name }}</div>
                </div>

                <div class="info-row">
                    <div class="info-label">শ্রেণিঃ</div>
                    <div class="info-value">{{ $exam->class->name }}</div>
                </div>

                <div class="info-row">
                    <div class="info-label">রোল নম্বরঃ</div>
                    <div class="info-value">{{ $student->student_id }}</div>
                </div>

                @if($seatAllocation)
                    <div class="info-row">
                        <div class="info-label">পরীক্ষা কক্ষঃ</div>
                        <div class="info-value">{{ $seatAllocation->room->title ?? 'Room ' . $seatAllocation->room->room_no }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">আসন নম্বরঃ</div>
                        <div class="info-value">{{ $seatAllocation->seat_number }}</div>
                    </div>
                @endif

                <div class="info-row">
                    <div class="info-label">পরীক্ষার তারিখঃ</div>
                    <div class="info-value">
                        {{ $exam->start_date ? $exam->start_date->format('d/m/Y') : '' }} 
                        @if($exam->end_date && $exam->end_date != $exam->start_date)
                            - {{ $exam->end_date->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
            </div>

            <table class="exam-routine">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>বিষয়</th>
                        <th>সময়</th>
                        <th>পূর্ণমান</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exam->examSubjects->sortBy('exam_date') as $examSubject)
                        <tr>
                            <td>{{ $examSubject->exam_date ? $examSubject->exam_date->format('d/m/Y') : '' }}</td>
                            <td>{{ $examSubject->subject->name }}</td>
                            <td>
                                @if($examSubject->exam_start_time && $examSubject->exam_end_time)
                                    {{ date('h:i A', strtotime($examSubject->exam_start_time)) }} - {{ date('h:i A', strtotime($examSubject->exam_end_time)) }}
                                @endif
                            </td>
                            <td>{{ $examSubject->total_full_mark }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="instructions">
                <h4>নির্দেশনাঃ</h4>
                <ul>
                    <li>পরীক্ষায় অংশগ্রহণের জন্য প্রবেশপত্র অবশ্যই সাথে রাখতে হবে।</li>
                    <li>পরীক্ষা শুরুর ১৫ মিনিট পূর্বে পরীক্ষা কক্ষে উপস্থিত হতে হবে।</li>
                    <li>পরীক্ষা কক্ষে মোবাইল ফোন, ক্যালকুলেটর বা অন্য কোনো ইলেকট্রনিক ডিভাইস নিষিদ্ধ।</li>
                    <li>পরীক্ষার খাতায় রোল নম্বর ছাড়া অন্য কোনো চিহ্ন দেওয়া যাবে না।</li>
                    <li>অসদুপায় অবলম্বন করলে পরীক্ষার ফলাফল বাতিল করা হবে।</li>
                </ul>
            </div>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p>পরীক্ষা নিয়ন্ত্রক</p>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p>অধ্যক্ষ/প্রধান শিক্ষক</p>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
