@php
    $subjectResults = $result->subject_results;
    $mainSubjects = collect();
    $optionalSubject = null;
    $totalGP = 0;
    
    // Group subjects to handle combined parts
    foreach($finalSubjects as $key => $fSub) {
        $res = $subjectResults->get($key);
        if (!$res) continue;

        $isAbsent = !empty($res['is_absent']);
        $isNotFound = ($res['grade'] == 'N/R');
        $hasAnyMark = ($res['creative'] > 0 || $res['mcq'] > 0 || $res['practical'] > 0 || $res['total'] > 0);

        // Prepare data
        $subData = [
            'name' => $res['name'] ?? $fSub['name'] ?? 'Unknown',
            'creative' => ($isNotFound || ($fSub['creative_full_mark'] ?? 0) <= 0) ? '-' : $res['creative'],
            'mcq' => ($isNotFound || ($fSub['mcq_full_mark'] ?? 0) <= 0) ? '-' : $res['mcq'],
            'practical' => ($isNotFound || ($fSub['practical_full_mark'] ?? 0) <= 0) ? '-' : $res['practical'],
            'total' => $isNotFound ? '-' : ($isAbsent ? 'Ab' : $res['total']),
            'grade' => $isNotFound ? '-' : ($isAbsent ? 'F' : $res['grade']),
            'gp' => $isNotFound ? '0.00' : number_format($res['gpa'] ?? 0, 2),
            'full_mark' => $res['full_mark'] ?? ($fSub['total_full_mark'] ?? '-'),
            'highest_mark' => $res['highest_mark'] ?? '-',
            'is_part' => !empty($res['display_only']),
            'is_combined' => !empty($fSub['is_combined_result']),
            'is_failed' => ($isAbsent || $res['grade'] == 'F' || $res['grade'] == 'N/R')
        ];

        if (!empty($res['is_optional'])) {
            $optionalSubject = $subData;
            $optionalGP = $res['gpa'] ?? 0;
        } else {
            $mainSubjects->push($subData);
            if (!$subData['is_part']) {
                $totalGP += (float) ($res['gpa'] ?? 0);
            }
        }
    }

    // Recount "Summary" main subjects
    $totalSummaryCompulsory = $mainSubjects->where('is_part', false)->count();
    $hasAnyCompulsoryFail = ($result->fail_count > 0);
    
    if ($hasAnyCompulsoryFail || $totalSummaryCompulsory == 0) {
        $gpaWithoutAdditional = '0.00';
    } else {
        $gpaWithoutAdditional = number_format($totalGP / $totalSummaryCompulsory, 2);
    }
@endphp

