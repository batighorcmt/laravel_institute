@extends('layouts.admin')
@section('title', 'Confirm Enrollment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-check mr-1"></i> ভর্তি নিশ্চিতকরণ</h1>
  <div>
    @php($qs = isset($filters) && is_array($filters) ? http_build_query($filters) : '')
    <a href="{{ route('principal.institute.admissions.enrollment.print', $school) }}@if($qs)?{{ $qs }}@endif" target="_blank" class="btn btn-outline-primary mr-2"><i class="fas fa-print mr-1"></i> প্রিন্ট</a>
    <a href="{{ route('principal.institute.admissions.applications', $school) }}" class="btn btn-outline-secondary">আবেদন তালিকা</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
@endif

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <h5 class="mb-0">আবেদনকৃত শিক্ষার্থীদের তালিকা</h5>
        <small class="text-muted">গৃহীত ও আবেদন ফিস পরিশোধিত (ভর্তি হয়নি)</small>
      </div>
      <form class="form-inline mt-2 mt-md-0" method="GET" action="{{ route('principal.institute.admissions.enrollment.index', $school) }}">
        <div class="form-group mr-2 mb-2">
          <label for="filter_class" class="mr-2">ক্লাস</label>
          <select name="class" id="filter_class" class="form-control form-control-sm">
            <option value="">সকল</option>
            @isset($classes)
              @foreach($classes as $cls)
                <option value="{{ $cls }}" {{ (isset($filters['class']) && $filters['class'] === (string)$cls) ? 'selected' : '' }}>{{ $cls }}</option>
              @endforeach
            @endisset
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label for="filter_perm" class="mr-2">অনুমতি</label>
          <select name="permission" id="filter_perm" class="form-control form-control-sm">
            <option value="" {{ (isset($filters['permission']) && $filters['permission']==='') ? 'selected' : '' }}>সকল</option>
            <option value="1" {{ (isset($filters['permission']) && $filters['permission']==='1') ? 'selected' : '' }}>অনুমোদিত</option>
            <option value="0" {{ (isset($filters['permission']) && $filters['permission']==='0') ? 'selected' : '' }}>অননুমোদিত</option>
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label for="filter_fee" class="mr-2">ফিস</label>
          <select name="fee_status" id="filter_fee" class="form-control form-control-sm">
            <option value="" {{ (isset($filters['fee_status']) && $filters['fee_status']==='') ? 'selected' : '' }}>সকল</option>
            <option value="paid" {{ (isset($filters['fee_status']) && $filters['fee_status']==='paid') ? 'selected' : '' }}>পরিশোধিত</option>
            <option value="unpaid" {{ (isset($filters['fee_status']) && $filters['fee_status']==='unpaid') ? 'selected' : '' }}>অপরিশোধিত</option>
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="নাম/আবেদন আইডি/মোবাইল">
        </div>
        <div class="form-group mb-2">
          <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-search mr-1"></i>ফিল্টার</button>
          <a href="{{ route('principal.institute.admissions.enrollment.index', $school) }}" class="btn btn-sm btn-outline-secondary">রিসেট</a>
        </div>
      </form>
    </div>
  </div>
  <div class="card-body p-0">
    @isset($stats)
      <div class="p-3">
        <div class="row text-center">
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">অনুমতি দেওয়া হয়েছে</div>
              <div class="h5 mb-0">{{ number_format($stats['permittedCount'] ?? 0) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">ভর্তি ফিস জমা</div>
              <div class="h5 mb-0">{{ number_format($stats['paidCount'] ?? 0) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">বাকি (জন)</div>
              <div class="h5 mb-0">{{ number_format($stats['dueCount'] ?? 0) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">মোট জমা (৳)</div>
              <div class="h5 mb-0">{{ number_format($stats['totalCollected'] ?? 0, 2) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">মোট নির্ধারিত (৳)</div>
              <div class="h5 mb-0">{{ number_format($stats['totalAssigned'] ?? 0, 2) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="border rounded py-2">
              <div class="text-muted small">বাকি (৳)</div>
              <div class="h5 mb-0">{{ number_format($stats['totalDue'] ?? 0, 2) }}</div>
            </div>
          </div>
        </div>
        <small class="text-muted d-block mt-1">নোট: ভর্তি ফিস পরিসংখ্যান কেবল এই পাতার জন্য; আবেদন তালিকার পরিসংখ্যান আলাদা থাকে।</small>
      </div>
    @endisset
    @if($applications->isEmpty())
      <div class="alert alert-info text-center m-3">
        <i class="fas fa-info-circle fa-2x mb-2"></i>
        <p class="mb-0">কোনো শিক্ষার্থী ভর্তির জন্য প্রস্তুত নেই।</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="thead-dark">
            <tr>
              <th style="width:50px">#</th>
              <th style="width:80px">ছবি</th>
              <th>আবেদন আইডি নং</th>
              <th>রোল নং</th>
              <th>নাম (বাংলা)</th>
              <th>নাম (English)</th>
              <th>ক্লাস</th>
              <th>মেধাক্রম</th>
              <th>ভর্তি অনুমতি</th>
              <th>ফিসের অবস্থা</th>
              <th>মোবাইল</th>
              <th style="width:120px">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($applications as $app)
              <tr id="row-{{ $app->id }}">
                <td>{{ ($loop->iteration + ($applications->currentPage()-1)*$applications->perPage()) }}</td>
                <td>
                  <img src="{{ $app->photo ? asset('storage/admission/'.$app->photo) : asset('images/default-avatar.png') }}" 
                       alt="Photo" 
                       class="img-thumbnail" 
                       style="width:60px; height:75px; object-fit:cover;">
                </td>
                <td>{{ $app->app_id ?? $app->id }}</td>
                <td>{{ $app->admission_roll_no ? str_pad($app->admission_roll_no, 3, '0', STR_PAD_LEFT) : '—' }}</td>
                <td><strong>{{ $app->name_bn }}</strong></td>
                <td>{{ $app->name_en }}</td>
                <td><span class="badge badge-primary">{{ $app->class_name }}</span></td>
                <td>
                  @php($merit = isset($app->merit_rank) ? $app->merit_rank : (isset($app->admission_merit_position) ? $app->admission_merit_position : null))
                  {{ $merit ? $merit : '—' }}
                </td>
                <td>
                  @php($permissionGranted = isset($app->admission_permission) ? (bool)$app->admission_permission : false)
                  @if($app->student_id)
                    <span class="badge badge-info">ভর্তি সম্পন্ন</span>
                  @else
                    <span class="badge {{ $permissionGranted ? 'badge-success' : 'badge-secondary' }}" id="perm-badge-{{ $app->id }}">
                      {{ $permissionGranted ? 'অনুমোদিত' : 'অননুমোদিত' }}
                    </span>
                    @php($admissionFeePaid = isset($app->admission_fee_paid) ? (bool)$app->admission_fee_paid : false)
                    @if(!$admissionFeePaid)
                      <button type="button" class="btn btn-outline-info btn-sm ml-2" onclick="openPermissionModal({{ $app->id }})">
                        অনুমতি দিন
                      </button>
                    @endif
                  @endif
                </td>
                <td>
                  @php($admissionFeePaid = isset($app->admission_fee_paid) ? (bool)$app->admission_fee_paid : false)
                  <span class="badge {{ $admissionFeePaid ? 'badge-success' : 'badge-warning' }}" id="fee-badge-{{ $app->id }}">
                    {{ $admissionFeePaid ? 'পরিশোধিত' : 'অপরিশোধিত' }}
                  </span>
                  @if(!$admissionFeePaid)
                    <form method="POST" action="{{ route('enrollment.fee.pay', [$school->id, $app->id]) }}" style="margin-top:6px;">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-success">পেমেন্ট করুন</button>
                    </form>
                  @endif
                </td>
                <td>{{ $app->mobile }}</td>
                <td class="text-center">
                  @if($app->student_id)
                    <a href="{{ route('principal.institute.students.show', [$school, $app->student_id]) }}" class="btn btn-primary btn-sm">
                      <i class="fas fa-id-card"></i> শিক্ষার্থী প্রোফাইল
                    </a>
                  @else
                    <button type="button" class="btn btn-success btn-sm enroll-btn" data-app-id="{{ $app->id }}" data-adm-fee-paid="{{ $admissionFeePaid ? 1 : 0 }}">
                      <i class="fas fa-check-circle"></i> ভর্তি করুন
                    </button>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
  @if(!$applications->isEmpty())
    <div class="card-footer">
      {{ $applications->links() }}
    </div>
  @endif
</div>

<!-- Enrollment Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1" role="dialog" aria-labelledby="enrollmentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ route('principal.institute.admissions.enrollment.store', $school) }}" id="enrollmentForm">
        @csrf
        <input type="hidden" name="application_id" id="modal_application_id">
        
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="enrollmentModalLabel">
            <i class="fas fa-user-graduate mr-2"></i>শিক্ষার্থী ভর্তি করুন
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body">
          <div id="modalLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3">Loading...</p>
          </div>
          
          <div id="modalContent" style="display:none;">
            <div class="row">
              <div class="col-md-8">
                <h6 class="mb-3"><i class="fas fa-user mr-2"></i>শিক্ষার্থীর তথ্য</h6>
                <table class="table table-sm table-bordered">
                  <tr>
                    <td width="35%"><strong>নাম (বাংলা)</strong></td>
                    <td id="student_name_bn"></td>
                  </tr>
                  <tr>
                    <td><strong>নাম (English)</strong></td>
                    <td id="student_name_en"></td>
                  </tr>
                  <tr>
                    <td><strong>পিতার নাম</strong></td>
                    <td id="father_name"></td>
                  </tr>
                  <tr>
                    <td><strong>মাতার নাম</strong></td>
                    <td id="mother_name"></td>
                  </tr>
                </table>
              </div>
              <div class="col-md-4 text-center">
                <img id="student_photo" src="" alt="Photo" class="img-thumbnail" style="width:150px; height:180px; object-fit:cover;">
              </div>
            </div>

            <hr>

            <h6 class="mb-3"><i class="fas fa-graduation-cap mr-2"></i>ভর্তি তথ্য</h6>
            
            <div class="form-group">
              <label for="modal_class_name">ক্লাস <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="modal_class_name" readonly style="background:#f0f0f0; font-weight:bold;">
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="section_id">শাখা/সেকশন</label>
                <select name="section_id" id="section_id" class="form-control">
                  <option value="">-- নির্বাচন করুন --</option>
                </select>
                <small class="text-muted">ঐচ্ছিক</small>
              </div>
              
              <div class="form-group col-md-6">
                <label for="group_id">গ্রুপ <span id="group_required_mark" class="text-danger" style="display:none;">*</span></label>
                <select name="group_id" id="group_id" class="form-control">
                  <option value="">-- নির্বাচন করুন --</option>
                </select>
                <small class="text-muted" id="group_hint"></small>
              </div>
            </div>

            <div class="form-group">
              <label for="roll_no">শ্রেণি রোল নম্বর <span class="text-danger">*</span></label>
              <input type="number" name="roll_no" id="roll_no" class="form-control" required min="1" placeholder="রোল নং প্রদান করুন">
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
          <button type="submit" class="btn btn-success" id="submitEnrollBtn">
            <i class="fas fa-save mr-1"></i>ভর্তি সম্পন্ন করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Admission Permission Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1" role="dialog" aria-labelledby="permissionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ url('principal/institute/'.$school->id.'/admissions/permission/store') }}" id="permissionForm">
        @csrf
        <input type="hidden" name="application_id" id="perm_application_id">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="permissionModalLabel"><i class="fas fa-user-shield mr-2"></i>ভর্তি অনুমতি সেট করুন</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="permLoading" class="text-center py-4" style="display:none;">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading...</p>
          </div>
          <div id="permContent">
            <div class="form-group">
              <label class="d-block">ভর্তি অনুমতি</label>
              <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-outline-success" id="permYesLabel">
                  <input type="radio" name="permission" id="permYes" value="1"> হ্যাঁ (অনুমোদিত)
                </label>
                <label class="btn btn-outline-danger" id="permNoLabel">
                  <input type="radio" name="permission" id="permNo" value="0"> না (অননুমোদিত)
                </label>
              </div>
            </div>
            <div class="form-group">
              <label for="admission_fee">ভর্তি ফিস (৳) <span class="text-danger">*</span></label>
              <input type="number" min="0" step="1" class="form-control" name="admission_fee" id="admission_fee" placeholder="ফিসের পরিমাণ নির্ধারণ করুন" required>
              <small class="text-muted">ফিস নির্ধারণ করলে শিক্ষার্থী পেমেন্ট করতে পারবে</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
          <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>সংরক্ষণ করুন</button>
        </div>
      </form>
    </div>
  </div>
  </div>

@push('scripts')
<script>
let currentRequireGroup = false;

function openEnrollmentModal(applicationId) {
  // Use vanilla JS if jQuery fails
  if (typeof jQuery !== 'undefined') {
    $('#modal_application_id').val(applicationId);
    $('#enrollmentModal').modal('show');
    $('#modalLoading').show();
    $('#modalContent').hide();
  } else {
    document.getElementById('modal_application_id').value = applicationId;
    const modal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
    modal.show();
    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalContent').style.display = 'none';
  }
  
  // Fetch application details - build URL manually to avoid binding issues
  const baseUrl = '{{ url("principal/institute/{$school->id}/admissions/enrollment") }}';
  const url = `${baseUrl}/${applicationId}/data`;
  
  console.log('Fetching URL:', url);
  
  fetch(url, {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error('Network response was not ok: ' + response.status);
      }
      return response.json();
    })
    .then(data => {
      console.log('Received data:', data);
      if (data.success) {
        // Populate student info using vanilla JS
        document.getElementById('student_name_bn').textContent = data.application.name_bn || '—';
        document.getElementById('student_name_en').textContent = data.application.name_en || '—';
        document.getElementById('father_name').textContent = data.application.father_name_bn || data.application.father_name_en || '—';
        document.getElementById('mother_name').textContent = data.application.mother_name_bn || data.application.mother_name_en || '—';
        
        const photoUrl = data.application.photo 
          ? '{{ asset("storage/admission") }}/' + data.application.photo
          : '{{ asset("images/default-avatar.png") }}';
        document.getElementById('student_photo').src = photoUrl;
        
        // Set class (readonly)
        document.getElementById('modal_class_name').value = data.application.class_name;
        
        // Populate sections
        const sectionSelect = document.getElementById('section_id');
        sectionSelect.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
        data.sections.forEach(section => {
          const option = document.createElement('option');
          option.value = section.id;
          option.textContent = section.name;
          sectionSelect.appendChild(option);
        });
        
        // Populate groups
        const groupSelect = document.getElementById('group_id');
        groupSelect.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
        data.groups.forEach(group => {
          const option = document.createElement('option');
          option.value = group.id;
          option.textContent = group.name;
          groupSelect.appendChild(option);
        });
        
        // Handle group requirement
        currentRequireGroup = data.requireGroup;
        const groupRequiredMark = document.getElementById('group_required_mark');
        const groupHint = document.getElementById('group_hint');
        
        if (data.requireGroup) {
          groupRequiredMark.style.display = 'inline';
          groupSelect.setAttribute('required', 'required');
          groupHint.innerHTML = '<span class="text-danger">ক্লাস ৯ম ও ১০ম এর জন্য বাধ্যতামূলক</span>';
        } else {
          groupRequiredMark.style.display = 'none';
          groupSelect.removeAttribute('required');
          groupHint.textContent = 'ঐচ্ছিক';
        }
        
        // Show content
        document.getElementById('modalLoading').style.display = 'none';
        document.getElementById('modalContent').style.display = 'block';
      } else {
        console.error('Server returned error:', data);
        alert('Error loading data: ' + (data.message || 'Unknown error'));
        // Close modal
        const modalEl = document.getElementById('enrollmentModal');
        if (typeof bootstrap !== 'undefined') {
          bootstrap.Modal.getInstance(modalEl).hide();
        }
      }
    })
    .catch(error => {
      console.error('Error details:', error);
      alert('Failed to load enrollment data. Check console for details.');
      // Close modal
      const modalEl = document.getElementById('enrollmentModal');
      if (typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
      }
    });
}

// Open permission modal
function openPermissionModal(applicationId) {
  // Set application id
  if (typeof jQuery !== 'undefined') {
    $('#perm_application_id').val(applicationId);
    $('#permissionModal').modal('show');
  } else {
    document.getElementById('perm_application_id').value = applicationId;
    const modal = new bootstrap.Modal(document.getElementById('permissionModal'));
    modal.show();
  }

  // Prefill from server (optional)
  const baseUrl = '{{ url("principal/institute/{$school->id}/admissions/permission") }}';
  const url = `${baseUrl}/${applicationId}/data`;

  // Show loading state
  document.getElementById('permLoading').style.display = 'block';
  document.getElementById('permContent').style.display = 'none';

  fetch(url, {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(data => {
      // Populate UI if data available
      const hasPermission = !!(data.permission);
      const fee = data.admission_fee || 0;
      document.getElementById('admission_fee').value = fee;
      // Toggle buttons
      if (hasPermission) {
        document.getElementById('permYes').checked = true;
        document.getElementById('permYesLabel').classList.add('active');
        document.getElementById('permNoLabel').classList.remove('active');
      } else {
        document.getElementById('permNo').checked = true;
        document.getElementById('permNoLabel').classList.add('active');
        document.getElementById('permYesLabel').classList.remove('active');
      }
    })
    .catch(() => {
      // Silently ignore; allow manual input
    })
    .finally(() => {
      document.getElementById('permLoading').style.display = 'none';
      document.getElementById('permContent').style.display = 'block';
    });
}

// Guard: prevent enrollment if admission fee unpaid
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.enroll-btn');
  if (!btn) return;
  const paid = btn.getAttribute('data-adm-fee-paid') === '1';
  if (!paid) {
    alert('ভর্তি ফিস পরিশোধ করা হয়নি। আগে অনুমতি ও ফিস নির্ধারণ করুন।');
    return;
  }
  const appId = btn.getAttribute('data-app-id');
  openEnrollmentModal(appId);
});

// Permission form validation + optimistic UI update
document.getElementById('permissionForm').addEventListener('submit', function(e) {
  const yesChecked = document.getElementById('permYes').checked;
  const noChecked = document.getElementById('permNo').checked;
  if (!yesChecked && !noChecked) {
    e.preventDefault();
    alert('অনুগ্রহ করে অনুমতি হ্যাঁ/না নির্বাচন করুন');
    return false;
  }

  const fee = Number(document.getElementById('admission_fee').value || 0);
  if (fee < 0) {
    e.preventDefault();
    alert('ফিসের পরিমাণ শূন্য বা তার বেশি হতে হবে');
    return false;
  }

  // After successful submit (handled by server), we can optimistically update UI
  // This block relies on server redirect with success flash; for SPA you would use fetch.
  // No extra JS here to avoid double submission issues.
});

// Form validation
document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
  if (currentRequireGroup) {
    const groupId = document.getElementById('group_id').value;
    if (!groupId) {
      e.preventDefault();
      alert('ক্লাস ৯ম ও ১০ম এর জন্য গ্রুপ নির্বাচন বাধ্যতামূলক');
      return false;
    }
  }
  
  const rollNo = document.getElementById('roll_no').value;
  if (!rollNo || rollNo < 1) {
    e.preventDefault();
    alert('অনুগ্রহ করে বৈধ রোল নম্বর প্রদান করুন');
    return false;
  }
});
</script>
@endpush

@endsection
