@extends('layouts.admin')
@section('title','Admission Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-receipt mr-1"></i> Admission Payments</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.applications', $school->id) }}" class="btn btn-outline-secondary">Back to Applications</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-inline mb-3">
      <div class="form-group mr-2">
        <label for="status" class="mr-2">Status</label>
        <select name="status" id="status" class="form-control">
          <option value="">All</option>
          <option value="Completed" {{ ($status ?? '')==='Completed' ? 'selected' : '' }}>Completed</option>
          <option value="Failed" {{ ($status ?? '')==='Failed' ? 'selected' : '' }}>Failed</option>
          <option value="Pending" {{ ($status ?? '')==='Pending' ? 'selected' : '' }}>Pending</option>
        </select>
      </div>
      <div class="form-group mr-2">
        <label for="from" class="mr-2">From</label>
        <input type="date" name="from" id="from" class="form-control" value="{{ $from ? \Illuminate\Support\Carbon::parse($from)->format('Y-m-d') : '' }}">
      </div>
      <div class="form-group mr-2">
        <label for="to" class="mr-2">To</label>
        <input type="date" name="to" id="to" class="form-control" value="{{ $to ? \Illuminate\Support\Carbon::parse($to)->format('Y-m-d') : '' }}">
      </div>
      <div class="form-group mr-2">
        <label for="q" class="mr-2">Search</label>
        <input type="text" name="q" id="q" class="form-control" placeholder="Tran/App ID/Name" value="{{ $search ?? '' }}">
      </div>
      <button type="submit" class="btn btn-primary mr-2">Filter</button>
      <a href="{{ route('principal.institute.admissions.payments', $school->id) }}" class="btn btn-outline-secondary">Reset</a>
    </form>
    <div class="table-responsive">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th style="width:80px">#</th>
            <th>Application ID</th>
            <th>Applicant</th>
            <th>Class</th>
            <th>Fee Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Method</th>
            <th>Tran ID</th>
            <th>Invoice</th>
            <th>Date</th>
            <th style="width:160px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $idx => $pay)
            @php($app = $pay->application)
            <tr>
              <td>{{ ($payments->firstItem() ?? 1) + $idx }}</td>
              <td>{{ $app?->app_id ?? '—' }}</td>
              <td>{{ $app?->name_en ?? $app?->name_bn ?? '—' }}</td>
              <td>{{ $app?->class_name ?? '—' }}</td>
              <td>
                @php($ft = strtolower((string)($pay->fee_type ?? '')))
                <span class="badge badge-{{ $ft==='admission' ? 'info' : 'primary' }}">
                  {{ $ft==='admission' ? 'Admission Fee' : 'Application Fee' }}
                </span>
              </td>
              <td>৳ {{ number_format((float)$pay->amount, 2) }}</td>
              <td>
                @if($pay->status === 'Completed')
                  <span class="badge badge-success">Completed</span>
                @elseif($pay->status === 'Failed')
                  <span class="badge badge-danger">Failed</span>
                @else
                  <span class="badge badge-secondary">{{ $pay->status ?? '—' }}</span>
                @endif
              </td>
              <td>{{ $pay->payment_method ?? '—' }}</td>
              <td><code>{{ $pay->tran_id ?? '—' }}</code></td>
              <td>
                @if($app)
                  <a href="{{ route('principal.institute.admissions.payments.invoice', [$school->id, $pay->id]) }}" class="btn btn-sm btn-outline-secondary">View</a>
                @else
                  —
                @endif
              </td>
              <td>{{ optional($pay->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  @if($app)
                    <a href="{{ route('principal.institute.admissions.applications.show', [$school->id, $app->id]) }}" class="btn btn-outline-primary" title="View Application"><i class="fas fa-eye"></i></a>
                    <a href="{{ route('principal.institute.admissions.applications.payments.details', [$school->id, $app->id]) }}" class="btn btn-outline-dark" title="Payment Details"><i class="fas fa-list"></i></a>
                  @else
                    <button class="btn btn-outline-secondary" disabled title="No Application"><i class="fas fa-eye"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted">No payments found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $payments->appends(request()->query())->links() }}</div>
</div>
@endsection