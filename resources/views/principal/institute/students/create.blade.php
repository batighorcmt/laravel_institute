@extends('layouts.admin')
@section('title','নতুন শিক্ষার্থী')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">নতুন শিক্ষার্থী ভর্তি</h1>
  <a href="{{ route('principal.institute.students.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card shadow-lg">
  <div class="card-body">
    <form method="post" action="{{ route('principal.institute.students.store',$school) }}" enctype="multipart/form-data" id="studentForm">@csrf

      <div class="mb-4">
        <h5 class="mb-2"><i class="fas fa-graduation-cap mr-2"></i>ভর্তি তথ্য</h5>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-calendar-alt mr-1"></i>শিক্ষাবর্ষ</label>
              <select name="enroll_academic_year_id" class="form-control">
                <option value="">-- নির্বাচন --</option>
                @foreach($years as $y)
                  <option value="{{ $y->id }}" {{ ($currentYear && $currentYear->id===$y->id)?'selected':'' }}>{{ $y->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-school mr-1"></i>শ্রেণি</label>
              <select name="enroll_class_id" id="enroll_class_id" class="form-control">
                <option value="">-- নির্বাচন --</option>
                @foreach(\App\Models\SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get() as $c)
                  <option value="{{ $c->id }}" {{ old('enroll_class_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label><i class="fas fa-code-branch mr-1"></i>শাখা</label>
              <select name="enroll_section_id" id="enroll_section_id" class="form-control">
                <option value="">--</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label><i class="fas fa-users mr-1"></i>গ্রুপ</label>
              <select name="enroll_group_id" id="enroll_group_id" class="form-control">
                <option value="">--</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label><i class="fas fa-hashtag mr-1"></i>রোল</label>
              <input type="number" name="enroll_roll_no" id="enroll_roll_no" class="form-control" value="{{ old('enroll_roll_no') }}" min="1">
              <small id="roll-hint" class="form-text text-muted"></small>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <h5 class="mb-2"><i class="fas fa-user mr-2"></i>ব্যক্তিগত তথ্য</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-user-edit mr-1"></i>নাম (English)</label>
              <input type="text" name="student_name_en" class="form-control" value="{{ old('student_name_en') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-user-edit mr-1"></i>নাম (বাংলা) *</label>
              <input type="text" name="student_name_bn" class="form-control" required value="{{ old('student_name_bn') }}">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-birthday-cake mr-1"></i>জন্ম তারিখ *</label>
              <input type="date" name="date_of_birth" class="form-control" required value="{{ old('date_of_birth') }}">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-venus-mars mr-1"></i>লিঙ্গ *</label>
              <select name="gender" class="form-control" required>
                <option value="male" {{ old('gender')=='male'?'selected':'' }}>ছেলে</option>
                <option value="female" {{ old('gender')=='female'?'selected':'' }}>মেয়ে</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-praying-hands mr-1"></i>ধর্ম</label>
              <select name="religion" class="form-control">
                <option value="">-- নির্বাচন --</option>
                @php($religions=['Islam'=>'ইসলাম','Hindu'=>'হিন্দু','Buddhist'=>'বৌদ্ধ','Christian'=>'খ্রিস্টান','Other'=>'অন্যান্য'])
                @foreach($religions as $val=>$label)
                  <option value="{{ $val }}" {{ old('religion')==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label><i class="fas fa-tint mr-1"></i>রক্তের গ্রুপ</label>
              <select name="blood_group" class="form-control">
                <option value="">-- নির্বাচন --</option>
                @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                  <option value="{{ $bg }}" {{ old('blood_group')==$bg?'selected':'' }}>{{ $bg }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <h5 class="mb-2"><i class="fas fa-users mr-2"></i>অভিভাবকের তথ্য</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-male mr-1"></i>পিতার নাম (English) *</label>
              <input type="text" name="father_name" class="form-control" required value="{{ old('father_name') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-male mr-1"></i>পিতার নাম (বাংলা) *</label>
              <input type="text" name="father_name_bn" class="form-control" required value="{{ old('father_name_bn') }}">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-female mr-1"></i>মাতার নাম (English) *</label>
              <input type="text" name="mother_name" class="form-control" required value="{{ old('mother_name') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-female mr-1"></i>মাতার নাম (বাংলা) *</label>
              <input type="text" name="mother_name_bn" class="form-control" required value="{{ old('mother_name_bn') }}">
            </div>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-phone mr-1"></i>অভিভাবকের ফোন *</label>
              <input type="text" name="guardian_phone" class="form-control" required value="{{ old('guardian_phone') }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>Guardian Relation</label>
              <select id="guardian_relation" name="guardian_relation" class="form-control">
                <option value="">-- নির্বাচন --</option>
                <option value="father" {{ old('guardian_relation')=='father'?'selected':'' }}>Father</option>
                <option value="mother" {{ old('guardian_relation')=='mother'?'selected':'' }}>Mother</option>
                <option value="other" {{ old('guardian_relation')=='other'?'selected':'' }}>Other</option>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>Guardian Name (English)</label>
              <input id="guardian_name_en" name="guardian_name_en" class="form-control" value="{{ old('guardian_name_en') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Guardian Name (Bangla)</label>
              <input id="guardian_name_bn" name="guardian_name_bn" class="form-control" value="{{ old('guardian_name_bn') }}">
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <!-- Detailed present/permanent address fields (composed into hidden address fields) -->
              <div class="row g-2">
                <div class="col-12"><strong>Present Address</strong></div>
                <div class="col-md-6"><input id="present_village" name="present_village" class="form-control" placeholder="গ্রাম/এলাকা" value="{{ old('present_village') }}" /></div>
                <div class="col-md-6"><input id="present_para_moholla" name="present_para_moholla" class="form-control" placeholder="পাড়া/মহল্লা" value="{{ old('present_para_moholla') }}" /></div>
                <div class="col-md-4 mt-2"><input id="present_post_office" name="present_post_office" class="form-control" placeholder="পোস্ট অফিস" value="{{ old('present_post_office') }}" /></div>
                <div class="col-md-4 mt-2"><input id="present_upazilla" name="present_upazilla" class="form-control" placeholder="উপজেলা" value="{{ old('present_upazilla') }}" /></div>
                <div class="col-md-4 mt-2"><input id="present_district" name="present_district" class="form-control" placeholder="জেলা" value="{{ old('present_district') }}" /></div>

                <div class="col-12 mt-3"><strong>Permanent Address</strong></div>
                <div class="col-12 mb-2 form-check">
                  <input type="checkbox" id="same_as_present" class="form-check-input" />
                  <label class="form-check-label" for="same_as_present">Present ঠিকানাটি Permanent-এর সাথে কপি করুন</label>
                </div>
                <div class="col-md-6"><input id="permanent_village" name="permanent_village" class="form-control" placeholder="গ্রাম/এলাকা" value="{{ old('permanent_village') }}" /></div>
                <div class="col-md-6"><input id="permanent_para_moholla" name="permanent_para_moholla" class="form-control" placeholder="পাড়া/মহল্লা" value="{{ old('permanent_para_moholla') }}" /></div>
                <div class="col-md-4 mt-2"><input id="permanent_post_office" name="permanent_post_office" class="form-control" placeholder="পোস্ট অফিস" value="{{ old('permanent_post_office') }}" /></div>
                <div class="col-md-4 mt-2"><input id="permanent_upazilla" name="permanent_upazilla" class="form-control" placeholder="উপজেলা" value="{{ old('permanent_upazilla') }}" /></div>
                <div class="col-md-4 mt-2"><input id="permanent_district" name="permanent_district" class="form-control" placeholder="জেলা" value="{{ old('permanent_district') }}" /></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Photo + Previous Education -->
      <div class="mb-4">
        <h5 class="mb-2"><i class="fas fa-image mr-2"></i>ছবি ও পূর্ববর্তী শিক্ষা</h5>
        <div class="row">
          <div class="col-md-4 text-center">
            <div class="photo-preview-wrapper mb-2" style="width:160px;height:160px;margin:0 auto;border:2px dashed #ddd;display:flex;align-items:center;justify-content:center;">
              <img id="photoPreview" src="{{ asset('images/default-avatar.png') }}" alt="photo" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <input type="file" name="photo" id="photoInput" class="form-control-file" accept="image/*" onchange="previewImage(event)">
            <div class="small text-muted mt-2">35x45mm (~413x531px), ≤1MB</div>
          </div>
          <div class="col-md-8">
            <div class="row g-2">
              <div class="col-md-6">
                <label>Previous School</label>
                <input type="text" name="previous_school" class="form-control" value="{{ old('previous_school') }}">
              </div>
              <div class="col-md-3">
                <label>Passing Year</label>
                <input type="text" name="pass_year" id="pass_year" class="form-control" value="{{ old('pass_year') }}">
              </div>
              <div class="col-md-3">
                <label>Result / Grade</label>
                <input type="text" name="previous_result" class="form-control" value="{{ old('previous_result') }}">
              </div>
              <div class="col-12 mt-3">
                <label>সংযুক্ত ঠিকানা (Legacy Address Field) (ঐচ্ছিক)</label>
                <textarea name="address" rows="1" class="form-control">{{ old('address') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <h5 class="mb-2"><i class="fas fa-file-alt mr-2"></i>অফিসিয়াল</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-calendar-check mr-1"></i>ভর্তি তারিখ *</label>
              <input type="date" name="admission_date" class="form-control" required value="{{ old('admission_date') }}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-toggle-on mr-1"></i>স্ট্যাটাস *</label>
              <select name="status" class="form-control" required>
                <option value="active" {{ old('status')=='active'?'selected':'' }}>Active</option>
                <option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>Inactive</option>
                <option value="graduated" {{ old('status')=='graduated'?'selected':'' }}>Graduated</option>
                <option value="transferred" {{ old('status')=='transferred'?'selected':'' }}>Transferred</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const schoolId = {{ $school->id }};
  const classSel = document.getElementById('enroll_class_id');
  const sectionSel = document.getElementById('enroll_section_id');
  const groupSel = document.getElementById('enroll_group_id');
  const yearInput = document.querySelector('[name="enroll_academic_year"]');
  const rollInput = document.getElementById('enroll_roll_no');
  const rollHint = document.getElementById('roll-hint');

  function fetchJSON(url, params, cb){
    const usp = new URLSearchParams(params);
    fetch(url + '?' + usp.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.json())
      .then(cb)
      .catch(()=>{});
  }

  function loadSections(){
    if(!sectionSel) return;
    sectionSel.innerHTML = '<option value="">--</option>';
    const cid = classSel.value;
    if(!cid) return;
    fetchJSON("{{ route('principal.institute.meta.sections',$school) }}", {class_id: cid}, data => {
      data.forEach(s=>{
        sectionSel.insertAdjacentHTML('beforeend', `<option value="${s.id}">${s.name}</option>`);
      });
    });
  }
  function loadGroups(){
    if(!groupSel) return;
    groupSel.innerHTML = '<option value="">--</option>';
    const cid = classSel.value;
    if(!cid) return;
    fetchJSON("{{ route('principal.institute.meta.groups',$school) }}", {class_id: cid}, data => {
      data.forEach(g=>{
        groupSel.insertAdjacentHTML('beforeend', `<option value="${g.id}">${g.name}</option>`);
      });
    });
  }
  function loadNextRoll(){
    if(!rollHint || !rollInput) return;
    const year = yearInput ? yearInput.value : '';
    const cid = classSel ? classSel.value : '';
    if(!year || !cid) { rollHint.textContent=''; return; }
    fetchJSON("{{ route('principal.institute.meta.next-roll',$school) }}", {year: year, class_id: cid, section_id: sectionSel ? sectionSel.value : '', group_id: groupSel ? groupSel.value : ''}, data => {
      if(data && data.next){
        rollHint.textContent = 'পরবর্তী রোল: ' + data.next;
        if(!rollInput.value){ rollInput.value = data.next; }
      }
    });
  }

  if(classSel){ classSel.addEventListener('change', ()=>{ loadSections(); loadGroups(); setTimeout(loadNextRoll,300); }); }
  if(sectionSel){ sectionSel.addEventListener('change', loadNextRoll); }
  if(groupSel){ groupSel.addEventListener('change', loadNextRoll); }
  if(yearInput){ yearInput.addEventListener('input', loadNextRoll); }
});
</script>
@endpush

@push('scripts')
<script>
// Image preview and client-side processing (based on admission apply page)
function loadImageBitmap(file) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = URL.createObjectURL(file);
  });
}

async function processPassportPhoto(file, targetW, targetH, maxBytes) {
  const img = await loadImageBitmap(file);
  const srcW = img.naturalWidth || img.width;
  const srcH = img.naturalHeight || img.height;
  const targetAspect = targetW / targetH;
  const srcAspect = srcW / srcH;
  let sx, sy, sw, sh;
  if (srcAspect > targetAspect) {
    sh = srcH; sw = Math.round(srcH * targetAspect);
    sx = Math.round((srcW - sw) / 2); sy = 0;
  } else {
    sw = srcW; sh = Math.round(srcW / targetAspect);
    sx = 0; sy = Math.round((srcH - sh) / 2);
  }
  const canvas = document.createElement('canvas');
  canvas.width = targetW; canvas.height = targetH;
  const ctx = canvas.getContext('2d');
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';
  ctx.drawImage(img, sx, sy, sw, sh, 0, 0, targetW, targetH);

  let quality = 0.85;
  let blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', quality));
  while (blob && blob.size > maxBytes && quality > 0.6) {
    quality -= 0.05;
    blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', quality));
  }
  if (!blob) throw new Error('Failed to compress image');
  return new File([blob], file.name || 'photo.jpg', { type: 'image/jpeg' });
}

async function previewImage(event) {
  const input = event.target;
  const file = input.files && input.files[0];
  if (!file) return;
  try {
    const processed = await processPassportPhoto(file, 413, 531, 1024 * 1024);
    const dt = new DataTransfer(); dt.items.add(processed); input.files = dt.files;
    const reader = new FileReader(); reader.onload = e => document.getElementById('photoPreview').src = e.target.result; reader.readAsDataURL(processed);
  } catch (e) { console.error('Photo process failed', e); }
}

// Address composition helpers
function buildAddress(prefix) {
  const parts = [
    document.getElementById(prefix + '_village')?.value?.trim(),
    document.getElementById(prefix + '_para_moholla')?.value?.trim(),
    document.getElementById(prefix + '_post_office')?.value?.trim(),
    document.getElementById(prefix + '_upazilla')?.value?.trim(),
    document.getElementById(prefix + '_district')?.value?.trim(),
  ].filter(Boolean);
  return parts.join(', ');
}

function updateAddresses() {
  const present = buildAddress('present');
  const permanent = buildAddress('permanent');
  const presentHidden = document.getElementById('present_address');
  const permanentHidden = document.getElementById('permanent_address');
  if (presentHidden) presentHidden.value = present;
  if (permanentHidden) permanentHidden.value = permanent;
}

function copyPresentAddress() {
  const checked = document.getElementById('same_as_present')?.checked;
  const fields = ['district','upazilla','post_office','village','para_moholla'];
  fields.forEach(function(f){
    const src = document.getElementById('present_' + f);
    const dst = document.getElementById('permanent_' + f);
    if (src && dst) {
      if (checked) dst.value = src.value; else dst.value = '';
      dst.readOnly = !!checked;
      if (checked) dst.classList.add('bg-light'); else dst.classList.remove('bg-light');
    }
  });
  updateAddresses();
}

// Guardian relation behavior and validations
function applyGuardianBehavior() {
  const rel = document.getElementById('guardian_relation')?.value;
  const gEn = document.getElementById('guardian_name_en');
  const gBn = document.getElementById('guardian_name_bn');
  const fEn = document.querySelector('[name="father_name"]');
  const fBn = document.querySelector('[name="father_name_bn"]');
  const mEn = document.querySelector('[name="mother_name"]');
  const mBn = document.querySelector('[name="mother_name_bn"]');
  if (rel === 'father') {
    if (gEn && fEn) gEn.value = fEn.value; if (gBn && fBn) gBn.value = fBn.value;
    if (gEn) gEn.readOnly = true; if (gBn) gBn.readOnly = true;
  } else if (rel === 'mother') {
    if (gEn && mEn) gEn.value = mEn.value; if (gBn && mBn) gBn.value = mBn.value;
    if (gEn) gEn.readOnly = true; if (gBn) gBn.readOnly = true;
  } else {
    if (gEn) gEn.readOnly = false; if (gBn) gBn.readOnly = false;
  }
}

// Wire up new inputs
document.addEventListener('DOMContentLoaded', function() {
  ['present','permanent'].forEach(function(prefix){
    ['district','upazilla','post_office','village','para_moholla'].forEach(function(f){
      const el = document.getElementById(prefix + '_' + f);
      if (el) {
        el.addEventListener('input', function(){ if (prefix === 'present' && document.getElementById('same_as_present')?.checked) {
            const dst = document.getElementById('permanent_' + f); if (dst) dst.value = el.value; }
          updateAddresses();
        });
        el.addEventListener('change', function(){ if (prefix === 'present' && document.getElementById('same_as_present')?.checked) {
            const dst = document.getElementById('permanent_' + f); if (dst) dst.value = el.value; }
          updateAddresses();
        });
      }
    });
  });
  document.getElementById('same_as_present')?.addEventListener('change', copyPresentAddress);
  copyPresentAddress();

  // Wire guardian relation change to behavior
  document.getElementById('guardian_relation')?.addEventListener('change', applyGuardianBehavior);
  // Apply initial guardian behavior (in case old values exist)
  applyGuardianBehavior();
});
</script>
@endpush