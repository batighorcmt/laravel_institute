@extends('layouts.print')

@section('title', $printTitle)

@push('print_head')
<style>
    @page { size: legal landscape; margin: 5mm 10mm; }
    
    .table-tabulation { width: 100% !important; border-collapse: collapse !important; table-layout: fixed; }
    .table-tabulation th, .table-tabulation td { 
        border: 1px solid #000 !important; 
        padding: 1px 2px !important; 
        font-size: 8.5pt !important; 
        line-height: 1.1 !important;
        vertical-align: middle !important;
    }
    .table-tabulation thead th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
    
    .text-center { text-align: center !important; }
    .font-weight-bold { font-weight: bold !important; }
    .st-name { font-size: 9pt !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Subject Rotation if needed? For now let's keep it compact */
    .sub-title { font-size: 7.5pt !important; font-weight: 700; height: 60px; }
    
    .fail-count { color: #d32f2f !important; font-weight: 800; }
    
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="print-content">
    @if($results->count() > 0)
        <table class="table-tabulation">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 25px;">{{ $lang === 'bn' ? 'ক্র:' : 'SL' }}</th>
                    <th rowspan="2" style="width: 65px;">{{ $lang === 'bn' ? 'আইডি' : 'ID' }}</th>
                    <th rowspan="2" style="width: 35px;">{{ $lang === 'bn' ? 'রোল' : 'Roll' }}</th>
                    <th rowspan="2" style="width: 140px;">{{ $lang === 'bn' ? 'নাম' : 'Name' }}</th>
                    
                    @foreach($finalSubjects as $key => $subject)
                        @php
                            $parts = [];
                            if($subject['creative_full_mark'] > 0) $parts[] = 'c';
                            if($subject['mcq_full_mark'] > 0) $parts[] = 'm';
                            if($subject['practical_full_mark'] > 0) $parts[] = 'p';
                            $parts[] = 't';
                            $parts[] = 'g';
                            $colspan = count($parts);
                        @endphp
                        <th colspan="{{ $colspan }}" class="text-center">
                            <div class="sub-title">
                                {{ $subject['name'] }}<br>
                                <small>({{ $subject['total_full_mark'] }})</small>
                            </div>
                        </th>
                    @endforeach
                    
                    <th rowspan="2" style="width: 35px;">{{ $lang === 'bn' ? 'ঐচ্ছিক বিষয়' : 'Optional' }}</th>
                    <th rowspan="2" style="width: 45px;">{{ $lang === 'bn' ? 'মোট' : 'Total' }}</th>
                    <th rowspan="2" style="width: 35px;">{{ $lang === 'bn' ? 'জিপিএ' : 'GPA' }}</th>
                    <th rowspan="2" style="width: 25px;">{{ $lang === 'bn' ? 'গ্রেড' : 'Grd' }}</th>
                    <th rowspan="2" style="width: 45px;">{{ $lang === 'bn' ? 'অবস্থা' : 'Stat' }}</th>
                    <th rowspan="2" style="width: 25px;">{{ $lang === 'bn' ? 'ফেল' : 'Fail' }}</th>
                </tr>
                <tr>
                    @foreach($finalSubjects as $key => $subject)
                        @if($subject['creative_full_mark'] > 0) <th class="text-center">CQ</th> @endif
                        @if($subject['mcq_full_mark'] > 0) <th class="text-center">MQ</th> @endif
                        @if($subject['practical_full_mark'] > 0) <th class="text-center">PR</th> @endif
                        <th class="text-center">T</th>
                        <th class="text-center">G</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    @php 
                        $st = $result->student;
                        $enroll = $st->currentEnrollment;
                        $stName = $lang === 'bn' ? ($st->student_name_bn ?: $st->student_name_en) : ($st->student_name_en ?: $st->student_name_bn);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ $st->student_id }}</td>
                        <td class="text-center">{{ $enroll->roll_no ?? '-' }}</td>
                        <td class="st-name">{{ $stName }}</td>

                        @foreach($finalSubjects as $key => $subject)
                            @php
                                $resData = $result->subject_results->get($key);
                                $grade = $resData['grade'] ?? '-';
                                $gpa = $resData['gpa'] ?? 0;
                                $total = $resData['total'] ?? 0;
                                $creative = $resData['creative'] ?? 0;
                                $mcq = $resData['mcq'] ?? 0;
                                $practical = $resData['practical'] ?? 0;
                                $isNR = ($grade === 'N/R');
                                $isAbsent = $resData['is_absent'] ?? false;
                                $isNA = !empty($resData['is_not_applicable']);
                            @endphp

                            @if($subject['creative_full_mark'] > 0)
                                <td class="text-center">{{ $isNA ? '' : ($isNR ? '-' : ($isAbsent ? 'Ab' : $creative)) }}</td>
                            @endif
                            @if($subject['mcq_full_mark'] > 0)
                                <td class="text-center">{{ $isNA ? '' : ($isNR ? '-' : ($isAbsent ? 'Ab' : $mcq)) }}</td>
                            @endif
                            @if($subject['practical_full_mark'] > 0)
                                <td class="text-center">{{ $isNA ? '' : ($isNR ? '-' : ($isAbsent ? 'Ab' : $practical)) }}</td>
                            @endif

                            <td class="text-center font-weight-bold">
                                {{ $isNA ? '' : ($isNR ? '-' : ($isAbsent ? 'Ab' : $total)) }}
                            </td>
                            <td class="text-center">
                                @if($isNA || $isNR || !empty($resData['display_only']))
                                    {{-- Empty/Dash --}}
                                @else
                                    {{ number_format($gpa, 2) }}
                                @endif
                            </td>
                        @endforeach

                        <td class="text-center small">{{ $result->fourth_subject_code ?? '-' }}</td>
                        <td class="text-center"><strong>{{ number_format($result->computed_total_marks ?? 0, 0) }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($result->computed_gpa ?? 0, 2) }}</strong></td>
                        <td class="text-center">
                            @php $letter = $result->computed_letter ?? 'F'; @endphp
                            <strong>{{ $letter }}</strong>
                        </td>
                        <td class="text-center small">
                            @php 
                                $status = $result->computed_status;
                                if ($lang === 'bn') {
                                    $status = ($status === 'অকৃতকার্য' || $letter === 'F') ? 'ফেল' : 'পাস';
                                } else {
                                    $status = ($status === 'অকৃতকার্য' || $letter === 'F') ? 'Failed' : 'Passed';
                                }
                            @endphp
                            {{ $status }}
                        </td>
                        <td class="text-center fail-count">
                            {{ $result->fail_count > 0 ? $result->fail_count : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures -->
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding: 0 40px;">
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'শ্রেণি শিক্ষক' : 'Class Teacher' }}
            </div>
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'যাচাইকারী' : 'Verified By' }}
            </div>
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'প্রধান শিক্ষক' : 'Principal' }}
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            {{ $lang === 'bn' ? 'কোন তথ্য পাওয়া যায়নি।' : 'No results found.' }}
        </div>
    @endif
</div>
@endsection
