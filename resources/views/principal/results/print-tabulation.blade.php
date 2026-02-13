@extends('layouts.admin')

@section('title', ' Tabulation Sheet')

@section('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { display: none !important; }
        .content-header { display: none !important; }
        .main-footer { display: none !important; }
        .content-wrapper { background: white !important; }
        .table { width: 100% !important; margin-bottom: 0 !important; background-color: transparent !important; }
        .table-bordered th, .table-bordered td { border: 1px solid #000 !important; }
        
        /* Ensure table fits on page */
        body { margin: 0; padding: 0; font-size: 10pt; }
        @page { size: landscape; margin: 10mm; }
    }
    .table-sm th, .table-sm td { padding: 0.2rem; }
    
    /* Sticky Name Column */
    .table-responsive { max-height: 80vh; overflow-y: auto; overflow-x: auto; }
</style>
@endsection

@section('content')
<div class="content-header no-print">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tabulation Sheet (Print)</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tabulation Print</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header no-print">
                <h3 class="card-title">Print Preview</h3>
                <div class="card-tools">
                    <button onclick="window.print()" class="btn btn-primary btn-sm">
                        <i class="fas fa-print"></i> Print Now
                    </button>
                    <button onclick="window.close()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Print Header -->
                <div class="text-center mb-4 d-none d-print-block" style="display: none;">
                    <h2>{{ $school->name }}</h2>
                    <h4>Tabulation Sheet</h4>
                    <h5>
                        Exam: {{ $exam->name }} | 
                        Class: {{ $class->name }} 
                        @if(request('section_id'))
                            @php $sec = \App\Models\Section::find(request('section_id')); @endphp
                            | Section: {{ $sec->name ?? $sec->section_name }}
                        @endif
                        | Year: {{ $exam->academicYear->name }}
                    </h5>
                </div>
                
                <!-- On screen header -->
                 <div class="text-center mb-4 d-print-none">
                    <h2>{{ $school->name }}</h2>
                    <h4>Tabulation Sheet</h4>
                    <h5>
                        Exam: {{ $exam->name }} | 
                        Class: {{ $class->name }}
                        @if(request('section_id'))
                            @php $sec = \App\Models\Section::find(request('section_id')); @endphp
                             | Section: {{ $sec->name ?? $sec->section_name }}
                        @endif 
                        | Year: {{ $exam->academicYear->name }}
                    </h5>
                </div>

                @if($results->count() > 0)
                    <div class="table-responsive">
                       <table class="table table-bordered table-sm" style="font-size: 11px;">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 40px;">SL</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">ID</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 50px;">Roll</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Section</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 150px;">Name</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">Group</th>
                                    @foreach($finalSubjects as $key => $subject)
                                        @php
                                            $parts = [];
                                            if($subject['creative_full_mark'] > 0) $parts[] = 'creative';
                                            if($subject['mcq_full_mark'] > 0) $parts[] = 'mcq';
                                            if($subject['practical_full_mark'] > 0) $parts[] = 'practical';
                                            $parts[] = 'total';
                                            $parts[] = 'gpa';
                                            $colspan = count($parts);
                                        @endphp
                                        <th colspan="{{ $colspan }}" class="text-center">
                                            {{ $subject['name'] }}<br>
                                            <small>({{ $subject['total_full_mark'] }})</small>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">Opt. Sub</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 50px;">Total</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 40px;">GPA</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 40px;">Grd</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">Status</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 40px;">Fail</th>
                                </tr>
                                <tr>
                                    @foreach($finalSubjects as $key => $subject)
                                        @if($subject['creative_full_mark'] > 0)
                                            <th class="text-center">CQ</th>
                                        @endif
                                        @if($subject['mcq_full_mark'] > 0)
                                            <th class="text-center">MCQ</th>
                                        @endif
                                        @if($subject['practical_full_mark'] > 0)
                                            <th class="text-center">Pr</th>
                                        @endif
                                        <th class="text-center">Tot</th>
                                        <th class="text-center">GPA</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $result->student->student_id ?? '-' }}</td>
                                        <td class="text-center">{{ optional(optional($result->student)->currentEnrollment)->roll_no ?? '-' }}</td>
                                        <td class="text-center">{{ optional(optional($result->student)->currentEnrollment)->section->name ?? '-' }}</td>
                                        <td>
                                            <strong>{{ $result->student->student_name_en ?: ($result->student->student_name_bn ?: 'Unknown') }}</strong>
                                        </td>
                                        <td class="text-center">{{ optional(optional($result->student)->currentEnrollment)->group->name ?? '-' }}</td>

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
                                            @endphp

                                            @if($subject['creative_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ $isNR ? '-' : ($isAbsent ? 'Ab' : $creative) }}</td>
                                            @endif
                                            @if($subject['mcq_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ $isNR ? '-' : ($isAbsent ? 'Ab' : $mcq) }}</td>
                                            @endif
                                            @if($subject['practical_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ $isNR ? '-' : ($isAbsent ? 'Ab' : $practical) }}</td>
                                            @endif

                                            <td class="text-center font-weight-bold">
                                                {{ $isNR ? '-' : ($isAbsent ? 'Ab' : $total) }}
                                            </td>
                                            <td class="text-center">
                                                @if($isNR || !empty($resData['display_only']))
                                                    <span class="text-muted">-</span>
                                                @else
                                                    {{ number_format($gpa, 2) }}
                                                @endif
                                            </td>
                                        @endforeach

                                        <td class="text-center">{{ $result->fourth_subject_code ?? '-' }}</td>
                                        <td class="text-center"><strong>{{ number_format($result->computed_total_marks ?? $result->total_marks ?? 0, 0) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($result->computed_gpa ?? $result->gpa ?? 0, 2) }}</strong></td>
                                        <td class="text-center">
                                            @php $letter = $result->computed_letter ?? $result->letter_grade; @endphp
                                            {{ $letter ?: 'F' }}
                                        </td>
                                        <td class="text-center">
                                            @php $status = $result->computed_status ?? ($result->result_status == 'passed' ? 'Passed' : 'Failed'); @endphp
                                            {{ $status }}
                                        </td>
                                        <td class="text-center font-weight-bold text-danger">
                                            {{ $result->fail_count > 0 ? $result->fail_count : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Print Footer -->
                    <div class="row mt-5 d-none d-print-flex" style="display: none;">
                        <div class="col-4 text-center">
                            <p class="border-top w-50 mx-auto pt-2">Prepared By</p>
                        </div>
                        <div class="col-4 text-center">
                            <p class="border-top w-50 mx-auto pt-2">Checked By</p>
                        </div>
                        <div class="col-4 text-center">
                            <p class="border-top w-50 mx-auto pt-2">Principal</p>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        No results found for print.
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<script>
    // Force CSS display block for print elements via JS if needed, though media queries should handle it.
    window.onbeforeprint = function() {
        document.querySelectorAll('.d-print-block, .d-print-flex').forEach(el => el.style.display = 'block');
        // flex special case
        document.querySelectorAll('.d-print-flex').forEach(el => el.style.display = 'flex');
    };
    window.onafterprint = function() {
        document.querySelectorAll('.d-print-block, .d-print-flex').forEach(el => el.style.display = 'none');
    };
</script>
@endsection
