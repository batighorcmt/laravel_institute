@extends('layouts.admin')
@section('title','শিক্ষক সম্পাদনা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">শিক্ষক সম্পাদনা</h1>
  <div>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-outline-secondary">পিছনে যান</a>
  </div>
</div>

@php /* Inline session alerts removed; toastr popup will display messages */ @endphp

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <ul class="mb-0 mt-2">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-body">
    @include('principal.teachers._form')
  </div>
</div>
@endsection
