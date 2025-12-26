@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Fine Settings</h3>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('billing.settings.fines.store') }}" class="mb-4">
      @csrf
      <div class="row align-items-end">
        <div class="col-md-3">
          <label class="form-label">Fine Type</label>
          <select name="fine_type" class="form-control" required>
            <option value="fixed" {{ ($setting && $setting->fine_type === 'fixed') ? 'selected' : '' }}>Fixed</option>
            <option value="percent" {{ ($setting && $setting->fine_type === 'percent') ? 'selected' : '' }}>Percentage</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Fine Value</label>
          <input type="number" step="0.01" name="fine_value" value="{{ $setting->fine_value ?? '' }}" class="form-control" placeholder="e.g. 50 or 2.5" required>
        </div>
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="active" id="active" {{ ($setting && $setting->active) ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Active</label>
          </div>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>

    @if($setting)
      <div class="mt-4">
        <h5>Current Setting</h5>
        <p>Type: <strong>{{ ucfirst($setting->fine_type) }}</strong>, Value: <strong>{{ $setting->fine_value }}</strong>, Active: <strong>{{ $setting->active ? 'Yes' : 'No' }}</strong></p>
      </div>
    @endif
  </div>
</div>
@endsection
