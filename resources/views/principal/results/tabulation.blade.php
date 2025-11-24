@extends('layouts.admin')

@section('title', 'ট্যাবুলেশন শিট - ' . $exam->name)

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
                <h3 class="card-title">{{ $exam->name }} - {{ $exam->class->name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.results.tabulation.print', [$school, $exam->id]) }}?class_id={{ request('class_id') }}&section_id={{ request('section_id') }}" 
                       class="btn btn-sm btn-light" 
                       target="_blank">
                        <i class="fas fa-print"></i> প্রিন্ট করুন
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('principal.institute.results.tabulation', [$school, $exam->id]) }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label>শাখা</label>
                                <select name="section_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- শাখা নির্বাচন করুন --</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                            {{ $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> দেখুন
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if($results->count() > 0 && $exam->examSubjects->count() > 0)
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-bordered table-sm" style="font-size: 11px;">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 50px;">ক্রমিক</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">রোল</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 200px;">শিক্ষার্থীর নাম</th>
                                    @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                        <th colspan="2" class="text-center" style="min-width: 80px;">
                                            {{ $examSubject->subject->name }}<br>
                                            <small>({{ $examSubject->total_full_mark }})</small>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">মোট</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">GPA</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">গ্রেড</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">মেধা<br>স্থান</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">ফলাফল</th>
                                </tr>
                                <tr>
                                    @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                        <th class="text-center" style="width: 50px;">নম্বর</th>
                                        <th class="text-center" style="width: 40px;">গ্রেড</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    @php
                                        $studentMarks = $marks->where('student_id', $result->student_id)->keyBy('exam_subject_id');
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $result->student->student_id }}</td>
                                        <td>{{ $result->student->student_name_en }}</td>
                                        
                                        @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                            @php
                                                $mark = $studentMarks->get($examSubject->id);
                                            @endphp
                                            <td class="text-center">
                                                @if($mark)
                                                    @if($mark->is_absent)
                                                        <span class="text-danger">Ab</span>
                                                    @else
                                                        {{ number_format($mark->total_marks, 0) }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($mark && !$mark->is_absent)
                                                    <span class="badge badge-{{ $mark->letter_grade == 'F' ? 'danger' : 'info' }}" style="font-size: 9px;">
                                                        {{ $mark->letter_grade }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td class="text-center"><strong>{{ number_format($result->total_marks, 0) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($result->gpa, 2) }}</strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $result->letter_grade == 'A+' ? 'success' : ($result->letter_grade == 'F' ? 'danger' : 'info') }}">
                                                {{ $result->letter_grade }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($result->result_status == 'passed')
                                                <strong>{{ $result->merit_position_class }}</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($result->result_status == 'passed')
                                                <span class="badge badge-success" style="font-size: 9px;">উত্তীর্ণ</span>
                                            @else
                                                <span class="badge badge-danger" style="font-size: 9px;">অনুত্তীর্ণ</span>
                                            @endif
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
            </div>
        </div>
    </div>
</section>
@endsection
