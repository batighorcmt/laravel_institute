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
                <div style="font-size: 11pt; color: #374151;">{{ $school->address_bn ?? $school->address }}</div>
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
                    <div class="detail-item">আইডি: {{ $student->student_id }}</div>
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
            <tfoot>
                @php
                    $leCompleted = (int) ($lessonSummary['completed'] ?? 0);
                    $lePartial = (int) ($lessonSummary['partial'] ?? 0);
                    $leNotDone = (int) ($lessonSummary['not_done'] ?? 0);
                    $leAbsent = (int) ($lessonSummary['absent'] ?? 0);
                    $leTotal = $leCompleted + $lePartial + $leNotDone + $leAbsent;
                    $lePositive = $leCompleted + $lePartial;
                    $leNegative = $leNotDone + $leAbsent;
                    $lePct = fn (int $count) => $leTotal > 0 ? round(($count / $leTotal) * 100, 1) : 0;
                @endphp
                <tr style="background-color: #f9fafb;">
                    <td style="font-weight: bold; font-size: 11pt;">মোট সারাংশ</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($leCompleted) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($lePartial) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($leNotDone) }}</td>
                    <td style="text-align: center; font-size: 11pt; font-weight: bold;">{{ toBengaliNumber($leAbsent) }}</td>
                </tr>
                <tr style="background-color: #f9fafb;">
                    <td style="font-weight: bold; font-size: 11pt;">শতকরা হার</td>
                    <td colspan="2" style="text-align: center; font-size: 11pt; font-weight: bold; color: #047857;">{{ toBengaliNumber($lePct($lePositive)) }}%</td>
                    <td colspan="2" style="text-align: center; font-size: 11pt; font-weight: bold; color: #b91c1c;">{{ toBengaliNumber($lePct($leNegative)) }}%</td>
                </tr>
            </tfoot>
        </table>

        @if(count($exams) > 0)
        <div class="section-title">
            <i class="fas fa-file-invoice"></i> সম্পন্ন পরীক্ষা ও ফলাফল
        </div>
        @foreach($exams as $exam)
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
        @endforeach
        @endif

        <div class="signature-section">
            <div class="report-card-signature-note"></div>

            <div class="signature-row">
            <div style="text-align: center; width: 150px;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">অভিভাবকের স্বাক্ষর</div>
            </div>
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
    </div>
