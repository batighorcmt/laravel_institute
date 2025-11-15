@extends('layouts.admin')
@section('title','Admission Payment History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-receipt mr-1"></i> Payment History</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.settings', $school) }}" class="btn btn-outline-secondary">Settings</a>
    <a href="{{ route('principal.institute.admissions.applications', $school) }}" class="btn btn-outline-primary">Applications</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <div class="text-muted">No payments recorded yet. Integration pending.</div>
  </div>
</div>
@endsection