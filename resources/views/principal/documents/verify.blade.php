@extends('layouts.admin')
@section('title','Document Verification')

@section('content')
<div class="card">
  <div class="card-header"><strong>Verification Result</strong></div>
  <div class="card-body">
    <p><strong>Type:</strong> {{ ucfirst($document->type) }}</p>
    <p><strong>Memo No:</strong> {{ $document->memo_no }}</p>
    <p><strong>Issued At:</strong> {{ $document->issued_at->format('d-m-Y') }}</p>
    @if($student)
      <p><strong>Student:</strong> {{ $student->full_name }} (ID: {{ $student->student_id }})</p>
    @endif
    <p><strong>School:</strong> {{ $school->name ?? 'N/A' }}</p>
    <p class="text-success">এই ডকুমেন্টটি বৈধ এবং সার্ভারে সংরক্ষিত রেকর্ডের সাথে মিল রয়েছে।</p>
  </div>
</div>
@endsection
