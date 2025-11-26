@extends('layouts.admin')
@section('title','শিক্ষার্থী বাল্ক ইমপোর্ট')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">শিক্ষার্থী বাল্ক ইমপোর্ট - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.students.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="{{ route('principal.institute.students.bulk.import',$school) }}" enctype="multipart/form-data">
      @csrf
      <div class="row">
        <div class="col-md-8">
          <div class="form-group">
            <label>Excel ফাইল নির্বাচন করুন (XLSX/XLS)</label>
            <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
            @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="col-md-4">
          <label>&nbsp;</label>
          <div>
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-upload mr-1"></i> আপলোড ও ইমপোর্ট
            </button>
            <a href="{{ route('principal.institute.students.bulk.template', $school) }}" class="btn btn-outline-info btn-block mt-2">
              <i class="fas fa-download mr-1"></i> নমুনা টেমপ্লেট ডাউনলোড
            </a>
          </div>
        </div>
      </div>
    </form>

    @if(session('bulk_import_report'))
      @php($report = session('bulk_import_report'))
      <hr class="my-4">
      <div class="alert alert-{{ count($report['errors']) > 0 ? 'warning' : 'success' }}">
        <h5 class="alert-heading">
          <i class="fas fa-check-circle"></i> ইমপোর্ট সম্পন্ন
        </h5>
        <p class="mb-1"><strong>সফল:</strong> {{ $report['success'] }} টি শিক্ষার্থী যোগ হয়েছে</p>
        @if(count($report['errors']) > 0)
          <p class="mb-1"><strong>ত্রুটি:</strong> {{ count($report['errors']) }} টি</p>
          <details class="mt-2">
            <summary class="text-danger" style="cursor:pointer">ত্রুটির তালিকা দেখুন</summary>
            <ul class="small mt-2 mb-0">
              @foreach($report['errors'] as $error)
                <li class="text-danger">{{ $error }}</li>
              @endforeach
            </ul>
          </details>
        @endif
      </div>

      @if(isset($report['imported_students']) && count($report['imported_students']) > 0)
        <h5 class="mt-4 mb-3">আপলোডকৃত শিক্ষার্থী তালিকা</h5>
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm">
            <thead class="table-light">
              <tr>
                <th>রোল নং</th>
                <th>নাম (ইংরেজি)</th>
                <th>নাম (বাংলা)</th>
                <th>শ্রেণি</th>
                <th>শাখা</th>
                <th>শিক্ষাবর্ষ</th>
                <th>জন্ম তারিখ</th>
                <th>লিঙ্গ</th>
                <th>ধর্ম</th>
              </tr>
            </thead>
            <tbody>
              @foreach($report['imported_students'] as $student)
                <tr>
                  <td>{{ $student['roll'] ?? '-' }}</td>
                  <td>{{ $student['name_en'] ?? '-' }}</td>
                  <td>{{ $student['name_bn'] ?? '-' }}</td>
                  <td>{{ $student['class'] ?? '-' }}</td>
                  <td>{{ $student['section'] ?? '-' }}</td>
                  <td>{{ $student['year'] ?? '-' }}</td>
                  <td>{{ $student['dob'] ?? '-' }}</td>
                  <td>{{ $student['gender'] ?? '-' }}</td>
                  <td>{{ $student['religion'] ?? '-' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    @endif

    <div class="mt-4 p-3 bg-light rounded">
      <h6 class="text-primary mb-2"><i class="fas fa-info-circle"></i> প্রয়োজনীয় তথ্য</h6>
      <p class="small mb-1"><strong>আবশ্যক ফিল্ড (৫টি):</strong></p>
      <ul class="small mb-0">
        <li><code>student_name_en</code> - শিক্ষার্থীর নাম (ইংরেজি)</li>
        <li><code>enroll_academic_year</code> - শিক্ষাবর্ষ (যেমন: 2025)</li>
        <li><code>enroll_roll_no</code> - রোল নম্বর</li>
        <li><code>enroll_class_name</code> অথবা <code>enroll_class_id</code> - শ্রেণির নাম (যেমন: Six, Seven)</li>
        <li><code>enroll_section_name</code> অথবা <code>enroll_section_id</code> - শাখার নাম (যেমন: A, B)</li>
      </ul>
      <p class="small text-muted mt-2 mb-0">অন্যান্য সকল ফিল্ড ঐচ্ছিক - ফাঁকা রাখতে পারবেন।</p>
    </div>
  </div>
</div>

@endsection
