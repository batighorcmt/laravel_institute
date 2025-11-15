@extends('layouts.admin')
@section('title','Admission Applications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-list mr-1"></i> Applications</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.settings', $school) }}" class="btn btn-outline-secondary">Settings</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th style="width:70px">#</th>
            <th>Applicant</th>
            <th>Phone</th>
            <th>Class</th>
            <th>Status</th>
            <th>Payment</th>
            <th style="width:160px">Actions</th>
            <th>Submitted</th>
          </tr>
        </thead>
        <tbody>
          @forelse($apps as $app)
            <tr>
              <td>{{ $app->id }}</td>
              <td>{{ $app->applicant_name }}</td>
              <td>{{ $app->phone }}</td>
              <td>{{ $app->class_name }}</td>
              <td>
                  @if($app->accepted_at)
                      <span class="badge badge-success">Accepted</span>
                  @elseif($app->status === 'cancelled')
                      <span class="badge badge-danger">Cancelled</span>
                  @else
                      <span class="badge badge-secondary">Pending</span>
                  @endif
              </td>
              <td>
                  @if($app->payment_status === 'Paid')
                      <span class="badge badge-success">Paid</span>
                  @else
                      <span class="badge badge-warning text-dark">Unpaid</span>
                  @endif
              </td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a href="{{ route('principal.institute.admissions.applications.show', [$school->id, $app->id]) }}" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                  <a href="{{ route('principal.institute.admissions.applications.edit', [$school->id, $app->id]) }}" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                    @if($app->app_id)
                      <a href="{{ route('admission.copy', [$school->code, $app->app_id]) }}" target="_blank" class="btn btn-outline-info" title="Print Copy"><i class="fas fa-print"></i></a>
                    @else
                      <button class="btn btn-outline-info" title="Missing App ID" disabled><i class="fas fa-print"></i></button>
                    @endif
                  <a href="{{ route('principal.institute.admissions.applications.payments.details', [$school->id, $app->id]) }}" class="btn btn-outline-dark" title="Payments"><i class="fas fa-receipt"></i></a>
                    @if(!$app->accepted_at && $app->status !== 'cancelled' && $app->payment_status==='Paid')
                      <form action="{{ route('principal.institute.admissions.applications.accept', [$school->id, $app->id]) }}" method="post" onsubmit="return confirm('গ্রহণ নিশ্চিত?')">
                          @csrf
                          <button class="btn btn-outline-success" title="Accept"><i class="fas fa-check"></i></button>
                      </form>
                  @endif
                    @if($app->status !== 'cancelled')
                      <form action="{{ route('principal.institute.admissions.applications.cancel', [$school->id, $app->id]) }}" method="post" class="ml-1" style="min-width:180px">
                        @csrf
                        <div class="input-group input-group-sm">
                          <input type="text" name="cancellation_reason" class="form-control" placeholder="বাতিলের কারণ" required>
                          <div class="input-group-append">
                            <button class="btn btn-outline-danger" title="Cancel" onclick="return confirm('বাতিল নিশ্চিত?')"><i class="fas fa-times"></i></button>
                          </div>
                        </div>
                      </form>
                    @endif
                  @if($app->accepted_at)
                      <a href="{{ route('principal.institute.admissions.applications.admit_card', [$school->id, $app->id]) }}" class="btn btn-outline-success" title="Admit Card"><i class="fas fa-id-card"></i></a>
                  @endif
                </div>
              </td>
              <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">No applications yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $apps->links() }}</div>
</div>
@endsection