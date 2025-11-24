@extends('layouts.admin')

@section('title', 'মেধা তালিকা - ' . $exam->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">মেধা তালিকা</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.results.marksheet', $school) }}">ফলাফল</a></li>
                    <li class="breadcrumb-item active">মেধা তালিকা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title">{{ $exam->name }} - {{ $exam->class->name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.results.merit-list.print', [$school, $exam->id]) }}?class_id={{ request('class_id') }}&section_id={{ request('section_id') }}&limit={{ request('limit', 50) }}" 
                       class="btn btn-sm btn-light" 
                       target="_blank">
                        <i class="fas fa-print"></i> প্রিন্ট করুন
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('principal.institute.results.merit-list', [$school, $exam->id]) }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- সকল শ্রেণি --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>শাখা</label>
                                <select name="section_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- সকল শাখা --</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                            {{ $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>সীমা</label>
                                <select name="limit" class="form-control" onchange="this.form.submit()">
                                    <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>শীর্ষ ১০</option>
                                    <option value="20" {{ request('limit') == 20 ? 'selected' : '' }}>শীর্ষ ২০</option>
                                    <option value="50" {{ request('limit', 50) == 50 ? 'selected' : '' }}>শীর্ষ ৫০</option>
                                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>শীর্ষ ১০০</option>
                                    <option value="">সকল</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                @if($results->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="8%">মেধা স্থান</th>
                                    <th width="10%">রোল</th>
                                    <th>শিক্ষার্থীর নাম</th>
                                    <th width="12%">শ্রেণি</th>
                                    <th width="10%">মোট নম্বর</th>
                                    <th width="8%">GPA</th>
                                    <th width="8%">গ্রেড</th>
                                    <th width="10%">ফলাফল</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    <tr>
                                        <td class="text-center">
                                            @if($result->merit_position_class <= 3)
                                                <strong class="text-{{ $result->merit_position_class == 1 ? 'warning' : ($result->merit_position_class == 2 ? 'info' : 'success') }}">
                                                    <i class="fas fa-trophy"></i> {{ $result->merit_position_class }}
                                                </strong>
                                            @else
                                                {{ $result->merit_position_class }}
                                            @endif
                                        </td>
                                        <td>{{ $result->student->student_id }}</td>
                                        <td>
                                            <strong>{{ $result->student->student_name_en }}</strong>
                                            @if($result->student->student_name_bn)
                                                <br><small class="text-muted">{{ $result->student->student_name_bn }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $result->class->name }} @if($result->section) - {{ $result->section->section_name }} @endif</td>
                                        <td class="text-center"><strong>{{ number_format($result->total_marks, 2) }}</strong></td>
                                        <td class="text-center">
                                            <strong class="text-primary">{{ number_format($result->gpa, 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $result->letter_grade == 'A+' ? 'success' : ($result->letter_grade == 'F' ? 'danger' : 'info') }}">
                                                {{ $result->letter_grade }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($result->result_status == 'passed')
                                                <span class="badge badge-success">উত্তীর্ণ</span>
                                            @else
                                                <span class="badge badge-danger">অনুত্তীর্ণ</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">মোট উত্তীর্ণ</span>
                                    <span class="info-box-number">{{ $results->where('result_status', 'passed')->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">মোট অনুত্তীর্ণ</span>
                                    <span class="info-box-number">{{ $results->where('result_status', 'failed')->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-percent"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">পাশের হার</span>
                                    <span class="info-box-number">
                                        {{ $results->count() > 0 ? round(($results->where('result_status', 'passed')->count() / $results->count()) * 100, 2) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">মোট পরীক্ষার্থী</span>
                                    <span class="info-box-number">{{ $results->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> কোনো ফলাফল পাওয়া যায়নি। 
                        <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}" class="alert-link">নম্বর Entry করুন</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
