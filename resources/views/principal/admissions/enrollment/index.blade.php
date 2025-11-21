@extends('layouts.admin')
@section('title', 'Confirm Enrollment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-check mr-1"></i> ভর্তি নিশ্চিতকরণ</h1>
  <div>
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
    <h5 class="mb-0">আবেদনকৃত শিক্ষার্থীদের তালিকা</h5>
    <small class="text-muted">গৃহীত ও পেমেন্ট সম্পন্ন শিক্ষার্থীরা</small>
  </div>
  <div class="card-body p-0">
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
              <th>রোল নং</th>
              <th>নাম (বাংলা)</th>
              <th>নাম (English)</th>
              <th>ক্লাস</th>
              <th>পিতার নাম</th>
              <th>মাতার নাম</th>
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
                <td>{{ $app->admission_roll_no ? str_pad($app->admission_roll_no, 3, '0', STR_PAD_LEFT) : '—' }}</td>
                <td><strong>{{ $app->name_bn }}</strong></td>
                <td>{{ $app->name_en }}</td>
                <td><span class="badge badge-primary">{{ $app->class_name }}</span></td>
                <td>{{ $app->father_name_bn }}</td>
                <td>{{ $app->mother_name_bn }}</td>
                <td>{{ $app->mobile }}</td>
                <td class="text-center">
                  <button type="button" class="btn btn-success btn-sm" onclick="openEnrollmentModal({{ $app->id }})">
                    <i class="fas fa-check-circle"></i> ভর্তি করুন
                  </button>
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
