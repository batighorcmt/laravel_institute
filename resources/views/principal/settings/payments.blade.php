@extends('layouts.admin')
@section('title','Online Payments (SSLCommerz)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-credit-card mr-1"></i> Online Payments</h1>
  <div>
    <a href="https://developer.sslcommerz.com/" target="_blank" class="btn btn-outline-secondary">Docs</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.payments.save', $school) }}">
      @csrf
      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Store ID</label>
          <input type="text" name="store_id" class="form-control" value="{{ old('store_id', $setting->store_id) }}" required>
        </div>
        <div class="form-group col-md-6">
          <label>Store Password</label>
          <input type="text" name="store_password" class="form-control" value="{{ old('store_password', $setting->store_password) }}" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-3">
          <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="sandbox" name="sandbox" value="1" {{ $setting->sandbox ? 'checked' : '' }}>
            <label for="sandbox" class="form-check-label">Sandbox Mode</label>
          </div>
        </div>
        <div class="form-group col-md-3">
          <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ $setting->active ? 'checked' : '' }}>
            <label for="active" class="form-check-label">Active</label>
          </div>
        </div>
      </div>
      <button class="btn btn-primary">Save Settings</button>
    </form>
    <hr>
    <div class="text-muted small">
      মনে রাখবেন: SSLCommerz Sandbox বা Live environment অনুযায়ী Store ID/Password আলাদা হয়। ভুল credentials দিলে পেমেন্ট ব্যর্থ হবে।
    </div>
  </div>
</div>
@endsection