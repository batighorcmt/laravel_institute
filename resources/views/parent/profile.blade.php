@extends('layouts.admin')
@section('title', 'শিক্ষার্থীর প্রোফাইল')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-user-graduate mr-2"></i> শিক্ষার্থীর প্রোফাইল</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    @if($selectedStudent)
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle"
                             src="{{ $selectedStudent->photo ? asset('storage/'.$selectedStudent->photo) : asset('images/default-student.png') }}"
                             alt="Student profile picture">
                    </div>
                    <h3 class="profile-username text-center">{{ $selectedStudent->student_name_en }}</h3>
                    <p class="text-muted text-center">{{ $selectedStudent->student_id }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>শ্রেণী</b> <a class="float-right">{{ $selectedStudent->class->name ?? 'N/A' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>রোল</b> <a class="float-right">{{ $selectedStudent->enrollments()->latest()->first()?->roll_no ?? 'N/A' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>লিঙ্গ</b> <a class="float-right">{{ ucfirst($selectedStudent->gender) }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#details" data-toggle="tab">ব্যক্তিগত তথ্য</a></li>
                        <li class="nav-item"><a class="nav-link" href="#guardian" data-toggle="tab">অভিভাবকের তথ্য</a></li>
                        <li class="nav-item"><a class="nav-link" href="#address" data-toggle="tab">ঠিকানা</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="active tab-pane" id="details">
                            <table class="table">
                                <tr><th>নাম (বাংলা)</th><td>{{ $selectedStudent->student_name_bn }}</td></tr>
                                <tr><th>জন্ম তারিখ</th><td>{{ $selectedStudent->date_of_birth ? $selectedStudent->date_of_birth->format('d M, Y') : 'N/A' }}</td></tr>
                                <tr><th>রক্তের গ্রুপ</th><td>{{ $selectedStudent->blood_group ?? 'N/A' }}</td></tr>
                                <tr><th>ধর্ম</th><td>{{ $selectedStudent->religion ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                        <div class="tab-pane" id="guardian">
                            <table class="table">
                                <tr><th>পিতার নাম</th><td>{{ $selectedStudent->father_name }}</td></tr>
                                <tr><th>মাতার নাম</th><td>{{ $selectedStudent->mother_name }}</td></tr>
                                <tr><th>অভিভাবকের ফোন</th><td>{{ $selectedStudent->guardian_phone }}</td></tr>
                                <tr><th>সম্পর্ক</th><td>{{ $selectedStudent->guardian_relation }}</td></tr>
                            </table>
                        </div>
                        <div class="tab-pane" id="address">
                            <h6>বর্তমান ঠিকানা</h6>
                            <p>{{ $selectedStudent->present_village }}, {{ $selectedStudent->present_post_office }}, {{ $selectedStudent->present_upazilla }}, {{ $selectedStudent->present_district }}</p>
                            <hr>
                            <h6>স্থায়ী ঠিকানা</h6>
                            <p>{{ $selectedStudent->permanent_village }}, {{ $selectedStudent->permanent_post_office }}, {{ $selectedStudent->permanent_upazilla }}, {{ $selectedStudent->permanent_district }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
