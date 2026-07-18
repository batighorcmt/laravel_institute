@extends('layouts.admin')
@section('title','Testimonial সংশোধন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Testimonial সংশোধন</h4>
  <a href="{{ route('principal.institute.documents.testimonial.history', $school) }}" class="btn btn-outline-secondary">ইতিহাস</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.documents.testimonial.update', [$school, $document->id]) }}">
      @csrf
      @method('PUT')
      @php
        $academicYearName = optional($academicYears->firstWhere('id', $document->data['academic_year'] ?? null))->name
            ?? ($document->data['academic_year'] ?? '-');
      @endphp
      {{-- These six fields are fixed once a testimonial is generated — shown
           as plain text only, not editable, to keep the certificate matching
           what was originally issued. --}}
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>শিক্ষাবর্ষ</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $academicYearName }}" readonly>
          <input type="hidden" name="academic_year" value="{{ $document->data['academic_year'] ?? '' }}">
        </div>
        <div class="form-group col-md-4">
          <label>বোর্ড</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $document->data['board'] ?? '-' }}" readonly>
          <input type="hidden" name="board" value="{{ $document->data['board'] ?? '' }}">
        </div>
        <div class="form-group col-md-4">
          <label>শ্রেণি</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $enrollment?->class?->name ?? '-' }}" readonly>
        </div>
        <div class="form-group col-md-4">
          <label>শাখা</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $enrollment?->section?->name ?? '-' }}" readonly>
        </div>
        <div class="form-group col-md-4">
          <label>শিক্ষার্থী</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $document->student?->full_name }}" readonly>
          <input type="hidden" name="student_id" value="{{ $document->student_id }}">
        </div>
        <div class="form-group col-md-4">
          <label>পরীক্ষার নাম</label>
          <input type="text" class="form-control-plaintext font-weight-bold" value="{{ $document->data['exam_name'] ?? '-' }}" readonly>
          <input type="hidden" name="exam_name" value="{{ $document->data['exam_name'] ?? '' }}">
        </div>
        <div class="form-group col-md-4">
          <label>সেশন</label>
          <input type="text" class="form-control" name="session" value="{{ $document->data['session'] ?? '' }}" placeholder="2023-2024" required>
        </div>
        <div class="form-group col-md-4">
          <label>পাশের বছর</label>
          <input type="number" class="form-control" name="passing_year" value="{{ $document->data['passing_year'] ?? '' }}" required>
        </div>
        <div class="form-group col-md-4">
          <label>ফলাফল</label>
          <input type="text" class="form-control" name="result" value="{{ $document->data['result'] ?? '' }}" placeholder="e.g. GPA 5.00">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4"><label>Roll</label><input type="text" class="form-control" name="roll" value="{{ $document->data['roll'] ?? '' }}"></div>
        <div class="form-group col-md-4"><label>Registration</label><input type="text" class="form-control" name="registration" value="{{ $document->data['registration'] ?? '' }}"></div>
        <div class="form-group col-md-4"><label>Center</label><input type="text" class="form-control" name="center" value="{{ $document->data['center'] ?? '' }}"></div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>গ্রুপ</label>
          <select class="form-control" name="group">
            <option value="">-- নির্বাচন করুন --</option>
            @php($currentGroup = $document->data['group'] ?? '')
            @foreach($groups as $g)
              <option value="{{ $g->bangla_name ?: $g->name }}" {{ $currentGroup === ($g->bangla_name ?: $g->name) ? 'selected' : '' }}>{{ $g->bangla_name ?: $g->name }}</option>
            @endforeach
            @if($currentGroup && ! $groups->contains(fn($g) => ($g->bangla_name ?: $g->name) === $currentGroup))
              <option value="{{ $currentGroup }}" selected>{{ $currentGroup }}</option>
            @endif
          </select>
        </div>
      </div>
      <button class="btn btn-primary">সংরক্ষণ করুন</button>
      <a target="_blank" href="{{ route('principal.institute.documents.testimonial.print', [$school,$document->id]) }}" class="btn btn-outline-secondary">Print</a>
    </form>
  </div>
</div>
@endsection