<div class="print-content" style="page-break-after: always;">
    
    <!-- Background -->
    <div class="bg-pattern"></div>

    <!-- Header -->
    <div class="header-section">
        @if($school->logo)
            <img src="{{ asset('storage/'.$school->logo) }}" class="header-logo" alt="Logo">
        @endif
        
        <div class="header-text">
            <h1>{{ $school->name }}</h1>
            <h2>{{ $school->address }}</h2>
        </div>

        <img src="{{ $student->photo_url }}" class="header-student-photo" alt="Student Photo">

        <div class="transcript-title">ACADEMIC TRANSCRIPT / একাডেমিক ট্রান্সক্রিপ্ট</div>
        <div class="exam-name-header">{{ $exam->name }} </div>
    </div>

    <!-- Info & Grading -->
    <div class="info-grading-container">
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Name of Student / নাম</td><td class="colon">:</td>
                    <td class="value"><span style="font-size: 13pt;">{{ $student->student_name_bn ?: $student->student_name_en }}</span> ({{ $student->student_id }})</td>
                </tr>
                <tr>
                    <td class="label">Father's Name / পিতার নাম</td><td class="colon">:</td>
                    <td class="value">{{ $student->father_name_bn ?: $student->father_name }}</td>
                </tr>
                <tr>
                    <td class="label">Mother's Name / মাতার নাম</td><td class="colon">:</td>
                    <td class="value">{{ $student->mother_name_bn ?: $student->mother_name }}</td>
                </tr>
                <tr>
                    <td class="label">Class & Section / শ্রেণি ও শাখা</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->class->name ?? '-' }} ({{ $student->currentEnrollment->section->name ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Roll Number / রোল নম্বর</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->roll_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Group / বিভাগ</td><td class="colon">:</td>
                    <td class="value">{{ $result->group_name ?? $student->currentEnrollment->group->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Birth / জন্ম তারিখ</td><td class="colon">:</td>
                    <td class="value">{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Result Status / ফলাফল</td><td class="colon">:</td>
                    <td class="value">
                        @if($result->fail_count > 0)
                          <span class="result-status-red">Failed / অকৃতকার্য ({{ $result->fail_count }} Subjects)</span>
                        @else
                            <span class="result-status-green">Passed / উত্তীর্ণ</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <table class="grading-table">
            <thead>
                <tr>
                    <th>Interval / নম্বর</th>
                    <th>Grade / গ্রেড</th>
                    <th>GP / পয়েন্ট</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>80-100</td><td>A+</td><td>5.00</td></tr>
                <tr><td>70-79</td><td>A</td><td>4.00</td></tr>
                <tr><td>60-69</td><td>A-</td><td>3.50</td></tr>
                <tr><td>50-59</td><td>B</td><td>3.00</td></tr>
                <tr><td>40-49</td><td>C</td><td>2.00</td></tr>
                <tr><td>33-39</td><td>D</td><td>1.00</td></tr>
                <tr><td>0-32</td><td>F</td><td>0.00</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Results Table -->
    <table class="result-table">
        <thead>
            <tr>
                <th style="width: 40px;">Sl. No.<br>ক্রমিক</th>
                <th class="text-left">Name of Subjects / বিষয়ের নাম</th>
                <th style="width: 40px;">Full Marks<br>পূর্ণ নম্বর</th>
                <th style="width: 40px;">Highest<br>সর্বোচ্চ</th>
                <th style="width: 40px;">CQ</th>
                <th style="width: 40px;">MCQ</th>
                <th style="width: 40px;">PR</th>
                <th style="width: 50px;">Total<br>মোট</th>
                <th style="width: 50px;">LG<br>গ্রেড</th>
                <th style="width: 50px;">GP<br>পয়েন্ট</th>
                <th style="width: 60px;">GPA<br><small>(W/O Addl)</small><br>জিপিএ</th>
                <th style="width: 60px;">GPA<br>জিপিএ</th>
            </tr>
        </thead>
        <tbody>
            @php $sl = 1; @endphp
            @foreach($mainSubjects as $index => $sub)
                <tr>
                    <td>{{ $sub['is_part'] ? '' : $sl++ }}</td>
                    <td class="text-left sub-name" style="{{ $sub['is_part'] ? 'padding-left: 20px; font-weight: normal; font-style: italic;' : '' }}">
                        {{ $sub['name'] }}
                    </td>
                    <td>{{ $sub['full_mark'] ?? '-' }}</td>
                    <td>{{ $sub['highest_mark'] ?? '-' }}</td>
                    <td>{{ $sub['creative'] }}</td>
                    <td>{{ $sub['mcq'] }}</td>
                    <td>{{ $sub['practical'] }}</td>
                    <td>{{ $sub['total'] }}</td>
                    <td>{{ $sub['is_part'] ? '' : $sub['grade'] }}</td>
                    <td>{{ $sub['is_part'] ? '' : $sub['gp'] }}</td>
                    
                    @if($index === 0)
                        <td rowspan="{{ count($mainSubjects) }}" style="vertical-align: middle;">
                             {{ $gpaWithoutAdditional }}
                        </td>
                        <td rowspan="{{ count($mainSubjects) + ($optionalSubject ? 2 : 0) + 1 }}" style="vertical-align: middle; font-weight: bold; font-size: 14pt;">
                            {{ number_format($result->computed_gpa, 2) }}
                        </td>
                    @endif
                </tr>
            @endforeach

            <!-- Additional Subject -->
            @if($optionalSubject)
                <tr>
                    <td colspan="11" style="background-color: #f9f9f9; padding: 2px 10px; border-bottom: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <b>Additional Subject / অতিরিক্ত বিষয়:</b>
                            <span style="color: red; font-size: 9pt; font-weight: bold;">GP Above 2 (+{{ (isset($optionalGP) && $optionalGP > 2) ? number_format($optionalGP - 2.0, 2) : '0.00' }})</span>
                        </div>
                    </td>
                </tr>
                 <tr>
                    <td>{{ $sl++ }}</td>
                    <td class="text-left sub-name">{{ $optionalSubject['name'] }}</td>
                    <td>{{ $optionalSubject['full_mark'] ?? '-' }}</td>
                    <td>{{ $optionalSubject['highest_mark'] ?? '-' }}</td>
                    <td>{{ $optionalSubject['creative'] }}</td>
                    <td>{{ $optionalSubject['mcq'] }}</td>
                    <td>{{ $optionalSubject['practical'] }}</td>
                    <td>{{ $optionalSubject['total'] }}</td>
                    <td>{{ $optionalSubject['grade'] }}</td>
                    <td>{{ $optionalSubject['gp'] }}</td>
                    <td class="text-center" style="font-weight: bold;">
                        {{ (isset($optionalGP) && $optionalGP > 2) ? number_format($optionalGP - 2.0, 2) : '0.00' }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Merit position Cards -->
    <div class="summary-cards">
        <div class="card-item">
            <div class="card-label">Grand Total Marks / মোট নম্বর</div>
            <span class="card-highlight">{{ $result->computed_total_marks }}</span>
        </div>
        <div class="card-item">
            <div class="card-label">Merit Position (Class) / মেধা স্থান (শ্রেণি)</div>
            <span class="card-highlight">{{ $result->class_position }}</span>
        </div>
        <div class="card-item">
            <div class="card-label">Merit Position (Section) / মেধা স্থান (শাখা)</div>
            <span class="card-highlight">{{ $result->section_position }}</span>
        </div>
    </div>

    <!-- Extra Activities & Attendance Table -->
    <table class="extra-activities-table" style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 8.5pt;">
        <tr>
            <!-- Attendance Column -->
            <td style="width: 48%; border: 1px solid #000; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr><th colspan="2" style="border-bottom: 1px solid #000; padding: 3px; background: #eee;">Attendance / উপস্থিতি</th></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd; width: 70%;">Total School Days / মোট কার্যদিবস</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd; text-align: center;">{{ $result->attendance_days }}</td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Total Present / মোট উপস্থিতি</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd; text-align: center;">{{ $result->attendance_present }}</td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Total Absent / মোট অনুপস্থিতি</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd; text-align: center;">{{ $result->attendance_absent }}</td></tr>
                    <tr><td style="padding: 2px 5px;">Attendance Rate (%) / উপস্থিতির হার</td><td style="border-left: 1px solid #ddd; text-align: center;">{{ $result->attendance_rate }}%</td></tr>
                </table>
            </td>

            <!-- Spacer -->
            <td style="width: 4%; border: none;"></td>

            <!-- Co-curricular Column -->
            <td style="width: 48%; border: 1px solid #000; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr><th colspan="2" style="border-bottom: 1px solid #000; padding: 3px; background: #eee;">Co-Curricular / সহ-পাঠক্রমিক</th></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd; width: 70%;">Moral Education / নৈতিক শিক্ষা</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Arts & Crafts / চারু ও কারুকলা</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Health & Sports / স্বাস্থ্য ও খেলাধুলা</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px;">Discipline / শৃঙ্খলা</td><td style="border-left: 1px solid #ddd;"></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 9pt;">
        <b>Remarks / মন্তব্য:</b> ____________________________________________________________________________________________________
    </div>

    <div class="footer-section">
        <div class="signature-box">
             <div style="height: 40px;"></div>
            <div class="signature-line">Class Teacher / শ্রেণি শিক্ষক</div>
        </div>
        <div class="signature-box">
             <div style="display: flex; align-items: flex-end; justify-content: center; min-height: 40px;">
                @if(isset($principalTeacher) && $principalTeacher->signature)
                    <img src="{{ asset('storage/' . $principalTeacher->signature) }}" alt="Signature" style="max-height: 45px; max-width: 150px; margin-bottom: 2px;">
                @endif
             </div>
             <div class="signature-line">Head Teacher / প্রধান শিক্ষক</div>
        </div>
    </div>
    
    <div class="date-publication">
        Date of Publication of Result / ফলাফল প্রকাশের তারিখ : {{ $result->published_at ? \Carbon\Carbon::parse($result->published_at)->format('d F, Y') : date('d F, Y') }}
    </div>

</div>
