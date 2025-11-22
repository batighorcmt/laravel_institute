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
      <label>First Name (English)</label>
      <input name="first_name" class="form-control" required value="{{ old('first_name', $teacher->first_name ?? '') }}">
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
    <div class="form-group col-md-6">
      <label>Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->user->email ?? '') }}">
    </div>
    <div class="form-group col-md-6">
      <label>Designation</label>
      <input name="designation" class="form-control" value="{{ old('designation', $teacher->designation ?? '') }}">
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

  <div class="form-group">
    <button class="btn btn-primary">{{ $editing ? 'Update' : 'Create' }}</button>
    <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-secondary">Cancel</a>
  </div>
</form>
