@php
  $editing = isset($teacher) && $teacher->id;
  $action = $editing ? route('principal.institute.teachers.update', [$school->id, $teacher->id]) : route('principal.institute.teachers.store', $school->id);
  $method = $editing ? 'PUT' : 'POST';

  // Prepare date fields safely
  $dob = old('date_of_birth');
  if (!$dob && $editing && !empty($teacher->date_of_birth)) {
    try {
      $dob = \Illuminate\Support\Carbon::parse($teacher->date_of_birth)->format('Y-m-d');
    } catch (\Throwable $e) {
      $dob = date('Y-m-d', strtotime($teacher->date_of_birth));
    }
  }

  $joining_date = old('joining_date');
  if (!$joining_date && $editing && !empty($teacher->joining_date)) {
    try {
      $joining_date = \Illuminate\Support\Carbon::parse($teacher->joining_date)->format('Y-m-d');
    } catch (\Throwable $e) {
      $joining_date = date('Y-m-d', strtotime($teacher->joining_date));
    }
  }
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
  @csrf
  @if($editing)
    @method('PUT')
  @endif
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>First Name (English) <span class="text-danger">*</span></label>
      <input name="first_name" class="form-control" required value="{{ old('first_name', $teacher->first_name ?? '') }}">
      @error('first_name')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
    <div class="form-group col-md-6">
      <label>Last Name (English)</label>
      <input name="last_name" class="form-control" value="{{ old('last_name', $teacher->last_name ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>First Name (Bangla)</label>
      <input name="first_name_bn" class="form-control" value="{{ old('first_name_bn', $teacher->first_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Last Name (Bangla)</label>
      <input name="last_name_bn" class="form-control" value="{{ old('last_name_bn', $teacher->last_name_bn ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Father's Name (Bangla)</label>
      <input name="father_name_bn" class="form-control" value="{{ old('father_name_bn', $teacher->father_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Father's Name (English)</label>
      <input name="father_name_en" class="form-control" value="{{ old('father_name_en', $teacher->father_name_en ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Mother's Name (Bangla)</label>
      <input name="mother_name_bn" class="form-control" value="{{ old('mother_name_bn', $teacher->mother_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Mother's Name (English)</label>
      <input name="mother_name_en" class="form-control" value="{{ old('mother_name_en', $teacher->mother_name_en ?? '') }}">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-4">
      <label>Date of Birth</label>
      <input type="date" name="date_of_birth" class="form-control" value="{{ $dob }}">
    </div>
    <div class="form-group col-md-4">
      <label>Joining Date</label>
      <input type="date" name="joining_date" class="form-control" value="{{ $joining_date }}">
    </div>
    <div class="form-group col-md-4">
      <label>Mobile</label>
      <input name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-4">
      <label>Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->user->email ?? '') }}">
    </div>
    <div class="form-group col-md-4">
      <label>Designation</label>
      <input name="designation" class="form-control" value="{{ old('designation', $teacher->designation ?? '') }}">
    </div>
    <div class="form-group col-md-4">
      <label>Initials</label>
      <input name="initials" class="form-control" value="{{ old('initials', $teacher->initials ?? '') }}">
      @error('initials')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>

  @if($editing && isset($teacher->user) && $teacher->user->password_changed_at)
    <div class="alert alert-warning mt-2">
      <i class="fas fa-key mr-1"></i>
      Password last changed: {{ $teacher->user->password_changed_at->format('d/m/Y h:i A') }}
    </div>
  @endif

  <div class="form-row">
    <div class="form-group col-md-4">
      <label>চাকুরী টাইপ</label>
      <select name="job_type" class="form-control">
        <option value="">-- নির্বাচন করুন --</option>
        <option value="এমপিও" {{ old('job_type', $teacher->job_type ?? '') == 'এমপিও' ? 'selected' : '' }}>এমপিও</option>
        <option value="নন-এমপিও" {{ old('job_type', $teacher->job_type ?? '') == 'নন-এমপিও' ? 'selected' : '' }}>নন-এমপিও</option>
        <option value="চুক্তিভিত্তিক" {{ old('job_type', $teacher->job_type ?? '') == 'চুক্তিভিত্তিক' ? 'selected' : '' }}>চুক্তিভিত্তিক</option>
      </select>
    </div>
    <div class="form-group col-md-4">
      <label>অবস্থা (Status)</label>
      <select name="status" class="form-control">
        <option value="active" {{ old('status', $teacher->status ?? 'active') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
        <option value="inactive" {{ old('status', $teacher->status ?? 'active') == 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
      </select>
    </div>
    <div class="form-group col-md-4 d-flex align-items-center mt-4">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="show_on_website" name="show_on_website" value="1" {{ old('show_on_website', $teacher->show_on_website ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="show_on_website" style="cursor: pointer; padding-top: 2px;">ওয়েবসাইটে প্রদর্শন করা হবে</label>
      </div>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Academic Info</label>
      <textarea name="academic_info" class="form-control">{{ old('academic_info', $teacher->academic_info ?? '') }}</textarea>
    </div>
    <div class="form-group col-md-6">
      <label>Qualification</label>
      <textarea name="qualification" class="form-control">{{ old('qualification', $teacher->qualification ?? '') }}</textarea>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-4">
      <label>Photo</label>
      <input type="file" name="photo" accept="image/*" class="form-control-file">
      @if($editing && $teacher->photo)
        <img src="{{ asset('storage/'.$teacher->photo) }}" style="max-height:80px;margin-top:8px;">
      @endif
    </div>
    <div class="form-group col-md-4">
      <label>Signature</label>
      <input type="file" name="signature" accept="image/*" class="form-control-file">
      @if($editing && $teacher->signature)
        <img src="{{ asset('storage/'.$teacher->signature) }}" style="max-height:80px;margin-top:8px;">
      @endif
    </div>
    <div class="form-group col-md-4">
      <label>Serial Number</label>
      <input name="serial_number" class="form-control" value="{{ old('serial_number', $teacher->serial_number ?? '') }}">
    </div>
  </div>

  <div class="row mt-4 bg-light p-3 rounded mx-0">
    <div class="col-md-6 mb-3 mb-md-0">
      <h5 class="border-bottom pb-2 mb-3"><i class="fas fa-map-marker-alt text-primary mr-2"></i>বর্তমান ঠিকানা</h5>
      <div class="form-group">
        <label>বিভাগ</label>
        <select name="present_division_id" id="present_division_id" class="form-control">
          <option value="">-- নির্বাচন করুন --</option>
          @foreach($divisions ?? [] as $div)
            <option value="{{ $div->id }}" {{ old('present_division_id', $teacher->present_division_id ?? '') == $div->id ? 'selected' : '' }}>{{ $div->bn_name ?? $div->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>জেলা</label>
        <select name="present_district_id" id="present_district_id" class="form-control" data-selected="{{ old('present_district_id', $teacher->present_district_id ?? '') }}">
          <option value="">-- নির্বাচন করুন --</option>
        </select>
      </div>
      <div class="form-group">
        <label>উপজেলা/থানা</label>
        <select name="present_thana_id" id="present_thana_id" class="form-control" data-selected="{{ old('present_thana_id', $teacher->present_thana_id ?? '') }}">
          <option value="">-- নির্বাচন করুন --</option>
        </select>
      </div>
      <div class="form-group">
        <label>ডাকঘর</label>
        <input type="text" name="present_post_office" id="present_post_office" class="form-control" value="{{ old('present_post_office', $teacher->present_post_office ?? '') }}">
      </div>
      <div class="form-group">
        <label>গ্রাম/মহল্লা</label>
        <input type="text" name="present_village" id="present_village" class="form-control" value="{{ old('present_village', $teacher->present_village ?? '') }}">
      </div>
    </div>
    <div class="col-md-6">
      <h5 class="border-bottom pb-2 mb-3"><i class="fas fa-home text-success mr-2"></i>স্থায়ী ঠিকানা</h5>
      <div class="custom-control custom-checkbox mb-3">
        <input type="checkbox" class="custom-control-input" id="same_as_present">
        <label class="custom-control-label text-primary font-weight-bold" for="same_as_present" style="cursor: pointer;">বর্তমান ঠিকানার অনুরূপ</label>
      </div>
      <div class="form-group">
        <label>বিভাগ</label>
        <select name="permanent_division_id" id="permanent_division_id" class="form-control">
          <option value="">-- নির্বাচন করুন --</option>
          @foreach($divisions ?? [] as $div)
            <option value="{{ $div->id }}" {{ old('permanent_division_id', $teacher->permanent_division_id ?? '') == $div->id ? 'selected' : '' }}>{{ $div->bn_name ?? $div->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>জেলা</label>
        <select name="permanent_district_id" id="permanent_district_id" class="form-control" data-selected="{{ old('permanent_district_id', $teacher->permanent_district_id ?? '') }}">
          <option value="">-- নির্বাচন করুন --</option>
        </select>
      </div>
      <div class="form-group">
        <label>উপজেলা/থানা</label>
        <select name="permanent_thana_id" id="permanent_thana_id" class="form-control" data-selected="{{ old('permanent_thana_id', $teacher->permanent_thana_id ?? '') }}">
          <option value="">-- নির্বাচন করুন --</option>
        </select>
      </div>
      <div class="form-group">
        <label>ডাকঘর</label>
        <input type="text" name="permanent_post_office" id="permanent_post_office" class="form-control" value="{{ old('permanent_post_office', $teacher->permanent_post_office ?? '') }}">
      </div>
      <div class="form-group">
        <label>গ্রাম/মহল্লা</label>
        <input type="text" name="permanent_village" id="permanent_village" class="form-control" value="{{ old('permanent_village', $teacher->permanent_village ?? '') }}">
      </div>
    </div>
  </div>

  <div class="form-group mt-4">
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-save"></i> {{ $editing ? 'আপডেট করুন' : 'শিক্ষক যুক্ত করুন' }}
    </button>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-secondary btn-lg">
      <i class="fas fa-times"></i> বাতিল
    </a>
  </div>

  <div class="alert alert-info mt-3">
    <i class="fas fa-info-circle"></i>
    <strong>নোট:</strong>
    <span class="text-danger">*</span> চিহ্নিত ফিল্ডগুলি বাধ্যতামূলক।
    Username এবং Password স্বয়ংক্রিয়ভাবে তৈরি হবে।
  </div>
  </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  function fetchLocationData(url, params, callback) {
    const queryString = new URLSearchParams(params).toString();
    fetch(`${url}?${queryString}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => callback(data))
    .catch(error => console.error('Error fetching location data:', error));
  }

  function loadDistricts(divisionId, targetSelectId, selectedDistrictId = '') {
    const targetSelect = document.getElementById(targetSelectId);
    if(!divisionId) {
      targetSelect.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
      return;
    }
    fetchLocationData("{{ route('location.districts') }}", { division_id: divisionId }, function(data) {
      let options = '<option value="">-- নির্বাচন করুন --</option>';
      data.forEach(function(item) {
        let selected = (selectedDistrictId == item.id) ? 'selected' : '';
        options += `<option value="${item.id}" ${selected}>${item.bn_name || item.name}</option>`;
      });
      targetSelect.innerHTML = options;
      // manually trigger change event if needed
      targetSelect.dispatchEvent(new Event('change'));
    });
  }

  function loadThanas(districtId, targetSelectId, selectedThanaId = '') {
    const targetSelect = document.getElementById(targetSelectId);
    if(!districtId) {
      targetSelect.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
      return;
    }
    fetchLocationData("{{ route('location.thanas') }}", { district_id: districtId }, function(data) {
      let options = '<option value="">-- নির্বাচন করুন --</option>';
      data.forEach(function(item) {
        let selected = (selectedThanaId == item.id) ? 'selected' : '';
        options += `<option value="${item.id}" ${selected}>${item.bn_name || item.name}</option>`;
      });
      targetSelect.innerHTML = options;
    });
  }

  // Element references
  const presentDivision = document.getElementById('present_division_id');
  const presentDistrict = document.getElementById('present_district_id');
  const presentThana = document.getElementById('present_thana_id');
  
  const permanentDivision = document.getElementById('permanent_division_id');
  const permanentDistrict = document.getElementById('permanent_district_id');
  const permanentThana = document.getElementById('permanent_thana_id');
  
  const presentPost = document.getElementById('present_post_office');
  const presentVill = document.getElementById('present_village');
  const permanentPost = document.getElementById('permanent_post_office');
  const permanentVill = document.getElementById('permanent_village');
  const sameAsPresent = document.getElementById('same_as_present');

  // Present Address Triggers
  if(presentDivision) {
    presentDivision.addEventListener('change', function() {
      loadDistricts(this.value, 'present_district_id');
      presentThana.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
    });
  }
  if(presentDistrict) {
    presentDistrict.addEventListener('change', function() {
      loadThanas(this.value, 'present_thana_id');
    });
  }

  // Permanent Address Triggers
  if(permanentDivision) {
    permanentDivision.addEventListener('change', function() {
      loadDistricts(this.value, 'permanent_district_id');
      permanentThana.innerHTML = '<option value="">-- নির্বাচন করুন --</option>';
    });
  }
  if(permanentDistrict) {
    permanentDistrict.addEventListener('change', function() {
      loadThanas(this.value, 'permanent_thana_id');
    });
  }

  // Same as Present Checkbox
  if(sameAsPresent) {
    sameAsPresent.addEventListener('change', function() {
      const permaFields = [permanentDivision, permanentDistrict, permanentThana, permanentPost, permanentVill];
      
      if(this.checked) {
        permanentDivision.value = presentDivision.value;
        permanentDivision.dispatchEvent(new Event('change'));
        
        setTimeout(() => {
          permanentDistrict.value = presentDistrict.value;
          permanentDistrict.dispatchEvent(new Event('change'));
          setTimeout(() => {
            permanentThana.value = presentThana.value;
          }, 500);
        }, 500);

        permanentPost.value = presentPost.value;
        permanentVill.value = presentVill.value;
        
        // Disable permanent fields
        permaFields.forEach(f => {
          if(f) {
            f.readOnly = true;
            f.style.pointerEvents = 'none';
          }
        });
      } else {
        // Re-enable
        permaFields.forEach(f => {
          if(f) {
            f.readOnly = false;
            f.style.pointerEvents = 'auto';
          }
        });
      }
    });
  }

  // Initial Load
  if(presentDivision && presentDivision.value) {
    loadDistricts(presentDivision.value, 'present_district_id', presentDistrict.getAttribute('data-selected'));
  }
  if(presentDistrict && presentDistrict.getAttribute('data-selected')) {
    setTimeout(() => {
        loadThanas(presentDistrict.getAttribute('data-selected'), 'present_thana_id', presentThana.getAttribute('data-selected'));
    }, 500);
  }
  
  if(permanentDivision && permanentDivision.value) {
    loadDistricts(permanentDivision.value, 'permanent_district_id', permanentDistrict.getAttribute('data-selected'));
  }
  if(permanentDistrict && permanentDistrict.getAttribute('data-selected')) {
    setTimeout(() => {
        loadThanas(permanentDistrict.getAttribute('data-selected'), 'permanent_thana_id', permanentThana.getAttribute('data-selected'));
    }, 500);
  }
});
</script>
@endpush
