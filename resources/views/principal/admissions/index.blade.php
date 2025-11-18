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
                      <button type="button" class="btn btn-outline-danger" title="Cancel" data-toggle="modal" data-target="#cancelModal" data-app-id="{{ $app->id }}" data-app-name="{{ $app->applicant_name }}" data-cancel-url="{{ route('principal.institute.admissions.applications.cancel', [$school->id, $app->id]) }}">
                        <i class="fas fa-times"></i>
                      </button>
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
  <!-- Cancellation Modal -->
  <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title" id="cancelModalLabel"><i class="fas fa-ban mr-1"></i> আবেদন বাতিল</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="cancelForm" method="post" action="#" class="m-0">
          @csrf
          <div class="modal-body">
            <p class="mb-2 small text-muted">আবেদন আইডি: <span id="cancelAppId" class="font-weight-bold"></span></p>
            <p class="mb-2 small text-muted">আবেদনকারীর নাম: <span id="cancelAppName" class="font-weight-bold"></span></p>
            <p class="mb-3 small">বাতিলের তারিখ (স্বয়ংক্রিয়): <span class="font-weight-bold">{{ now()->format('d-m-Y H:i') }}</span></p>
            <div class="form-group mb-2">
              <label class="font-weight-semibold">বাতিলের কারণ <span class="text-danger">*</span></label>
              <textarea name="cancellation_reason" id="cancellationReason" class="form-control" rows="3" placeholder="কারণ লিখুন" required></textarea>
            </div>
            <div class="alert alert-warning py-2 mb-2 small">একবার বাতিল করলে পুনরায় গ্রহণ করতে চাইলে পৃথক অনুমোদন প্রয়োজন হতে পারে।</div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">বন্ধ</button>
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-check mr-1"></i> নিশ্চিত বাতিল</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var cancelModal = document.getElementById('cancelModal');
  $('#cancelModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var appId = button.data('app-id');
    var appName = button.data('app-name');
    var url = button.data('cancel-url');
    $('#cancelForm').attr('action', url);
    $('#cancelAppId').text(appId);
    $('#cancelAppName').text(appName);
    $('#cancellationReason').val('').focus();
  });
});
</script>
@endpush