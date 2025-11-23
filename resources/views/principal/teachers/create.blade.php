@extends('layouts.admin')
@section('title','নতুন শিক্ষক যুক্ত করুন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">নতুন শিক্ষক যুক্ত করুন</h1>
  <div>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-outline-secondary">পিছনে যান</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-check-circle"></i> {{ session('success') }}
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <strong>দয়া করে নিম্নলিখিত ত্রুটি সংশোধন করুন:</strong>
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
