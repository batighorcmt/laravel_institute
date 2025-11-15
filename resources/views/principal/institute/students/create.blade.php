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
    <!-- Progress Bar -->
    <div class="progress mb-4" style="height: 8px;">
      <div class="progress-bar bg-success" role="progressbar" style="width: 25%" id="formProgress"></div>
    </div>

    <form method="post" action="{{ route('principal.institute.students.store',$school) }}" enctype="multipart/form-data" id="studentForm">@csrf

      <!-- Tabs Navigation -->
      <ul class="nav nav-tabs nav-justified mb-4" id="formTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="enrollment-tab" data-toggle="tab" href="#enrollment" role="tab">
            <i class="fas fa-graduation-cap mr-2"></i>ভর্তি তথ্য
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="personal-tab" data-toggle="tab" href="#personal" role="tab">
            <i class="fas fa-user mr-2"></i>ব্যক্তিগত তথ্য
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="guardian-tab" data-toggle="tab" href="#guardian" role="tab">
            <i class="fas fa-users mr-2"></i>অভিভাবকের তথ্য
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="official-tab" data-toggle="tab" href="#official" role="tab">
            <i class="fas fa-file-alt mr-2"></i>অফিসিয়াল
          </a>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="formTabsContent">
        <!-- Enrollment Tab -->
        <div class="tab-pane fade show active" id="enrollment" role="tabpanel">
          <div class="row">
            <div class="col-md-12">
              <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>Enrollment তথ্য দিলে সংরক্ষণ শেষে আপনাকে বিষয় নির্বাচন পাতায় নিয়ে যাবে।
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label><i class="fas fa-calendar-alt mr-1"></i>শিক্ষাবর্ষ</label>
                <select name="enroll_academic_year" class="form-control">
                  <option value="">-- নির্বাচন --</option>
                  @php($cyVal = $currentYear ? (is_numeric($currentYear->name) ? $currentYear->name : ($currentYear->start_date?->format('Y'))) : '')
                  @if($currentYear && $cyVal)
                    <option value="{{ $cyVal }}" selected>{{ $currentYear->name }}</option>
                  @endif
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

        <!-- Personal Tab -->
        <div class="tab-pane fade" id="personal" role="tabpanel">
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
            <div class="col-md-4">
              <div class="form-group">
                <label><i class="fas fa-birthday-cake mr-1"></i>জন্ম তারিখ *</label>
                <input type="date" name="date_of_birth" class="form-control" required value="{{ old('date_of_birth') }}">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><i class="fas fa-venus-mars mr-1"></i>লিঙ্গ *</label>
                <select name="gender" class="form-control" required>
                  <option value="male" {{ old('gender')=='male'?'selected':'' }}>ছেলে</option>
                  <option value="female" {{ old('gender')=='female'?'selected':'' }}>মেয়ে</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
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

        <!-- Guardian Tab -->
        <div class="tab-pane fade" id="guardian" role="tabpanel">
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
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label><i class="fas fa-phone mr-1"></i>অভিভাবকের ফোন *</label>
                <input type="text" name="guardian_phone" class="form-control" required value="{{ old('guardian_phone') }}">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label><i class="fas fa-map-marker-alt mr-1"></i>ঠিকানা *</label>
                <textarea name="address" rows="3" class="form-control" required>{{ old('address') }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Official Tab -->
        <div class="tab-pane fade" id="official" role="tabpanel">
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
      </div>

      <!-- Navigation Buttons -->
      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-secondary" id="prevBtn" disabled><i class="fas fa-arrow-left mr-1"></i> পূর্ববর্তী</button>
        <button type="button" class="btn btn-primary" id="nextBtn"><i class="fas fa-arrow-right mr-1"></i> পরবর্তী</button>
        <button type="submit" class="btn btn-success d-none" id="submitBtn"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
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

  const tabs = ['enrollment', 'personal', 'guardian', 'official'];
  let currentTab = 0;
  const progressBar = document.getElementById('formProgress');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const submitBtn = document.getElementById('submitBtn');

  function updateProgress() {
    const progress = ((currentTab + 1) / tabs.length) * 100;
    progressBar.style.width = progress + '%';
  }

  function showTab(tabIndex) {
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    document.getElementById(tabs[tabIndex]).classList.add('show', 'active');
    document.getElementById(tabs[tabIndex] + '-tab').classList.add('active');
    prevBtn.disabled = tabIndex === 0;
    nextBtn.style.display = tabIndex === tabs.length - 1 ? 'none' : 'inline-block';
    submitBtn.classList.toggle('d-none', tabIndex !== tabs.length - 1);
    updateProgress();
  }

  nextBtn.addEventListener('click', function() {
    if (currentTab < tabs.length - 1) {
      currentTab++;
      showTab(currentTab);
    }
  });

  prevBtn.addEventListener('click', function() {
    if (currentTab > 0) {
      currentTab--;
      showTab(currentTab);
    }
  });

  function fetchJSON(url, params, cb){
    const usp = new URLSearchParams(params);
    fetch(url + '?' + usp.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.json())
      .then(cb)
      .catch(()=>{});
  }

  function loadSections(){
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
    const year = yearInput.value;
    const cid = classSel.value;
    if(!year || !cid) { rollHint.textContent=''; return; }
    fetchJSON("{{ route('principal.institute.meta.next-roll',$school) }}", {year: year, class_id: cid, section_id: sectionSel.value, group_id: groupSel.value}, data => {
      if(data && data.next){
        rollHint.textContent = 'পরবর্তী রোল: ' + data.next;
        if(!rollInput.value){ rollInput.value = data.next; }
      }
    });
  }

  classSel.addEventListener('change', ()=>{ loadSections(); loadGroups(); setTimeout(loadNextRoll,300); });
  sectionSel.addEventListener('change', loadNextRoll);
  groupSel.addEventListener('change', loadNextRoll);
  yearInput.addEventListener('input', loadNextRoll);
});
</script>
@endpush