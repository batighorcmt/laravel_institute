@extends('layouts.admin')
@section('title','শিক্ষক রুটিন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-calendar-alt mr-1"></i> শিক্ষক রুটিন</h1>
</div>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">শিক্ষক নির্বাচন করুন</h3>
  </div>
  <div class="card-body">
    <form action="{{ route('principal.institute.routine.teacher-print', $school) }}" method="GET" target="_blank">
      <div class="form-row align-items-end">
        <div class="form-group col-md-4 mb-md-0 mb-3">
          <label for="teacher_id">শিক্ষক</label>
          <select name="teacher_id" id="teacher_id" class="form-control select2" required>
            <option value="">— নির্বাচন করুন —</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}">{{ $teacher->user->name ?? 'Unknown' }} {{ $teacher->designation ? ' - '.$teacher->designation : '' }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-4 mb-0">
          <button type="submit" class="btn btn-primary"><i class="fas fa-print mr-1"></i> প্রিন্ট পেজ দেখুন</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
});
</script>
@endpush
