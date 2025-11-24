@extends('layouts.admin')

@section('title', 'পরিসংখ্যান - ' . $exam->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">পরীক্ষার পরিসংখ্যান</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.results.marksheet', $school) }}">ফলাফল</a></li>
                    <li class="breadcrumb-item active">পরিসংখ্যান</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Exam Info -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">{{ $exam->name }} - {{ $exam->class->name }}</h3>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">মোট পরীক্ষার্থী</span>
                        <span class="info-box-number">{{ $totalStudents }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">উত্তীর্ণ</span>
                        <span class="info-box-number">{{ $passedStudents }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $totalStudents > 0 ? ($passedStudents / $totalStudents * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $totalStudents > 0 ? round($passedStudents / $totalStudents * 100, 2) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">অনুত্তীর্ণ</span>
                        <span class="info-box-number">{{ $failedStudents }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $totalStudents > 0 ? ($failedStudents / $totalStudents * 100) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $totalStudents > 0 ? round($failedStudents / $totalStudents * 100, 2) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">গড় GPA</span>
                        <span class="info-box-number">{{ $averageGPA }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Distribution -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">গ্রেড বিতরণ</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($gradeDistribution as $grade => $count)
                        @php
                            $percentage = $totalStudents > 0 ? round($count / $totalStudents * 100, 2) : 0;
                            $badgeColor = match($grade) {
                                'A+' => 'success',
                                'A' => 'primary',
                                'A-' => 'info',
                                'B' => 'secondary',
                                'C' => 'warning',
                                'D' => 'light',
                                'F' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-{{ $badgeColor }}">
                                    <i class="fas fa-award"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">গ্রেড {{ $grade }}</span>
                                    <span class="info-box-number">{{ $count }}</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-{{ $badgeColor }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="progress-description">{{ $percentage }}%</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Subject-wise Statistics -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">বিষয়ভিত্তিক পরিসংখ্যান</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>বিষয়</th>
                                <th>পূর্ণমান</th>
                                <th>সর্বোচ্চ</th>
                                <th>সর্বনিম্ন</th>
                                <th>গড়</th>
                                <th>পাসের হার</th>
                                <th>উত্তীর্ণ</th>
                                <th>অনুত্তীর্ণ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjectStats as $stat)
                                <tr>
                                    <td><strong>{{ $stat['subject'] }}</strong></td>
                                    <td>{{ $stat['full_marks'] }}</td>
                                    <td class="text-success"><strong>{{ $stat['highest'] }}</strong></td>
                                    <td class="text-danger">{{ $stat['lowest'] }}</td>
                                    <td>{{ $stat['average'] }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: {{ $stat['pass_rate'] }}%">
                                                {{ $stat['pass_rate'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-success">{{ $stat['passed'] }}</td>
                                    <td class="text-danger">{{ $stat['failed'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        @if($topPerformers->count() > 0)
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title">শীর্ষ ১০ মেধাবী</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="10%">স্থান</th>
                                    <th width="15%">রোল</th>
                                    <th>নাম</th>
                                    <th width="15%">মোট নম্বর</th>
                                    <th width="12%">GPA</th>
                                    <th width="12%">গ্রেড</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topPerformers as $result)
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
                                        <td><strong>{{ $result->student->student_name_en }}</strong></td>
                                        <td class="text-center">{{ number_format($result->total_marks, 2) }}</td>
                                        <td class="text-center"><strong class="text-primary">{{ number_format($result->gpa, 2) }}</strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-success">{{ $result->letter_grade }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
