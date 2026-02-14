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
        
        <div class="header-main">
            @if($school->logo)
                <img src="{{ asset('storage/'.$school->logo) }}" class="header-logo" alt="Logo">
            @endif
            <div class="header-text">
                <h1>{{ $school->name }}</h1>
                <h2>{{ $school->address }}</h2>
            </div>
        </div>

        <div class="transcript-title">ACADEMIC TRANSCRIPT</div>
        <div class="exam-name-header">{{ $exam->name }} </div>
    </div>

    <!-- Info & Grading -->
    <div class="info-grading-container">
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Name of Student</td><td class="colon">:</td>
                    <td class="value"><span style="font-size: 13pt;">{{ $student->student_name_en ?: $student->student_name_bn }}</span> ({{ $student->student_id }})</td>
                </tr>
                <tr>
                    <td class="label">Father's Name</td><td class="colon">:</td>
                    <td class="value">{{ $student->father_name ?: $student->father_name_bn }}</td>
                </tr>
                <tr>
                    <td class="label">Mother's Name</td><td class="colon">:</td>
                    <td class="value">{{ $student->mother_name ?: $student->mother_name_bn }}</td>
                </tr>
                <tr>
                    <td class="label">Class & Section</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->class->name ?? '-' }} ({{ $student->currentEnrollment->section->name ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Roll Number</td><td class="colon">:</td>
                    <td class="value">{{ $student->currentEnrollment->roll_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Group</td><td class="colon">:</td>
                    <td class="value">{{ $result->group_name ?? $student->currentEnrollment->group->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td><td class="colon">:</td>
                    <td class="value">{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Result Status</td><td class="colon">:</td>
                    <td class="value">
                        @if($result->fail_count > 0)
                            Failed (Failed in {{ $result->fail_count }} subjects)
                        @else
                            Passed
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <table class="grading-table">
            <thead>
                <tr>
                    <th>Class Interval</th>
                    <th>Letter Grade</th>
                    <th>Grade Point</th>
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
                <th style="width: 40px;">Sl. No.</th>
                <th class="text-left">Name of Subjects</th>
                <th style="width: 40px;">CQ</th>
                <th style="width: 40px;">MCQ</th>
                <th style="width: 40px;">PR</th>
                <th style="width: 50px;">Total Mark</th>
                <th style="width: 50px;">Letter Grade</th>
                <th style="width: 50px;">Grade Point</th>
                <th style="width: 60px;">GPA<br><small>(W/O Addl)</small></th>
                <th style="width: 60px;">GPA</th>
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
                    <td>{{ $sl++ }}</td>
                    <td class="text-left sub-name">
                        <span style="font-weight: normal; font-size: 9pt;">Additional Subject:</span><br>
                        {{ $optionalSubject['name'] }}
                    </td>
                    <td>{{ $optionalSubject['creative'] }}</td>
                    <td>{{ $optionalSubject['mcq'] }}</td>
                    <td>{{ $optionalSubject['practical'] }}</td>
                    <td>{{ $optionalSubject['total'] }}</td>
                    <td>{{ $optionalSubject['grade'] }}</td>
                    <td>{{ $optionalSubject['gp'] }}</td>
                    <td colspan="2" style="background-color: #f7f7f7;">
                        <span style="font-size: 8pt; color: #666;">GP Above 2</span><br>
                        <b>{{ (isset($optionalGP) && $optionalGP > 2) ? number_format($optionalGP - 2.0, 2) : '0.00' }}</b>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Merit position Cards -->
    <div class="summary-cards">
        <div class="card-item">
            <div class="card-label">Grand Total Marks</div>
            <b>{{ $result->computed_total_marks }}</b>
        </div>
        <div class="card-item">
            <div class="card-label">Merit Position (Class)</div>
            <b>{{ $result->class_position }}</b>
        </div>
        <div class="card-item">
            <div class="card-label">Merit Position (Section)</div>
            <b>{{ $result->section_position }}</b>
        </div>
    </div>

    <!-- Extra Activities & Attendance Table -->
    <table class="extra-activities-table" style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 8.5pt;">
        <tr>
            <!-- Attendance Column -->
            <td style="width: 48%; border: 1px solid #000; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr><th colspan="2" style="border-bottom: 1px solid #000; padding: 3px; background: #eee;">Attendance</th></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd; width: 70%;">Total School Days</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Total Present</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Total Absent</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px;">Attendance Rate (%)</td><td style="border-left: 1px solid #ddd;"></td></tr>
                </table>
            </td>

            <!-- Spacer -->
            <td style="width: 4%; border: none;"></td>

            <!-- Co-curricular Column -->
            <td style="width: 48%; border: 1px solid #000; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr><th colspan="2" style="border-bottom: 1px solid #000; padding: 3px; background: #eee;">Co-Curricular Activities</th></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd; width: 70%;">Moral Education</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Arts & Crafts</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px; border-bottom: 1px solid #ddd;">Health & Sports</td><td style="border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td></tr>
                    <tr><td style="padding: 2px 5px;">Discipline</td><td style="border-left: 1px solid #ddd;"></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top: 10px; font-size: 9pt;">
        <b>Remarks:</b> ____________________________________________________________________________________________________
    </div>

    <!-- Footer Signatures -->
    <div class="footer-section">
        <div class="signature-box">
             <div style="height: 30px;"></div>
            <div class="signature-line">Compared by</div>
        </div>
        <div class="signature-box">
             <!-- Empty space for signature -->
             <div style="height: 30px;"></div>
             <div class="signature-line">Controller of Examinations</div>
        </div>
    </div>
    
    <div class="date-publication">
        Date of Publication of Result : {{ $result->published_at ? \Carbon\Carbon::parse($result->published_at)->format('d F, Y') : date('d F, Y') }}
    </div>

</div>
