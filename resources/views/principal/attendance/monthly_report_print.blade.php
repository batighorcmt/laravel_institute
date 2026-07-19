@extends('layouts.print')

@section('suppress_header')@endsection
@section('suppress_watermark')@endsection

@php
    // Reuse variables from controller: $students, $dates, $attendanceMatrix, $holidayDates, $weeklyHolidayNums, $month, $classes, $sections
    $bnDigits = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];
    $toBn = function ($value) use ($bnDigits) {
        $str = (string) $value;
        return strtr($str, $bnDigits); };
    $bnMonths = [1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর'];
    $bnWeekdaysShort = [1 => 'সোম', 2 => 'মঙ্গল', 3 => 'বুধ', 4 => 'বৃহ.', 5 => 'শুক্র', 6 => 'শনি', 7 => 'রবি'];
@endphp

@push('print_head')
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }

        body {
            background: #fff;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            table-layout: fixed;
            page-break-inside: auto !important;
        }

        .attendance-table tbody {
            page-break-inside: auto !important;
        }

        .attendance-table tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #333;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }

        .attendance-table thead th {
            background: #f1f1f1;
            font-weight: 700;
            padding: 4px 2px;
        }

        .name-col {
            text-align: left;
            padding-left: 4px !important;
            white-space: normal;
            line-height: 1.2;
            word-break: break-word;
        }

        .holiday-label {
            font-size: 9px;
            letter-spacing: -0.5px;
        }

        /* Header centering and larger school name for print */
        .print-header {
            display: block !important;
        }

        .print-header>div {
            border: none !important;
            text-align: center !important;
            width: 100%;
            display: block;
        }

        .school-name-text {
            font-size: 34px !important;
            font-weight: 900 !important;
            display: block;
            margin-bottom: 0px;
            line-height: 1;
        }

        .school-address-text {
            font-size: 15px !important;
            display: block;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .report-title-text {
            font-size: 18px !important;
            font-weight: 700 !important;
            display: block;
            margin-bottom: 2px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .report-meta-text {
            font-size: 14px !important;
            display: block;
            line-height: 1.2;
        }

        /* Column widths for fixed layout */
        .col-roll {
            width: 40px;
        }

        .col-name {
            width: 140px;
        }

        .col-total {
            width: 50px;
        }

        /* Present/absent icon colors for print */
        .present-icon {
            color: #008000 !important;
            font-weight: bold;
            font-size: 14px;
        }

        .absent-icon {
            color: #ff0000 !important;
            font-weight: bold;
            font-size: 14px;
        }

        .late-icon {
            color: #ff9800 !important;
            font-weight: bold;
            font-size: 14px;
        }

        .half-day-icon {
            color: #17a2b8 !important;
            font-weight: bold;
            font-size: 14px;
        }

        /* Text colors for tfoot */
        .text-success {
            color: #008000 !important;
            font-weight: bold;
        }

        .text-danger {
            color: #ff0000 !important;
            font-weight: bold;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .info-card {
            flex: 1;
            padding: 6px 10px;
            border-radius: 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .info-card-title {
            font-weight: 700;
            font-size: 10px;
            opacity: 0.85;
            margin-bottom: 2px;
        }

        .info-card-value {
            font-weight: 900;
            font-size: 13px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            table thead {
                display: table-header-group;
            }
        }
    </style>
@endpush

@section('content')
    <div class="print-only">
        @php
            $selectedClass = collect($classes)->firstWhere('id', request('class_id'));
            $className = $selectedClass->bangla_name ?? $selectedClass->name ?? null;
            $selectedSection = collect($sections)->firstWhere('id', request('section_id'));
            $sectionName = $selectedSection->bangla_name ?? $selectedSection->name ?? null;
            $year = null;
            $monthName = null;
            if (!empty($month)) {
                $ts = strtotime($month . '-01');
                $year = $toBn(date('Y', $ts));
                $monthName = $bnMonths[(int) date('n', $ts)] ?? date('F', $ts);
            }

            $meta = [];
            if (!empty($year)) {
                $meta[] = 'বছর: ' . $year;
            }
            if (!empty($monthName)) {
                $meta[] = 'মাস: ' . $monthName;
            } elseif (!empty($month)) {
                $meta[] = 'মাস: ' . date('F Y', strtotime($month . '-01'));
            }
            if (!empty($className)) {
                $meta[] = 'শ্রেণি: ' . $className;
            }
            if (!empty($sectionName)) {
                $meta[] = 'শাখা: ' . $sectionName;
            }

            // Calculate monthly day stats
            $totalWorkingDaysCount = 0;
            $attendanceTakenDays = 0;
            $attendanceNotTakenDays = 0;

            foreach($dates ?? [] as $d) {
                $wdnLoop = (int) date('N', strtotime($d));
                $isHoliday = in_array($d, $holidayDates ?? []) || in_array($wdnLoop, $weeklyHolidayNums ?? []);
                
                if (!$isHoliday) {
                    $totalWorkingDaysCount++;
                    
                    $taken = false;
                    foreach($students ?? [] as $st) {
                        if (isset($attendanceMatrix[$st->student_id][$d])) {
                            $taken = true;
                            break;
                        }
                    }
                    
                    if ($taken) {
                        $attendanceTakenDays++;
                    } else {
                        $attendanceNotTakenDays++;
                    }
                }
            }
        @endphp

        <div class="print-header d-none d-print-block"
            style="text-align:center; border-bottom:1px solid #000; padding-bottom:10px; margin-bottom: 10px; position: relative;">
            @if(!empty($school->logo))
                <div style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: auto !important; text-align: left !important; display: inline-block !important;">
                    <img src="{{ asset('storage/' . $school->logo) }}" style="height: 65px; width: auto; object-fit: contain;">
                </div>
            @endif
            
            <div style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); text-align: right; font-size: 13px; line-height: 1.5;">
                <div style="color: #000; text-align: right;">মোট কর্মদিবস: <span style="font-weight: 700;">{{ $toBn($totalWorkingDaysCount) }}</span></div>
                <div style="color: #000; text-align: right;">হাজিরা গৃহীত: <span style="font-weight: 700; color: #2e7d32;">{{ $toBn($attendanceTakenDays) }}</span></div>
                <div style="color: #000; text-align: right;">হাজিরা অগৃহীত: <span style="font-weight: 700; color: #c62828;">{{ $toBn($attendanceNotTakenDays) }}</span></div>
            </div>

            <div class="school-name-text">{{ $school->name_bn ?? $school->name ?? config('app.name') }}</div>
            @php
                $address = $school->address_bn ?? $school->address;
            @endphp
            @if(!empty($address))
                <div class="school-address-text">{{ $address }}</div>
            @endif
            <div class="report-title-text">মাসিক হাজিরা রিপোর্ট (Attendance)</div>
            @if(!empty($meta))
                <div class="report-meta-text">{{ implode(' | ', $meta) }}</div>
            @endif
        </div>
    </div>

    <div style="margin-top:6px;">
        @php
            // compute daily totals
            $dailyPresent = [];
            $dailyAbsent = [];
            $workingDays = 0;
            foreach (($dates ?? []) as $d) {
                $dailyPresent[$d] = 0;
                $dailyAbsent[$d] = 0;
                $wdnTmp = (int) date('N', strtotime($d));
                if (!in_array($d, $holidayDates ?? []) && !in_array($wdnTmp, $weeklyHolidayNums ?? [])) {
                    $workingDays++;
                }
            }
            foreach (($students ?? []) as $stRow) {
                foreach (($dates ?? []) as $d) {
                    $status = $attendanceMatrix[$stRow->student_id][$d] ?? null;
                    $wdnTmp = (int) date('N', strtotime($d));
                    $isHoliday = in_array($d, $holidayDates ?? []) || in_array($wdnTmp, $weeklyHolidayNums ?? []);
                    if ($isHoliday)
                        continue;
                    if (in_array($status, ['present', 'late', 'half_day'])) {
                        $dailyPresent[$d]++;
                    } else {
                        $dailyAbsent[$d]++;
                    }
                }
            }
            $sumPresent = array_sum($dailyPresent);
            $sumAbsent = array_sum($dailyAbsent);
            
            $totalStudents = count($students);
            $totalPossible = $totalStudents * $workingDays;
            $currentRate = $totalPossible > 0 ? ($sumPresent / $totalPossible) * 100 : 0;
            $currentAbsentRate = $totalPossible > 0 ? ($sumAbsent / $totalPossible) * 100 : 0;

            // Rank Calculation
            $yearVal = $selectedYearId ?? null;
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $sectionEnrollments = \App\Models\StudentEnrollment::where('school_id', $school->id)
                ->where('status', 'active')
                ->when($yearVal, fn($q) => $q->where('academic_year_id', $yearVal))
                ->whereNotNull('section_id')
                ->selectRaw('section_id as sec_id, count(student_id) as total_students')
                ->groupBy('section_id')
                ->pluck('total_students', 'sec_id')
                ->toArray();

            $sectionPresents = \App\Models\Attendance::whereBetween('attendance.date', [$startDate, $endDate])
                ->whereIn('attendance.status', ['present', 'late', 'half_day'])
                ->whereNotNull('attendance.section_id')
                ->where('attendance.school_id', $school->id)
                ->selectRaw('attendance.section_id as sec_id, count(attendance.id) as present_count')
                ->groupBy('attendance.section_id')
                ->pluck('present_count', 'sec_id')
                ->toArray();

            $sectionRates = [];
            foreach($sectionEnrollments as $secId => $stCount) {
                $possible = $stCount * $workingDays;
                $present = $sectionPresents[$secId] ?? 0;
                $rate = $possible > 0 ? ($present / $possible) * 100 : 0;
                $sectionRates[$secId] = round($rate, 4);
            }
            arsort($sectionRates);

            $currentSectionId = request('section_id');
            $currentRank = 0;
            $rank = 1;
            $prevRate = -1;
            $actualRank = 1;
            foreach($sectionRates as $secId => $rate) {
                if ($rate !== $prevRate) {
                    $actualRank = $rank;
                }
                if ($secId == $currentSectionId) {
                    $currentRank = $actualRank;
                    break;
                }
                $prevRate = $rate;
                $rank++;
            }
            $totalSectionsCount = count($sectionRates);
            if ($currentRank === 0 && !isset($sectionRates[$currentSectionId])) {
                $currentRank = '-';
                $totalSectionsCount = $totalSectionsCount > 0 ? $totalSectionsCount : '-';
            }
            
            // Teacher name
            $classTeacherName = 'নির্ধারিত নয়';
            if ($selectedSection) {
                if ($selectedSection->class_teacher_id) {
                    $teacher = \App\Models\Teacher::find($selectedSection->class_teacher_id);
                    if ($teacher) {
                        $name = $teacher->full_name_bn ?: $teacher->full_name;
                        if (!empty($teacher->initials)) {
                            $name .= ' (' . $teacher->initials . ')';
                        }
                        $classTeacherName = $name;
                    } else {
                        $classTeacherName = $selectedSection->class_teacher_name ?? 'নির্ধারিত নয়';
                    }
                } else {
                    $classTeacherName = $selectedSection->class_teacher_name ?? 'নির্ধারিত নয়';
                }
            }

            // Fetch Holiday Data
            $holidaysData = \App\Models\Holiday::where('school_id', $school->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get(['date', 'title'])
                ->keyBy(fn($h) => \Carbon\Carbon::parse($h->date)->toDateString());
        @endphp

        <!-- Cards -->
        <div style="display: flex; justify-content: space-between; gap: 8px; margin-bottom: 8px; font-size: 11px;">
            <div class="info-card" style="background-color: #e3f2fd; border-left: 4px solid #2196f3; color: #0d47a1;">
                <div class="info-card-title">শ্রেণি শিক্ষক</div>
                <div class="info-card-value">{{ $classTeacherName }}</div>
            </div>
            <div class="info-card" style="background-color: #f3e5f5; border-left: 4px solid #9c27b0; color: #4a148c;">
                <div class="info-card-title">মোট শিক্ষার্থী</div>
                <div class="info-card-value">{{ $toBn($totalStudents) }} জন</div>
            </div>
            <div class="info-card" style="background-color: #e8f5e9; border-left: 4px solid #4caf50; color: #1b5e20;">
                <div class="info-card-title">উপস্থিতির হার</div>
                <div class="info-card-value">{{ $toBn(number_format($currentRate, 2)) }}%</div>
            </div>
            <div class="info-card" style="background-color: #ffebee; border-left: 4px solid #f44336; color: #b71c1c;">
                <div class="info-card-title">অনুপস্থিতির হার</div>
                <div class="info-card-value">{{ $toBn(number_format($currentAbsentRate, 2)) }}%</div>
            </div>
            <div class="info-card" style="background-color: #fff8e1; border-left: 4px solid #ffc107; color: #ff6f00;">
                <div class="info-card-title">শাখাগুলোর মধ্যে অবস্থান</div>
                <div class="info-card-value">
                    @if($currentRank === '-')
                        তথ্য নেই
                    @else
                        {{ $toBn($totalSectionsCount) }} টির মধ্যে {{ $toBn($currentRank) }} তম
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Fix for Chrome flex-to-table print pagination bug -->
        <div style="clear: both; display: table; width: 100%;"></div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-roll">রোল</th>
                    <th rowspan="2" class="col-name">শিক্ষার্থীর নাম</th>
                    @foreach($dates as $d)
                        @php $dn = date('d', strtotime($d)); @endphp
                        <th>{{ $toBn($dn) }}</th>
                    @endforeach
                    <th rowspan="2" class="col-total">মোট উপ.</th>
                    <th rowspan="2" class="col-total">মোট অনু.</th>
                    <th rowspan="2" class="col-total">%</th>
                </tr>
                <tr>
                    @foreach($dates as $d)
                        @php $wdn = (int) date('N', strtotime($d)); @endphp
                        <th style="font-size:8px;">{{ $bnWeekdaysShort[$wdn] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            @php
                $firstChunkSize = 8;
                $otherChunkSize = 8;
                $studentChunks = [];
                $studentsArray = $students->all();
                if (count($studentsArray) > 0) {
                    $studentChunks[] = collect(array_slice($studentsArray, 0, $firstChunkSize));
                    $remaining = array_slice($studentsArray, $firstChunkSize);
                    foreach (array_chunk($remaining, $otherChunkSize) as $c) {
                        $studentChunks[] = collect($c);
                    }
                }
                $totalChunks = count($studentChunks);
                $chunkIdx = 0;
            @endphp
            @if($totalChunks === 0)
                <tbody>
                    <tr><td colspan="{{ 4 + count($dates) }}" style="text-align:center; padding:20px;">কোনো তথ্য পাওয়া যায়নি</td></tr>
                </tbody>
            @endif
            @foreach($studentChunks as $studentChunk)
            @php 
                $isFirstChunk = ($chunkIdx === 0);
                $isLastChunk = ($chunkIdx === $totalChunks - 1);
                $chunkIdx++;
            @endphp
            <tbody>
                @foreach($studentChunk as $index => $st)
                    @php 
                        $present = 0;
                        $absent = 0;
                        $total = 0; 
                        $isFirstInChunk = $loop->first;
                    @endphp
                    <tr>
                        <td>{{ $st->roll_no }}</td>
                        <td class="name-col">{{ $st->student_name_bn ?? $st->student_name_en }}</td>
                        @foreach($dates as $d)
                            @php
                                $status = $attendanceMatrix[$st->student_id][$d] ?? null;
                                $wdnLoop = (int) date('N', strtotime($d));
                                $isHolidayDate = in_array($d, $holidayDates ?? []);
                                $isWeeklyHoliday = in_array($wdnLoop, $weeklyHolidayNums ?? []);
                                $isHoliday = $isHolidayDate || $isWeeklyHoliday;
                            @endphp
                            
                            @if($isHoliday)
                                @if($isFirstInChunk)
                                    @php
                                        $holidayName = 'ছুটি';
                                        if ($isHolidayDate) {
                                            $holidayName = $holidaysData[$d]->title ?? 'ছুটি';
                                        } elseif ($isWeeklyHoliday) {
                                            $holidayName = 'সাপ্তাহিক ছুটি';
                                        }
                                        $bTop = $isFirstChunk ? '1px solid #333' : 'none';
                                        $bBottom = $isLastChunk ? '1px solid #333' : 'none';
                                    @endphp
                                    <td rowspan="{{ $studentChunk->count() }}" style="vertical-align: middle; text-align: center; padding: 0; background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border-top: {{ $bTop }} !important; border-bottom: {{ $bBottom }} !important;">
                                        <div style="writing-mode: vertical-rl; transform: rotate(180deg); margin: 0 auto; font-size: 11px; color: #d32f2f; letter-spacing: 1px; white-space: nowrap;">
                                            {{ $holidayName }}
                                        </div>
                                    </td>
                                @endif
                            @else
                                @php
                                    $total++;
                                    if (in_array($status, ['present', 'late', 'half_day'])) {
                                        $present++;
                                    } else {
                                        $absent++;
                                        $status = 'absent';
                                    }
                                @endphp
                                <td>
                                    @if($status === 'present')<span class="present-icon">&#10003;</span>
                                    @elseif($status === 'absent')<span class="absent-icon">&#10007;</span>
                                    @elseif($status === 'late')<span class="late-icon">&#9881;</span>
                                    @elseif($status === 'half_day')<span class="half-day-icon">&#9679;</span>
                                    @else &nbsp; @endif
                                </td>
                            @endif
                        @endforeach
                        <td>{{ $toBn($present) }}</td>
                        <td>{{ $toBn($absent) }}</td>
                        <td>
                            @if($total > 0) {{ $toBn(number_format(($present / $total) * 100, 2)) }}% @else — @endif
                        </td>
                    </tr>
                @endforeach
                
                {{-- Footer Rows: appended to the very last chunk's tbody to prevent orphaned pages --}}
                @if($isLastChunk && !empty($dates))
                    <tr style="font-weight:700; background:#f8f9fa;">
                        <td colspan="2" style="text-align:right;">দৈনিক মোট উপস্থিতি</td>
                        @foreach($dates as $d)
                            @php $wdnTmp = (int) date('N', strtotime($d));
                            $isHoliday = in_array($d, $holidayDates ?? []) || in_array($wdnTmp, $weeklyHolidayNums ?? []); @endphp
                            @if($isHoliday)
                                <td class="text-muted" style="font-size:8px;">ছুটি</td>
                            @else
                                <td class="text-success">{{ $toBn($dailyPresent[$d] ?? 0) }}</td>
                            @endif
                        @endforeach
                        <td class="text-success">{{ $toBn($sumPresent) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr style="font-weight:700; background:#f8f9fa;">
                        <td colspan="2" style="text-align:right;">দৈনিক মোট অনুপস্থিতি</td>
                        @foreach($dates as $d)
                            @php $wdnTmp = (int) date('N', strtotime($d));
                            $isHoliday = in_array($d, $holidayDates ?? []) || in_array($wdnTmp, $weeklyHolidayNums ?? []); @endphp
                            @if($isHoliday)
                                <td class="text-muted" style="font-size:8px;">ছুটি</td>
                            @else
                                <td class="text-danger">{{ $toBn($dailyAbsent[$d] ?? 0) }}</td>
                            @endif
                        @endforeach
                        <td class="text-danger">{{ $toBn($sumAbsent) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
            @endforeach
        </table>
    </div>

    <div class="print-only" style="text-align:right; margin-top:8px; font-size:11px;">প্রিন্ট:
        {{ $toBn(now()->format('d-m-Y h:i A')) }}</div>
@endsection

@push('scripts')
    <script>
        // Auto open print dialog when the dedicated print view loads
        window.addEventListener('load', function () {
            try { setTimeout(function () { window.print(); }, 250); } catch (e) { }
        });
        // Optionally close after print (commented out to avoid unexpected closes)
        // window.addEventListener('afterprint', function(){ window.close(); });
    </script>
@endpush