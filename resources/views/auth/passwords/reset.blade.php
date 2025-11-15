@extends('layouts.admin')
@section('title','নতুন পাসওয়ার্ড সেট করুন')
@section('content')
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card">
      <div class="card-header">নতুন পাসওয়ার্ড সেট করুন</div>
      <div class="card-body">
        <form method="POST" action="{{ route('password.update') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <div class="form-group">
            <label for="email">ইমেইল</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
          </div>
          <div class="form-group">
            <label for="password">নতুন পাসওয়ার্ড</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
            @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
          </div>
          <div class="form-group">
            <label for="password-confirm">পাসওয়ার্ড নিশ্চিত করুন</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
          </div>
          <button type="submit" class="btn btn-success">সেভ করুন</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
