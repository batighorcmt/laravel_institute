@extends('layouts.admin')
@section('title','শিক্ষার্থী তালিকা প্রিন্ট কন্ট্রোলস')
@section('content')
@php
  $years = $years ?? collect();
  $currentYear = $currentYear ?? null;
  $selectedYear = $selectedYear ?? null;
  $selectedYearId = $selectedYearId ?? 0;
  $cols = is_array($cols ?? null) ? $cols : [];
  $lang = $lang ?? 'bn';
  $u = auth()->user();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">প্রিন্ট কন্ট্রোলস - {{ $school->name }}</h1>
  <div>
    <a href="{{ route('principal.institute.students.index', $school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকায় ফিরে যান</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="m-0">প্রিন্ট সেটিংস</h5>
  </div>
  <div class="card-body">
    <form method="get" action="{{ route('principal.institute.students.print-preview', $school) }}" class="needs-validation" novalidate>
      <div class="row">
        <div class="col-12 col-lg-4 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">কলাম নির্বাচন</h6>
            @php
              $allColumns = [
                'serial' => 'ক্রমিক',
                'student_id' => 'আইডি নং',
                'name' => 'নাম',
                'father' => 'পিতার নাম',
                'class' => 'শ্রেণি',
                'section' => 'শাখা',
                'roll' => 'রোল',
                'group' => 'গ্রুপ',
                'mobile' => 'মোবাইল নং',
                'status' => 'স্ট্যাটাস',
                'subjects' => 'বিষয়সমূহ',
                'photo' => 'ছবি',
              ];
            @endphp
            <div class="row">
              @foreach($allColumns as $key => $label)
                <div class="col-6">
                  <div class="custom-control custom-checkbox mb-1">
                    <input type="checkbox" class="custom-control-input" id="col-{{ $key }}" name="cols[]" value="{{ $key }}" {{ in_array($key, $cols) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="col-{{ $key }}">{{ $label }}</label>
                  </div>
                </div>
              @endforeach
            </div>
            <small class="text-muted d-block mt-2">প্রয়োজনীয় কলামগুলো সিলেক্ট করে নিন। ছবি যুক্ত করলে পেজ বড় হতে পারে।</small>
          </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">ভাষা</h6>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="lang-bn" name="lang" value="bn" {{ $lang==='bn' ? 'checked' : '' }}>
              <label class="custom-control-label" for="lang-bn">বাংলা</label>
            </div>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="lang-en" name="lang" value="en" {{ $lang==='en' ? 'checked' : '' }}>
              <label class="custom-control-label" for="lang-en">English</label>
            </div>

            <hr>
            <h6 class="mb-2">ইয়ার নির্বাচন</h6>
            <select name="year_id" class="form-control">
              <option value="">-- বছর নির্বাচন --</option>
              @foreach($years as $y)
                <option value="{{ $y->id }}" {{ (int)$selectedYearId===$y->id?'selected':'' }}>{{ $y->name }}</option>
              @endforeach
            </select>

            <div class="form-group mt-3 mb-0">
              <label class="mb-1">ফল সীমা (max 5000)</label>
              <input type="number" class="form-control" name="limit" min="1" max="5000" value="{{ request('limit', 1000) }}">
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">ফিল্টারসমূহ</h6>

            <div class="form-group mb-2">
              <input type="text" name="q" class="form-control" placeholder="নাম/আইডি সার্চ..." value="{{ request('q') }}">
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">শ্রেণি</label>
                <select name="class_id" class="form-control form-control-sm">
                  <option value="">-- শ্রেণি নির্বাচন --</option>
                  @foreach($school->classes ?? [] as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">শাখা</label>
                <select name="section_id" class="form-control form-control-sm">
                  <option value="">-- শাখা নির্বাচন --</option>
                  @foreach($school->sections ?? [] as $section)
                    <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">গ্রুপ</label>
                <select name="group_id" class="form-control form-control-sm">
                  <option value="">-- গ্রুপ নির্বাচন --</option>
                  @foreach($school->groups ?? [] as $group)
                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">স্ট্যাটাস</label>
                <select name="status" class="form-control form-control-sm">
                  <option value="">-- স্ট্যাটাস নির্বাচন --</option>
                  <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                  <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
                  <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>গ্র্যাজুয়েট</option>
                  <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>ট্রান্সফার্ড</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">লিঙ্গ</label>
                <select name="gender" class="form-control form-control-sm">
                  <option value="">-- লিঙ্গ নির্বাচন --</option>
                  <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>পুরুষ</option>
                  <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>মহিলা</option>
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">ধর্ম</label>
                <select name="religion" class="form-control form-control-sm">
                  <option value="">-- ধর্ম নির্বাচন --</option>
                  <option value="Islam" {{ request('religion') == 'Islam' ? 'selected' : '' }}>ইসলাম</option>
                  <option value="Hindu" {{ request('religion') == 'Hindu' ? 'selected' : '' }}>হিন্দু</option>
                  <option value="Buddhist" {{ request('religion') == 'Buddhist' ? 'selected' : '' }}>বৌদ্ধ</option>
                  <option value="Christian" {{ request('religion') == 'Christian' ? 'selected' : '' }}>খ্রিস্টান</option>
                  <option value="Other" {{ request('religion') == 'Other' ? 'selected' : '' }}>অন্যান্য</option>
                </select>
              </div>
            </div>

            @php
              $villages = \App\Models\Student::forSchool($school->id)->whereNotNull('present_village')->distinct()->pluck('present_village')->sort()->unique();
            @endphp
            <div class="form-group mb-0">
              <label class="small mb-1">গ্রাম</label>
              <select name="village" class="form-control form-control-sm">
                <option value="">-- গ্রাম নির্বাচন --</option>
                @foreach($villages as $village)
                  <option value="{{ $village }}" {{ request('village') == $village ? 'selected' : '' }}>{{ $village }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <button class="btn btn-primary"><i class="fas fa-eye mr-1"></i> প্রিভিউ</button>
          <a class="btn btn-outline-danger ml-2" href="{{ route('principal.institute.students.print-controls', $school) }}"><i class="fas fa-undo mr-1"></i> রিসেট</a>
        </div>
        <div class="text-muted small">
          প্রিভিউ পেজে গিয়ে Print চাপুন
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
