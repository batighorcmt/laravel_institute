@extends('layouts.admin')
@section('title','Edit Application')
@push('styles')
<style>
    .admission-edit-card{border-radius:16px;overflow:hidden;}
    .section-header{border-left:4px solid #0d6efd;}
    .photo-preview-wrapper{border:2px dashed #d0d7de;border-radius:50%;width:140px;height:140px;overflow:hidden;display:flex;align-items:center;justify-content:center;transition:.3s;}
    .photo-preview-wrapper:hover{border-color:#0d6efd;cursor:pointer;}
    .form-control:focus,.form-select:focus{border-color:#86b7fe;box-shadow:0 0 0 .25rem rgba(13,110,253,.25);}    
</style>
@endpush
@section('content')
<div class="container-fluid py-3">
    <h1 class="h5 mb-4">আবেদন সম্পাদনা</h1>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors && $errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('principal.institute.admissions.applications.update', [$school->id,$application->id]) }}" method="POST" enctype="multipart/form-data" class="admission-edit-card card needs-validation" novalidate>
        @csrf
        <div class="card-body p-4">
            <!-- Personal Section -->
            <div class="section-header bg-light p-2 mb-3 rounded">
                <h5 class="mb-0"><i class="fa fa-user me-2"></i> ব্যক্তিগত তথ্য</h5>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="name_bn" id="name_bn" value="{{ old('name_bn',$application->name_bn) }}" class="form-control @error('name_bn') is-invalid @enderror" placeholder="বাংলা নাম" required>
                        <label for="name_bn">নাম (BN) *</label>
                        @error('name_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="name_en" id="name_en" value="{{ old('name_en',$application->name_en) }}" class="form-control @error('name_en') is-invalid @enderror" placeholder="English Name" required>
                        <label for="name_en">নাম (EN) *</label>
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="father_name_bn" id="father_name_bn" value="{{ old('father_name_bn',$application->father_name_bn) }}" class="form-control @error('father_name_bn') is-invalid @enderror" placeholder="পিতার নাম (বাংলায়)">
                        <label for="father_name_bn">পিতা (BN)</label>
                        @error('father_name_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="father_name_en" id="father_name_en" value="{{ old('father_name_en',$application->father_name_en) }}" class="form-control @error('father_name_en') is-invalid @enderror" placeholder="Father (English)" required>
                        <label for="father_name_en">পিতা (EN) *</label>
                        @error('father_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="mother_name_bn" id="mother_name_bn" value="{{ old('mother_name_bn',$application->mother_name_bn) }}" class="form-control @error('mother_name_bn') is-invalid @enderror" placeholder="মাতার নাম (বাংলায়)">
                        <label for="mother_name_bn">মাতা (BN)</label>
                        @error('mother_name_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input name="mother_name_en" id="mother_name_en" value="{{ old('mother_name_en',$application->mother_name_en) }}" class="form-control @error('mother_name_en') is-invalid @enderror" placeholder="Mother (English)" required>
                        <label for="mother_name_en">মাতা (EN) *</label>
                        @error('mother_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select name="guardian_relation" id="guardian_relation" class="form-select @error('guardian_relation') is-invalid @enderror">
                            <option value="">--</option>
                            @foreach(['father'=>'পিতা','mother'=>'মাতা','uncle'=>'চাচা/মামা','aunt'=>'চাচী/খালা','brother'=>'ভাই','sister'=>'বোন','other'=>'অন্যান্য'] as $k=>$v)
                                <option value="{{ $k }}" @selected(old('guardian_relation',$application->guardian_relation)===$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <label for="guardian_relation">অভিভাবকের সম্পর্ক</label>
                        @error('guardian_relation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input name="guardian_name_bn" id="guardian_name_bn" value="{{ old('guardian_name_bn',$application->guardian_name_bn) }}" class="form-control @error('guardian_name_bn') is-invalid @enderror" placeholder="গার্ডিয়ান BN">
                        <label for="guardian_name_bn">অভিভাবক (BN)</label>
                        @error('guardian_name_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input name="guardian_name_en" id="guardian_name_en" value="{{ old('guardian_name_en',$application->guardian_name_en) }}" class="form-control @error('guardian_name_en') is-invalid @enderror" placeholder="Guardian EN">
                        <label for="guardian_name_en">অভিভাবক (EN)</label>
                        @error('guardian_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <!-- Photo + Contact Row -->
                <div class="col-12 mt-2">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-md-3">
                            <div class="text-center bg-light rounded p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                <div class="photo-preview-wrapper mb-2" onclick="document.getElementById('photoInput').click()">
                                    <img id="photoPreview" src="{{ $application->photo ? asset('storage/admission/'.$application->photo) : asset('images/default-avatar.png') }}" alt="Photo" style="width:140px;height:140px;object-fit:cover;">
                                </div>
                                <input type="file" name="photo" id="photoInput" class="d-none @error('photo') is-invalid @enderror" accept="image/*" onchange="previewImage(event)">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('photoInput').click()"><i class="fa fa-camera me-1"></i> পরিবর্তন</button>
                                @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted mt-1">Passport style ≤2MB</small>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input name="mobile" id="mobile" value="{{ old('mobile',$application->mobile) }}" class="form-control @error('mobile') is-invalid @enderror" placeholder="মোবাইল" required>
                                        <label for="mobile">মোবাইল *</label>
                                        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input name="birth_reg_no" id="birth_reg_no" value="{{ old('birth_reg_no',$application->birth_reg_no) }}" class="form-control @error('birth_reg_no') is-invalid @enderror" placeholder="জন্ম নিবন্ধন">
                                        <label for="birth_reg_no">জন্ম নিবন্ধন নম্বর</label>
                                        @error('birth_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" name="dob" id="dob" value="{{ old('dob', optional($application->dob)->format('Y-m-d')) }}" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB">
                                        <label for="dob">জন্ম তারিখ</label>
                                        @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                            <option value="">--</option>
                                            @foreach(['Male'=>'পুরুষ','Female'=>'নারী','Other'=>'অন্যান্য'] as $k=>$v)
                                                <option value="{{ $k }}" @selected(old('gender',$application->gender)===$k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                        <label for="gender">লিঙ্গ *</label>
                                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="religion" id="religion" class="form-select @error('religion') is-invalid @enderror">
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach(['islam'=>'ইসলাম','hindu'=>'হিন্দু','christian'=>'খ্রিস্টান','buddhist'=>'বৌদ্ধ','other'=>'অন্যান্য'] as $k=>$v)
                                                <option value="{{ $k }}" @selected(old('religion',$application->religion)===$k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                        <label for="religion">ধর্ম</label>
                                        @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="blood_group" id="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                                <option value="{{ $bg }}" @selected(old('blood_group',$application->blood_group)===$bg)>{{ $bg }}</option>
                                            @endforeach
                                        </select>
                                        <label for="blood_group">রক্তের গ্রুপ</label>
                                        @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Address Section -->
            <div class="section-header bg-light p-2 mb-3 rounded"><h5 class="mb-0"><i class="fa fa-home me-2"></i> ঠিকানা তথ্য</h5></div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">বর্তমান ঠিকানা</h6>
                    <div class="row g-2">
                        <div class="col-md-6"><div class="form-floating"><input name="present_district" id="present_district" value="{{ old('present_district',$application->present_district) }}" class="form-control" placeholder="জেলা"><label for="present_district">জেলা</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="present_upazilla" id="present_upazilla" value="{{ old('present_upazilla',$application->present_upazilla) }}" class="form-control" placeholder="উপজেলা"><label for="present_upazilla">উপজেলা</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="present_post_office" id="present_post_office" value="{{ old('present_post_office',$application->present_post_office) }}" class="form-control" placeholder="ডাকঘর"><label for="present_post_office">ডাকঘর</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="present_village" id="present_village" value="{{ old('present_village',$application->present_village) }}" class="form-control" placeholder="গ্রাম"><label for="present_village">গ্রাম</label></div></div>
                        <div class="col-12"><div class="form-floating"><input name="present_para_moholla" id="present_para_moholla" value="{{ old('present_para_moholla',$application->present_para_moholla) }}" class="form-control" placeholder="পাড়া/মহল্লা"><label for="present_para_moholla">পাড়া/মহল্লা</label></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">স্থায়ী ঠিকানা</h6>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="same_as_present" onclick="copyPresentAddress()"><label class="form-check-label" for="same_as_present">বর্তমান ঠিকানার মতো</label></div>
                    <div class="row g-2">
                        <div class="col-md-6"><div class="form-floating"><input name="permanent_district" id="permanent_district" value="{{ old('permanent_district',$application->permanent_district) }}" class="form-control" placeholder="জেলা"><label for="permanent_district">জেলা</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="permanent_upazilla" id="permanent_upazilla" value="{{ old('permanent_upazilla',$application->permanent_upazilla) }}" class="form-control" placeholder="উপজেলা"><label for="permanent_upazilla">উপজেলা</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="permanent_post_office" id="permanent_post_office" value="{{ old('permanent_post_office',$application->permanent_post_office) }}" class="form-control" placeholder="ডাকঘর"><label for="permanent_post_office">ডাকঘর</label></div></div>
                        <div class="col-md-6"><div class="form-floating"><input name="permanent_village" id="permanent_village" value="{{ old('permanent_village',$application->permanent_village) }}" class="form-control" placeholder="গ্রাম"><label for="permanent_village">গ্রাম</label></div></div>
                        <div class="col-12"><div class="form-floating"><input name="permanent_para_moholla" id="permanent_para_moholla" value="{{ old('permanent_para_moholla',$application->permanent_para_moholla) }}" class="form-control" placeholder="পাড়া/মহল্লা"><label for="permanent_para_moholla">পাড়া/মহল্লা</label></div></div>
                    </div>
                </div>
            </div>
            <!-- Education Section -->
            <div class="section-header bg-light p-2 mb-3 rounded"><h5 class="mb-0"><i class="fa fa-graduation-cap me-2"></i> পূর্ববর্তী শিক্ষা</h5></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="form-floating"><input name="last_school" id="last_school" value="{{ old('last_school',$application->last_school) }}" class="form-control @error('last_school') is-invalid @enderror" placeholder="সর্বশেষ স্কুল"><label for="last_school">সর্বশেষ বিদ্যালয়</label>@error('last_school')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                <div class="col-md-3"><div class="form-floating"><input name="result" id="result" value="{{ old('result',$application->result) }}" class="form-control @error('result') is-invalid @enderror" placeholder="ফলাফল"><label for="result">ফলাফল</label>@error('result')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                <div class="col-md-3"><div class="form-floating"><input name="pass_year" id="pass_year" value="{{ old('pass_year',$application->pass_year) }}" class="form-control @error('pass_year') is-invalid @enderror" placeholder="পাসের বছর"><label for="pass_year">পাসের বছর</label>@error('pass_year')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                <div class="col-12"><div class="form-floating"><textarea name="achievement" id="achievement" class="form-control @error('achievement') is-invalid @enderror" style="height:110px" placeholder="অর্জন / Achievement">{{ old('achievement',$application->achievement) }}</textarea><label for="achievement">অর্জন / Achievement</label>@error('achievement')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('principal.institute.admissions.applications.show', [$school->id,$application->id]) }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> ফিরে যান</a>
            <button class="btn btn-primary"><i class="fa fa-save me-1"></i> আপডেট সংরক্ষণ</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
function previewImage(e){const f=e.target.files&&e.target.files[0];if(!f)return;const r=new FileReader();r.onload=t=>document.getElementById('photoPreview').src=t.target.result;r.readAsDataURL(f);} 
function copyPresentAddress(){const c=document.getElementById('same_as_present').checked;['district','upazilla','post_office','village','para_moholla'].forEach(f=>{const s=document.getElementById('present_'+f);const d=document.getElementById('permanent_'+f);if(s&&d){d.value=c?s.value:'';d.readOnly=c;c?d.classList.add('bg-light'):d.classList.remove('bg-light');}});} 
function applyGuardianBehavior(){const rel=document.getElementById('guardian_relation')?.value;const gEn=document.getElementById('guardian_name_en');const gBn=document.getElementById('guardian_name_bn');const fEn=document.getElementById('father_name_en');const fBn=document.getElementById('father_name_bn');const mEn=document.getElementById('mother_name_en');const mBn=document.getElementById('mother_name_bn');if(rel==='father'){if(gEn&&fEn)gEn.value=fEn.value;if(gBn&&fBn)gBn.value=fBn.value;if(gEn)gEn.readOnly=true;if(gBn)gBn.readOnly=true;}else if(rel==='mother'){if(gEn&&mEn)gEn.value=mEn.value;if(gBn&&mBn)gBn.value=mBn.value;if(gEn)gEn.readOnly=true;if(gBn)gBn.readOnly=true;}else{if(gEn)gEn.readOnly=false;if(gBn)gBn.readOnly=false;}}
document.addEventListener('DOMContentLoaded',()=>{applyGuardianBehavior();document.getElementById('guardian_relation')?.addEventListener('change',applyGuardianBehavior);document.getElementById('same_as_present')?.addEventListener('change',copyPresentAddress);});
</script>
@endpush
@endsection