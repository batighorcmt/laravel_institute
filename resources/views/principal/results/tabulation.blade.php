@extends('layouts.admin')

@section('title', 'ট্যাবুলেশন শিট' . (optional($exam)->name ? ' - ' . optional($exam)->name : ''))

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">ট্যাবুলেশন শিট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.results.marksheet', $school) }}">ফলাফল</a></li>
                    <li class="breadcrumb-item active">ট্যাবুলেশন শিট</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        @php 
                            $eName = optional($exam)->name_bn ?? optional($exam)->name ?? 'ট্যাবুলেশন শীট';
                            $cName = optional(optional($exam)->class)->name_bn ?? optional(optional($exam)->class)->name;
                        @endphp
                        {{ $eName }} @if($cName) - {{ $cName }} @endif
                    </h3>
                    <div class="card-tools">
                        @php $classIdForPrint = $class->id ?? request('class_id'); @endphp
                        @if($exam && $classIdForPrint)
                            <a href="{{ route('principal.institute.results.tabulation.print', [$school, $exam->id, $classIdForPrint]) }}?section_id={{ request('section_id') }}"
                               class="btn btn-sm btn-light"
                               target="_blank">
                                <i class="fas fa-print"></i> প্রিন্ট করুন
                            </a>
                        @endif
                    </div>
                </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('principal.institute.results.tabulation', $school) }}" class="mb-3" id="tabulation-filter">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শিক্ষাবর্ষ</label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control">
                                    <option value="">-- শিক্ষাবর্ষ নির্বাচন করুন --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" id="class_id" class="form-control">
                                    <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                                    @foreach($classes as $cls)
                                        <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                                            {{ $cls->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>শাখা</label>
                                <select name="section_id" id="section_id" class="form-control">
                                    <option value="">-- (সব শাখা) --</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                            {{ $section->name ?? $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>পরীক্ষা</label>
                                <select name="exam_id" id="exam_id" class="form-control">
                                    <option value="">-- পরীক্ষা নির্বাচন করুন --</option>
                                    @foreach($exams as $examItem)
                                        <option value="{{ $examItem->id }}" {{ request('exam_id') == $examItem->id ? 'selected' : '' }}>
                                            {{ $examItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if($results->count() > 0 && $exam && $exam->examSubjects->count() > 0)
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-bordered table-sm" style="font-size: 13px;">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 50px;">Serial</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Student ID</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Roll</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 100px;">Section</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 200px;">Name</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Group</th>
                                    @foreach($finalSubjects as $key => $subject)
                                        @php
                                            $parts = [];
                                            if($subject['creative_full_mark'] > 0) $parts[] = 'creative';
                                            if($subject['mcq_full_mark'] > 0) $parts[] = 'mcq';
                                            if($subject['practical_full_mark'] > 0) $parts[] = 'practical';
                                            // always include total and gpa
                                            $parts[] = 'total';
                                            $parts[] = 'gpa';
                                            $colspan = count($parts);
                                        @endphp
                                        <th colspan="{{ $colspan }}" class="text-center" style="min-width: 120px;">
                                            {{ $subject['name'] }}<br>
                                            <small>({{ $subject['total_full_mark'] ?? '-' }})</small>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="align-middle text-center" style="width: 120px;">Optional Subject</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Total</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">GPA</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">Grade</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">Status</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">Fail Count</th>
                                </tr>
                                <tr>
                                    @foreach($finalSubjects as $key => $subject)
                                        @if($subject['creative_full_mark'] > 0)
                                            <th class="text-center" style="width: 50px;">CQ</th>
                                        @endif
                                        @if($subject['mcq_full_mark'] > 0)
                                            <th class="text-center" style="width: 50px;">MCQ</th>
                                        @endif
                                        @if($subject['practical_full_mark'] > 0)
                                            <th class="text-center" style="width: 50px;">Prac</th>
                                        @endif
                                        <th class="text-center" style="width: 50px;">Total</th>
                                        <th class="text-center" style="width: 40px;">GPA</th>
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
                                                $grade = $resData['grade'] ?? '';
                                                $gpa = $resData['gpa'] ?? '';
                                                $total = $resData['total'] ?? '';
                                                $creative = $resData['creative'] ?? '';
                                                $mcq = $resData['mcq'] ?? '';
                                                $practical = $resData['practical'] ?? '';
                                                
                                                // Check for absent/NR to style appropriately if needed
                                                $isNR = ($grade === 'N/R' || ($grade === '' && empty($resData['display_only'])));
                                                $isAbsent = $resData['is_absent'] ?? false;
                                                $isNA = !empty($resData['is_not_applicable']);
                                            @endphp

                                            @if($subject['creative_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : $creative) }}</td>
                                            @endif
                                            @if($subject['mcq_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : $mcq) }}</td>
                                            @endif
                                            @if($subject['practical_full_mark'] > 0)
                                                <td class="text-center text-muted">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : $practical) }}</td>
                                            @endif

                                            <td class="text-center font-weight-bold">
                                                {{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : $total) }}
                                            </td>
                                            <td class="text-center">
                                                @if($isNA || $isNR || !empty($resData['display_only']))
                                                    {{-- Empty --}}
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
                                            @if($letter)
                                                <span class="badge badge-{{ $letter == 'A+' ? 'success' : ($letter == 'F' ? 'danger' : 'info') }}">{{ $letter }}</span>
                                            @else
                                                <span class="badge badge-danger">F</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php $status = $result->computed_status ?? ($result->result_status == 'passed' ? 'Passed' : 'Failed'); @endphp
                                            @if($status == 'উত্তীর্ণ' || $status == 'Passed')
                                                <span class="badge badge-success">Passed</span>
                                            @elseif($status == 'অনুপস্থিত')
                                                <span class="badge badge-warning">Absent</span>
                                            @elseif($status == 'অকৃতকার্য' || $status == 'Failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold text-danger">
                                            {{ $result->fail_count > 0 ? $result->fail_count : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(request('class_id'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> নির্বাচিত ফিল্টারের জন্য কোনো ফলাফল পাওয়া যায়নি।
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> অনুগ্রহ করে শ্রেণি নির্বাচন করুন।
                    </div>
                @endif
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const examsUrl = "{{ route('principal.institute.results.exams-by-year', $school) }}";
    const sectionsUrl = "{{ route('principal.institute.results.sections-by-class', $school) }}";

    const examSelect = document.getElementById('exam_id');
    const yearSelect = document.getElementById('academic_year_id');
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    function clearSelect(sel, placeholder){
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
    }

    function loadExams(yearId, classId, selected){
        clearSelect(examSelect, '-- পরীক্ষা নির্বাচন করুন --');
        if(!yearId || !classId) return;
        fetch(examsUrl + '?academic_year_id=' + encodeURIComponent(yearId) + '&class_id=' + encodeURIComponent(classId))
            .then(r => r.json())
            .then(data => {
                data.forEach(function(e){
                    const opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.name;
                    if(selected && String(selected) === String(e.id)) opt.selected = true;
                    examSelect.appendChild(opt);
                });
            }).catch(()=>{});
    }

    function loadSections(classId, selected){
        clearSelect(sectionSelect, '-- (সব শাখা) --');
        if(!classId) return;
        fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId))
            .then(r => r.json())
            .then(data => {
                data.forEach(function(s){
                    const name = s.name || s.section_name || ('Section ' + s.id);
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = name;
                    if(selected && String(selected) === String(s.id)) opt.selected = true;
                    sectionSelect.appendChild(opt);
                });
            }).catch(()=>{});
    }

    // initialize if values present
    const initYear = yearSelect ? yearSelect.value : '';
    const initClass = classSelect ? classSelect.value : '';
    const initExam = '{{ request("exam_id") }}';
    const initSection = '{{ request("section_id") }}';

    if(initYear && initClass) loadExams(initYear, initClass, initExam);
    if(initClass) loadSections(initClass, initSection);

    if(yearSelect){
        yearSelect.addEventListener('change', function(){ 
            loadExams(this.value, classSelect ? classSelect.value : '', null); 
        });
    }
    if(classSelect){
        classSelect.addEventListener('change', function(){ 
            loadSections(this.value, null);
            loadExams(yearSelect ? yearSelect.value : '', this.value, null);
        });
    }
});
</script>
@endpush
            </div>
        </div>
    </div>
</section>
@endsection
