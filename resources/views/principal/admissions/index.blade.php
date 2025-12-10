@extends('layouts.admin')
@section('title','Admission Applications')

@section('content')
@push('styles')
@endpush
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-list mr-1"></i> Admission Applications</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.settings', $school) }}" class="btn btn-outline-secondary">Settings</a>
    <a href="{{ route('principal.institute.admissions.applications.summary', $school->id) }}" class="btn btn-outline-primary ml-2">Application Summary</a>
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
  <div class="col-md-2 col-6 mb-2">
    <div class="border rounded p-2 text-center bg-light">
      <div class="small text-muted">Unpaid Fees</div>
      <div class="h6 mb-0 text-warning">৳ {{ number_format($unpaidAmount,2) }}</div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="form-inline">
      <label for="appSearch" class="mr-2 mb-0 font-weight-semibold">Search:</label>
      <input type="text" id="appSearch" class="form-control form-control-sm w-50" placeholder="Type to filter...">
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0" id="applicationsTable">
        <thead>
          <tr>
            <th style="width:70px">#</th>
            <th>Class</th>
            <th>Application ID</th>
            <th>Roll No</th>
            <th>Applicant Name</th>
            <th>Father's Name</th>
            <th>Mobile No</th>
            <th>Photo</th>
            <th>Present Address</th>
            <th>Status</th>
            <th>Payment</th>
            <th style="width:180px">Actions</th>
            <th>Submitted</th>
          </tr>
        </thead>
        <tbody>
          @forelse($apps as $app)
            <tr>
              <td>{{ ($loop->iteration + ($apps->currentPage()-1)*$apps->perPage()) }}</td>
              <td>{{ $app->class_name }}</td>
              <td>{{ $app->app_id ?: '—' }}</td>
              <td>{{ $app->admission_roll_no ? str_pad($app->admission_roll_no,3,'0',STR_PAD_LEFT) : '—' }}</td>
              <td>{{ $app->name_en ?? $app->applicant_name }}</td>
              <td>{{ $app->father_name_en }}</td>
              <td>{{ $app->mobile }}</td>
              <td>
                <img src="{{ $app->photo ? asset('storage/admission/'.$app->photo) : asset('images/default-avatar.png') }}"
                     alt="Photo" style="width:55px;height:70px;object-fit:cover;cursor:pointer" class="rounded border shadow-sm app-photo-thumb"
                     data-photo-url="{{ $app->photo ? asset('storage/admission/'.$app->photo) : asset('images/default-avatar.png') }}"
                     data-app-name="{{ $app->name_en ?? $app->applicant_name }}">
              </td>
              <td>
                @php
                  $parts = [];
                  if($app->present_village){
                      $v = $app->present_village;
                      if($app->present_para_moholla){ $v .= ' ('.$app->present_para_moholla.')'; }
                      $parts[] = $v;
                  }
                  if($app->present_post_office){ $parts[] = $app->present_post_office; }
                  if($app->present_upazilla){ $parts[] = $app->present_upazilla; }
                  if($app->present_district){ $parts[] = $app->present_district; }
                  echo e(implode(', ', $parts) ?: '—');
                @endphp
              </td>
              <td>
                  @if($app->accepted_at)
                      <span class="badge badge-success">Accepted</span>
                      @if($app->student_id)
                        <span class="badge badge-info ml-1" title="Enrolled"><i class="fas fa-user-check"></i> Enrolled</span>
                      @endif
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
                  <form action="{{ route('principal.institute.admissions.applications.reset_password', [$school->id, $app->id]) }}" method="post" onsubmit="return confirm('পাসওয়ার্ড রিসেট নিশ্চিত?');" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning" title="Reset Password"><i class="fas fa-key"></i></button>
                  </form>
                  @if(!$app->student_id)
                    <a href="{{ route('principal.institute.admissions.applications.edit', [$school->id, $app->id]) }}" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                  @else
                    <button class="btn btn-outline-secondary" title="Already Enrolled" disabled><i class="fas fa-edit"></i></button>
                  @endif
                  @if($app->app_id && $app->payment_status === 'Paid')
                    <a href="{{ route('principal.institute.admissions.applications.copy', [$school->id, $app->id]) }}" target="_blank" class="btn btn-outline-info" title="Print Copy"><i class="fas fa-print"></i></a>
                  @else
                    <button class="btn btn-outline-info" title="{{ $app->payment_status === 'Paid' ? 'Missing App ID' : 'Unpaid – Copy Disabled' }}" disabled><i class="fas fa-print"></i></button>
                  @endif
                  <a href="{{ route('principal.institute.admissions.applications.payments.details', [$school->id, $app->id]) }}" class="btn btn-outline-dark" title="Payments"><i class="fas fa-receipt"></i></a>
                  @if(!$app->accepted_at && $app->status !== 'cancelled' && $app->payment_status==='Paid')
                    <form action="{{ route('principal.institute.admissions.applications.accept', [$school->id, $app->id]) }}" method="post" onsubmit="return confirm('Confirm accept?')">
                      @csrf
                      <button class="btn btn-outline-success" title="Accept"><i class="fas fa-check"></i></button>
                    </form>
                  @endif
                  @if($app->status !== 'cancelled' && !$app->student_id)
                    <button type="button" class="btn btn-outline-danger" title="Cancel" data-toggle="modal" data-target="#cancelModal" data-app-id="{{ $app->id }}" data-app-name="{{ $app->name_en ?? $app->applicant_name }}" data-cancel-url="{{ route('principal.institute.admissions.applications.cancel', [$school->id, $app->id]) }}">
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
            <tr><td colspan="12" class="text-center text-muted">No applications yet.</td></tr>
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
          <h5 class="modal-title" id="cancelModalLabel"><i class="fas fa-ban mr-1"></i> Cancel Application</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="cancelForm" method="post" action="#" class="m-0">
          @csrf
          <div class="modal-body">
            <p class="mb-2 small text-muted">Application ID: <span id="cancelAppId" class="font-weight-bold"></span></p>
            <p class="mb-2 small text-muted">Applicant Name: <span id="cancelAppName" class="font-weight-bold"></span></p>
            <p class="mb-3 small">Cancellation Time (auto): <span class="font-weight-bold">{{ now()->format('d-m-Y H:i') }}</span></p>
            <div class="form-group mb-2">
              <label class="font-weight-semibold">Reason <span class="text-danger">*</span></label>
              <textarea name="cancellation_reason" id="cancellationReason" class="form-control" rows="3" placeholder="Write reason" required></textarea>
            </div>
            <div class="alert alert-warning py-2 mb-2 small">Once cancelled, re-accept may require additional approval.</div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-check mr-1"></i> Confirm Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
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
  // Live search filter
  const input = document.getElementById('appSearch');
  const rows = document.querySelectorAll('#applicationsTable tbody tr');
  input.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    rows.forEach(r => {
      const txt = r.textContent.toLowerCase();
      r.style.display = txt.indexOf(term) !== -1 ? '' : 'none';
    });
  });
});
// Photo modal dynamic creation
function ensurePhotoModal(){
  if(document.getElementById('photoModal')) return;
  const modalHtml = `
  <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header py-2 bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-image mr-1"></i> ছবি প্রদর্শন</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body text-center">
          <img id="modalFullPhoto" src="" alt="Full Photo" style="max-width:100%;height:auto;border-radius:12px;box-shadow:0 4px 18px rgba(0,0,0,.25)">
          <div class="mt-2 small text-muted" id="modalPhotoCaption"></div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">বন্ধ</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.insertAdjacentHTML('beforeend', modalHtml);
}
function openPhotoModal(url, caption){
  ensurePhotoModal();
  document.getElementById('modalFullPhoto').src = url;
  document.getElementById('modalPhotoCaption').textContent = caption || '';
  $('#photoModal').modal('show');
}
// Allow clicking thumbnail itself
document.addEventListener('click',function(e){if(e.target.classList.contains('app-photo-thumb')){openPhotoModal(e.target.dataset.photoUrl,e.target.dataset.appName);}});
</script>
@endpush