@extends('layouts.admin')
@section('title','Class Admission Settings')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-cog mr-1"></i> Class Admission Settings</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.settings', $school) }}" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="row">
  <div class="col-md-5">
    <div class="card mb-3">
      <div class="card-header py-2">Add New</div>
      <div class="card-body py-2">
        <form method="post" action="{{ route('principal.institute.admissions.class-settings.store', $school) }}" class="mb-0">
          @csrf
          <div class="form-group mb-2">
            <label class="small mb-1">Class (numeric) <span class="text-danger">*</span></label>
            <select name="class_code" class="form-control form-control-sm" required>
              <option value="">-- Select class --</option>
              @isset($classes)
                @foreach($classes as $c)
                  <option value="{{ $c->numeric_value }}">{{ $c->name }} ({{ $c->numeric_value }})</option>
                @endforeach
              @endisset
            </select>
          </div>
          <div class="form-group mb-2">
            <label class="small mb-1">Fee Amount (BDT) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="fee_amount" class="form-control form-control-sm" required>
          </div>
          <div class="form-group mb-2">
            <label class="small mb-1">Deadline (optional)</label>
            <input type="date" name="deadline" class="form-control form-control-sm">
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="active" id="activeNew" value="1" checked>
            <label class="form-check-label small" for="activeNew">Active</label>
          </div>
          <button class="btn btn-primary btn-sm">Save</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card mb-3">
      <div class="card-header py-2">Existing Settings</div>
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>Class</th>
              <th>Fee</th>
              <th>Deadline</th>
              <th>Status</th>
              <th style="width:130px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($settings as $set)
              <tr>
                <td class="align-middle">{{ $set->class_code }}</td>
                <td class="align-middle">{{ number_format($set->fee_amount,2) }}</td>
                <td class="align-middle">{{ $set->deadline ? $set->deadline->format('d-m-Y') : '—' }}</td>
                <td class="align-middle">
                  @if($set->active && (!$set->deadline || $set->deadline->isFuture()))
                    <span class="badge badge-success">Open</span>
                  @elseif($set->active && $set->deadline && $set->deadline->isPast())
                    <span class="badge badge-warning text-dark">Expired</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td class="align-middle">
                  <div class="d-flex gap-1">
                    <form action="{{ route('principal.institute.admissions.class-settings.update', [$school, $set->id]) }}" method="post" class="form-inline mr-1">
                      @csrf @method('PUT')
                      <input type="number" step="0.01" name="fee_amount" value="{{ $set->fee_amount }}" class="form-control form-control-sm mb-1" style="width:90px" required>
                      <input type="date" name="deadline" value="{{ $set->deadline ? $set->deadline->format('Y-m-d') : '' }}" class="form-control form-control-sm mb-1" style="width:140px">
                      <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="active" value="1" id="active{{ $set->id }}" {{ $set->active ? 'checked' : '' }}>
                        <label class="form-check-label small" for="active{{ $set->id }}">Active</label>
                      </div>
                      <button class="btn btn-sm btn-success">Update</button>
                    </form>
                    <form action="{{ route('principal.institute.admissions.class-settings.destroy', [$school, $set->id]) }}" method="post" onsubmit="return confirm('Delete?')" class="ml-1">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted text-center">No class settings yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
