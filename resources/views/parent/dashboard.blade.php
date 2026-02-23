@extends('layouts.admin')
@section('title', 'অভিভাবক/শিক্ষার্থী ড্যাশবোর্ড')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-tachometer-alt mr-2"></i> ড্যাশবোর্ড</h1>
            </div>
            <div class="col-sm-6">
                @if($children->count() > 1)
                <form action="{{ route('parent.dashboard') }}" method="GET" class="float-sm-right">
                    <div class="input-group">
                        <select name="student_id" class="form-control" onchange="this.form.submit()">
                            @foreach($children as $child)
                            <option value="{{ $child->id }}" {{ $selectedStudent->id == $child->id ? 'selected' : '' }}>
                                {{ $child->student_name_en }} ({{ $child->student_id }})
                            </option>
                            @endforeach
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">দেখুন</button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@if(!$selectedStudent)
<div class="alert alert-warning">কোনো শিক্ষার্থীর তথ্য পাওয়া যায়নি।</div>
@else
<div class="row">
    <!-- প্রোফাইল কার্ড -->
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle"
                         src="{{ $selectedStudent->photo ? asset('storage/'.$selectedStudent->photo) : asset('images/default-student.png') }}"
                         alt="Student profile picture">
                </div>
                <h3 class="profile-username text-center">{{ $selectedStudent->student_name_en }}</h3>
                <p class="text-muted text-center">ID: {{ $selectedStudent->student_id }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>শ্রেণী</b> <a class="float-right">{{ $selectedStudent->enrollments()->latest()->first()?->class?->name ?? 'N/A' }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>সেকশন</b> <a class="float-right">{{ $selectedStudent->enrollments()->latest()->first()?->section?->name ?? 'N/A' }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>রোল</b> <a class="float-right">{{ $selectedStudent->enrollments()->latest()->first()?->roll_no ?? 'N/A' }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="row">
            <!-- হাজিরা পরিসংখ্যান -->
            <div class="col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $attendanceStats['present'] }} দিন</h3>
                        <p>মোট উপস্থিতি (এই শিক্ষাবর্ষে)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $attendanceStats['absent'] }} দিন</h3>
                        <p>মোট অনুপস্থিতি (এই শিক্ষাবর্ষে)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ফলাফল কার্ড -->
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> সর্বশেষ পরীক্ষার ফলাফল</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>পরীক্ষা</th>
                            <th>GPA</th>
                            <th>গ্রেড</th>
                            <th>প্রকাশের তারিখ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestResults as $result)
                        <tr>
                            <td>{{ $result->exam->name }}</td>
                            <td>{{ $result->gpa }}</td>
                            <td>{{ $result->grade ?? $result->letter_grade }}</td>
                            <td>{{ $result->published_at ? $result->published_at->format('d M, Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">কোনো পরীক্ষার ফলাফল এখন পর্যন্ত প্রকাশিত হয়নি।</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- নোটিশ বোর্ড -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i> সাম্প্রতিক নোটিশ</h3>
            </div>
            <div class="card-body p-2">
                <ul class="list-unstyled">
                    @forelse($notices as $notice)
                    <li class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $notice->title }}</strong>
                            <small class="text-muted">{{ $notice->publish_at ? $notice->publish_at->format('d M, Y') : '-' }}</small>
                        </div>
                        <p class="mb-0 text-sm text-truncate">{{ Str::limit($notice->body, 100) }}</p>
                    </li>
                    @empty
                    <li class="text-center">কোনো নোটিশ নেই।</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
