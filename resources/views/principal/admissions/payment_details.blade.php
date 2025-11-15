@extends('layouts.admin')
@section('title','Payment Details')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 m-0">Payment Details: {{ $application->applicant_name }} ({{ $application->app_id }})</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.applications.show', [$school->id,$application->id]) }}" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered mb-0 table-sm">
        <thead class="bg-light">
          <tr>
            <th>#</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Method</th>
            <th>Tran ID</th>
            <th>Invoice</th>
            <th>Gateway Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $pay)
            <tr>
              <td>{{ $pay->id }}</td>
              <td>{{ number_format($pay->amount,2) }}</td>
              <td>
                @if($pay->status==='Completed')
                  <span class="badge badge-success">Completed</span>
                @elseif($pay->status==='Failed')
                  <span class="badge badge-danger">Failed</span>
                @else
                  <span class="badge badge-warning text-dark">Initiated</span>
                @endif
              </td>
              <td>{{ $pay->payment_method }}</td>
              <td class="small">{{ $pay->tran_id }}</td>
              <td class="small">{{ $pay->invoice_no }}</td>
              <td class="small">{{ $pay->gateway_status ?? '—' }}</td>
              <td>{{ $pay->created_at->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">No payments yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@if($payments->first()?->gateway_response)
  <div class="card mt-3">
    <div class="card-header py-2"><strong>Latest Gateway Raw Response</strong></div>
    <div class="card-body">
      <pre class="small mb-0">{{ json_encode($payments->first()->gateway_response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
  </div>
@endif
@endsection