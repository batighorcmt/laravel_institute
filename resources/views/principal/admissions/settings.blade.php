@extends('layouts.admin')
@section('title','Admission Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-graduate mr-1"></i> Admission Settings</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.applications', $school) }}" class="btn btn-outline-primary">Applications</a>
  </div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.admissions.settings.update', $school) }}">
      @csrf
      <div class="form-group form-check">
        <input type="checkbox" name="admissions_enabled" value="1" class="form-check-input" id="admissions_enabled" {{ $school->admissions_enabled ? 'checked' : '' }}>
        <label class="form-check-label" for="admissions_enabled">Enable public admission form</label>
      </div>
      <div class="form-group mt-3">
        <label for="admission_academic_year_id" class="font-weight-semibold">Admission Academic Year *</label>
        <select name="admission_academic_year_id" id="admission_academic_year_id" class="form-control" required>
          <option value="">-- Choose Year --</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected($school->admission_academic_year_id==$year->id)>{{ $year->name }} ({{ $year->start_date }} - {{ $year->end_date }})</option>
          @endforeach
        </select>
        <small class="text-muted">এ শিক্ষাবর্ষের জন্য আবেদন গ্রহণ করা হবে।</small>
      </div>
      <button class="btn btn-primary">Save</button>
    </form>

    <hr>
    <h5>Public Form Link</h5>
    @if($school->admissions_enabled && $school->admission_academic_year_id)
      <code>{{ url('/admission/'.$school->code) }}</code>
      <div class="text-muted">Share this link for public applications.</div>
    @else
      <div class="text-muted">Enable admissions to generate a public link.</div>
    @endif
  </div>
</div>
@endsection
