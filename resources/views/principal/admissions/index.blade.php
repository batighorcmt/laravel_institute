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
    <a href="{{ route('principal.institute.admissions.applications.print', $school->id) }}" class="btn btn-outline-info ml-2" target="_blank"><i class="fas fa-print mr-1"></i> Print Page</a>
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
    <form method="get" action="{{ route('principal.institute.admissions.applications', $school->id) }}">
      <div class="row">
        <div class="col-md-2 mb-2">
          <label class="small mb-1">শ্রেণি</label>
          <select name="class" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($classes ?? []) as $c)
              <option value="{{ $c }}" {{ ($filters['class'] ?? '')===$c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">লিঙ্গ</label>
          <select name="gender" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($genders ?? []) as $g)
              <option value="{{ $g }}" {{ ($filters['gender'] ?? '')===$g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">ধর্ম</label>
          <select name="religion" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($religions ?? []) as $r)
              <option value="{{ $r }}" {{ ($filters['religion'] ?? '')===$r ? 'selected' : '' }}>{{ $r }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">গ্রাম</label>
          <select name="village" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($villages ?? []) as $v)
              <option value="{{ $v }}" {{ ($filters['village'] ?? '')===$v ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">উপজেলা</label>
          <select name="upazila" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($upazilas ?? []) as $u)
              <option value="{{ $u }}" {{ ($filters['upazila'] ?? '')===$u ? 'selected' : '' }}>{{ $u }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">জেলা</label>
          <select name="district" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($districts ?? []) as $d)
              <option value="{{ $d }}" {{ ($filters['district'] ?? '')===$d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">পূর্ববর্তী বিদ্যালয়</label>
          <select name="prev_school" class="form-control form-control-sm">
            <option value="">সব</option>
            @foreach(($prevSchools ?? []) as $ps)
              <option value="{{ $ps }}" {{ ($filters['prev_school'] ?? '')===$ps ? 'selected' : '' }}>{{ $ps }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">ফিস স্ট্যাটাস</label>
          <select name="pay_status" class="form-control form-control-sm">
            <option value="">সব</option>
            <option value="Paid" {{ ($filters['pay_status'] ?? '')==='Paid' ? 'selected' : '' }}>Paid</option>
            <option value="Unpaid" {{ ($filters['pay_status'] ?? '')==='Unpaid' ? 'selected' : '' }}>Unpaid</option>
            @foreach(($payStatuses ?? []) as $ps)
              @if($ps && !in_array($ps,['Paid']))
                <option value="{{ $ps }}" {{ ($filters['pay_status'] ?? '')===$ps ? 'selected' : '' }}>{{ $ps }}</option>
              @endif
            @endforeach
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">তারিখ হতে</label>
          <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 mb-2">
          <label class="small mb-1">তারিখ পর্যন্ত</label>
          <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4 mb-2">
          <label class="small mb-1">সার্চ (সমস্ত ডাটা)</label>
          <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="নাম, আইডি, মোবাইল, ঠিকানা ইত্যাদি">
        </div>
      </div>
      <div class="text-right">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i> ফিল্টার প্রযোজ্য করো</button>
        <a href="{{ route('principal.institute.admissions.applications', $school->id) }}" class="btn btn-sm btn-secondary ml-2">রিসেট</a>
      </div>
    </form>
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
        <tbody id="appsTbody">
          @include('principal.admissions.partials._rows', ['apps'=>$apps, 'school'=>$school])
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer" id="appsPagination">
    @include('principal.admissions.partials._pagination', ['apps'=>$apps, 'school'=>$school])
  </div>
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
  // Real-time search/filter via AJAX
  const form = document.querySelector('form[action*="admissions/applications"]');
  const tbody = document.getElementById('appsTbody');
  const pager = document.getElementById('appsPagination');
  const debounce = (fn, delay) => { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); }; };
  const buildUrl = (page=null) => {
    const base = form.getAttribute('action');
    const fd = new FormData(form);
    if (page) fd.set('page', page);
    const params = new URLSearchParams(fd);
    return base + '?' + params.toString();
  };
  const loadData = (url) => {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(json => {
        if (json.rows) tbody.innerHTML = json.rows;
        if (json.pagination) pager.innerHTML = json.pagination;
      })
      .catch(() => {});
  };
  const trigger = debounce(() => loadData(buildUrl()), 350);
  // Bind search input
  const qInput = form.querySelector('input[name="q"]');
  if (qInput) qInput.addEventListener('input', trigger);
  // Auto-apply for dropdowns and dates
  form.querySelectorAll('select, input[type="date"]').forEach(el => {
    el.addEventListener('change', () => loadData(buildUrl()));
  });
  // Intercept pagination clicks
  document.addEventListener('click', function(e){
    const a = e.target.closest('#appsPagination .pagination a');
    if (a) { e.preventDefault(); const url = a.getAttribute('href'); loadData(url); }
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