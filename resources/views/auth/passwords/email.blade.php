@extends('layouts.admin')
@section('title','পাসওয়ার্ড রিসেট')
@section('content')
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card">
      <div class="card-header">পাসওয়ার্ড রিসেট লিঙ্ক</div>
      <div class="card-body">
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
          @csrf
          <div class="form-group">
            <label for="email">ইমেইল</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
          </div>
          <button type="submit" class="btn btn-primary">লিঙ্ক পাঠান</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
