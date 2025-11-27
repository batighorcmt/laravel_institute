@extends('layouts.admin')

@section('title', 'Student Profile')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">শিক্ষার্থীর প্রোফাইল</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('teacher.institute.directory.students', $school) }}">Students</a></li>
          <li class="breadcrumb-item active">Profile</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <img src="{{ $student->photo ? asset('storage/'.$student->photo) : asset('images/avatar-student.png') }}" class="img-thumbnail mr-3" style="width:90px;height:90px;object-fit:cover;" alt="Photo">
              <div>
                <h4 class="mb-1">{{ $student->student_name_bn ?? $student->student_name_en }}</h4>
                <div class="text-muted">ID: {{ $student->id }}</div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-sm-6">
                <strong>শ্রেণি</strong>
                <div>{{ $enroll?->class->name ?? '-' }}</div>
              </div>
              <div class="col-sm-6">
                <strong>শাখা</strong>
                <div>{{ $enroll?->section?->name ?? '-' }}</div>
              </div>
              <div class="col-sm-6 mt-2">
                <strong>বিভাগ</strong>
                <div>{{ $enroll?->group?->name ?? '-' }}</div>
              </div>
              <div class="col-sm-6 mt-2">
                <strong>রোল</strong>
                <div>{{ $enroll?->roll_no ?? '-' }}</div>
              </div>
              <div class="col-sm-6 mt-2">
                <strong>লিঙ্গ</strong>
                <div class="text-capitalize">{{ $student->gender ?? '-' }}</div>
              </div>
              <div class="col-sm-6 mt-2">
                <strong>ধর্ম</strong>
                <div class="text-capitalize">{{ $student->religion ?? '-' }}</div>
              </div>
              <div class="col-sm-6 mt-3">
                <strong>অভিভাবকের মোবাইল</strong>
                <div>{{ $student->guardian_phone ?? '-' }}</div>
              </div>
              <div class="col-sm-12 mt-3">
                <strong>বর্তমান ঠিকানা</strong>
                @php
                  $pv = $student->present_village;
                  $pp = $student->present_para_moholla;
                  $po = $student->present_post_office ?: $pv; // fallback to village if post office missing
                  $pu = $student->present_upazilla;
                  $pd = $student->present_district;
                  if($pv || $pp || $po || $pu || $pd){
                    $presentFormatted = 'গ্রামঃ ' . ($pv ?: '-') . ($pp ? ' (' . $pp . ')' : '') . ', ডাকঘরঃ ' . ($po ?: '-') . ', উপজেলাঃ ' . ($pu ?: '-') . ', জেলাঃ ' . ($pd ?: '-') ;
                  } else {
                    $presentFormatted = '-';
                  }
                @endphp
                <div>{{ $presentFormatted }}</div>
              </div>
              <div class="col-sm-12 mt-2">
                <strong>স্থায়ী ঠিকানা</strong>
                @php
                  $mv = $student->permanent_village;
                  $mp = $student->permanent_para_moholla;
                  $mo = $student->permanent_post_office ?: $mv; // fallback
                  $mu = $student->permanent_upazilla;
                  $md = $student->permanent_district;
                  if($mv || $mp || $mo || $mu || $md){
                    $permanentFormatted = 'গ্রামঃ ' . ($mv ?: '-') . ($mp ? ' (' . $mp . ')' : '') . ', ডাকঘরঃ ' . ($mo ?: '-') . ', উপজেলাঃ ' . ($mu ?: '-') . ', জেলাঃ ' . ($md ?: '-') ;
                  } else {
                    $permanentFormatted = '-';
                  }
                @endphp
                <div>{{ $permanentFormatted }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
