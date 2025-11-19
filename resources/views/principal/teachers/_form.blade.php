@php
  $editing = isset($teacherRole) && $teacherRole->user;
  $u = $editing ? $teacherRole->user : null;
  $action = $editing ? route('principal.institute.teachers.update', [$school->id, $teacherRole->id]) : route('principal.institute.teachers.store', $school->id);
  $method = $editing ? 'PUT' : 'POST';

  // Prepare date fields safely (user may be null or dates may be strings)
  $dob = old('date_of_birth');
  if (!$dob) {
    if ($u && !empty($u->date_of_birth)) {
      try {
        $dob = \Illuminate\Support\Carbon::parse($u->date_of_birth)->format('Y-m-d');
      } catch (\Throwable $e) {
        $dob = date('Y-m-d', strtotime($u->date_of_birth));
      }
    } else {
      $dob = '';
    }
  }

  $joining_date = old('joining_date');
  if (!$joining_date) {
    if ($u && !empty($u->joining_date)) {
      try {
        $joining_date = \Illuminate\Support\Carbon::parse($u->joining_date)->format('Y-m-d');
      } catch (\Throwable $e) {
        $joining_date = date('Y-m-d', strtotime($u->joining_date));
      }
    } else {
      $joining_date = '';
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
      <label>First Name (English)</label>
      <input name="first_name" class="form-control" required value="{{ old('first_name', $u->first_name ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Last Name (English)</label>
      <input name="last_name" class="form-control" value="{{ old('last_name', $u->last_name ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>First Name (Bangla)</label>
      <input name="first_name_bn" class="form-control" value="{{ old('first_name_bn', $u->first_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Last Name (Bangla)</label>
      <input name="last_name_bn" class="form-control" value="{{ old('last_name_bn', $u->last_name_bn ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Father's Name (Bangla)</label>
      <input name="father_name_bn" class="form-control" value="{{ old('father_name_bn', $u->father_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Father's Name (English)</label>
      <input name="father_name_en" class="form-control" value="{{ old('father_name_en', $u->father_name_en ?? '') }}">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Mother's Name (Bangla)</label>
      <input name="mother_name_bn" class="form-control" value="{{ old('mother_name_bn', $u->mother_name_bn ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Mother's Name (English)</label>
      <input name="mother_name_en" class="form-control" value="{{ old('mother_name_en', $u->mother_name_en ?? '') }}">
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
      <input name="phone" class="form-control" value="{{ old('phone', $u->phone ?? '') }}">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email', $u->email ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Designation</label>
      <input name="designation" class="form-control" value="{{ old('designation', $teacherRole->designation ?? '') }}">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-6">
      <label>Academic Info</label>
      <textarea name="academic_info" class="form-control">{{ old('academic_info', $u->academic_info ?? '') }}</textarea>
    </div>
    <div class="form-group col-md-6">
      <label>Qualification</label>
      <textarea name="qualification" class="form-control">{{ old('qualification', $u->qualification ?? '') }}</textarea>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-4">
      <label>Photo</label>
      <input type="file" name="photo" accept="image/*" class="form-control-file">
      @if($u && $u->photo)
        <img src="{{ asset('storage/'.$u->photo) }}" style="max-height:80px;margin-top:8px;">
      @endif
    </div>
    <div class="form-group col-md-4">
      <label>Signature</label>
      <input type="file" name="signature" accept="image/*" class="form-control-file">
      @if($u && $u->signature)
        <img src="{{ asset('storage/'.$u->signature) }}" style="max-height:80px;margin-top:8px;">
      @endif
    </div>
    <div class="form-group col-md-4">
      <label>Serial Number</label>
      <input name="serial_number" class="form-control" value="{{ old('serial_number', $teacherRole->serial_number ?? '') }}">
    </div>
  </div>

  <div class="form-group">
    <button class="btn btn-primary">{{ $editing ? 'Update' : 'Create' }}</button>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-secondary">Cancel</a>
  </div>
</form>
