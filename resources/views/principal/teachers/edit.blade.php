@extends('layouts.admin')
@section('title','শিক্ষক সম্পাদনা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">শিক্ষক সম্পাদনা</h1>
  <div>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-outline-secondary">Back to list</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    @include('principal.teachers._form')
  </div>
</div>
@endsection
