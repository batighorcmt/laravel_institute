@extends('layouts.admin')
@section('title','Application Summary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-chart-pie mr-1"></i> আবেদন সারাংশ (Application Summary)</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.applications', $school->id) }}" class="btn btn-outline-secondary">Back to Applications</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="form-inline">
      <label for="year_id" class="mr-2">Academic Year</label>
      <select name="year_id" id="year_id" class="form-control mr-2">
        <option value="">All</option>
        @php($years = \App\Models\AcademicYear::where('school_id',$school->id)->orderByDesc('start_date')->get())
        @foreach($years as $y)
          <option value="{{ $y->id }}" {{ (int)$yearId === (int)$y->id ? 'selected' : '' }}>{{ $y->name ?? ($y->start_date.' - '.($y->end_date ?: '')) }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary">Apply</button>
    </form>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Total</div>
      <div class="h5 mb-0">{{ $totalApps }}</div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Accepted</div>
      <div class="h5 mb-0 text-success">{{ $acceptedApps }}</div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Cancelled</div>
      <div class="h5 mb-0 text-danger">{{ $cancelledApps }}</div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Paid Apps</div>
      <div class="h5 mb-0 text-primary">{{ $paidApps }}</div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Unpaid Apps</div>
      <div class="h5 mb-0 text-warning">{{ $unpaidApps }}</div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Total Paid Fees</div>
      <div class="h6 mb-0">৳ {{ number_format($totalPaidAmount,2) }}</div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2 font-weight-bold">By Class</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Class</th><th class="text-right">Count</th></tr></thead>
            <tbody>
              @forelse($byClass as $r)
                <tr><td>{{ $r->class_name ?: '—' }}</td><td class="text-right">{{ $r->total }}</td></tr>
              @empty
                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2 font-weight-bold">By Gender</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Gender</th><th class="text-right">Count</th></tr></thead>
            <tbody>
              @forelse($byGender as $r)
                <tr><td>{{ $r->gender ?: '—' }}</td><td class="text-right">{{ $r->total }}</td></tr>
              @empty
                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2 font-weight-bold">By Village (Present)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>ক্রমিক নং</th><th>Village</th><th class="text-right">Count</th></tr></thead>
            <tbody>
              @forelse($byVillage as $r)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $r->present_village ?: '—' }}</td><td class="text-right">{{ $r->total }}</td></tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header py-2 font-weight-bold">By Previous School</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>ক্রমিক নং</th><th>School</th><th class="text-right">Count</th></tr></thead>
            <tbody>
              @forelse($byPrevSchool as $r)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $r->last_school ?: '—' }}</td><td class="text-right">{{ $r->total }}</td></tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
