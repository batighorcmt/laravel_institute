<x-layout.public :school="$school" :title="'ভর্তি আবেদন ফর্ম — ' . $school->name">
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Bootstrap overrides and custom styles */
        :root {
            --bs-blue: #0d6efd;
            --bs-primary: #0d6efd;
            /* Add other bootstrap variables if needed */
        }
        .admission-form-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .section-header {
            border-left: 4px solid var(--bs-primary);
        }
        .photo-preview-wrapper:hover {
            border-color: var(--bs-primary);
            cursor: pointer;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        /* TailwindCSS might interfere, so we might need to be more specific */
        .container {
            max-width: 1024px; /* Or another width */
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    @endpush

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($errors && $errors->any())
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">কিছু সমস্যা রয়েছে:</h5>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card admission-form-card shadow-lg border-0">
                    <div class="pt-4 text-center">
                        @php $logo = $school->logo ?? null; @endphp
                        @if($logo)
                            <img src="{{ asset('storage/'.$logo) }}" alt="{{ $school->name }}" style="height:64px" />
                        @else
                            <div class="fw-bold text-secondary">{{ $school->name }}</div>
                        @endif
                    </div>
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center"><i class="fas fa-user-graduate me-2"></i> শিক্ষার্থী ভর্তি আবেদন ফর্ম</h4>
                    </div>
                    
                    <form action="{{ route('admission.apply.submit', $school->code) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <div class="alert alert-info py-2 mb-0" id="selectedClassInfo" style="display:none;"></div>
                            </div>
                            <!-- Personal Information Section -->
                            <div class="form-section mb-4">
                                <div class="section-header bg-light p-2 mb-3 rounded">
                                    <h5 class="section-title text-dark mb-0"><i class="fas fa-user-circle me-2"></i> ব্যক্তিগত তথ্য</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <!-- Class and Session -->
                                    <div class="col-md-6">
                                        @php($bnClasses = ['6'=>'ষষ্ঠ শ্রেণি','7'=>'সপ্তম শ্রেণি','8'=>'অষ্টম শ্রেণি','9'=>'নবম শ্রেণি','10'=>'দশম শ্রেণি'])
                                        <div class="form-floating">
                                            <select class="form-select @error('class_name') is-invalid @enderror" id="class" name="class_name" required {{ (isset($classSettings) && $classSettings->count()===0)?'disabled':'' }}>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                @isset($classSettings)
                                                    @forelse($classSettings as $cs)
                                                        <option value="{{ $cs->class_code }}" {{ old('class_name') == $cs->class_code ? 'selected' : '' }} data-fee="{{ $cs->fee_amount }}" data-deadline="{{ $cs->deadline? $cs->deadline->format('d-m-Y'):'' }}">
                                                            {{ $bnClasses[$cs->class_code] ?? $cs->class_code }} (ফি: {{ (int)$cs->fee_amount }}৳{{ $cs->deadline? ', শেষ তারিখ: '.$cs->deadline->format('d-m-Y'):'' }})
                                                        </option>
                                                    @empty
                                                    @endforelse
                                                @endisset
                                            </select>
                                            <label for="class">ভর্তি ইচ্ছুক শ্রেণি <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('class_name') {{ $message }} @else দয়া করে শ্রেণি নির্বাচন করুন @enderror</div>
                                        </div>
                                        <div id="classMeta" class="small mt-2 text-muted"></div>
                                        @isset($classSettings)
                                            @if($classSettings->count()===0)
                                                <div class="alert alert-warning mt-2 mb-0 p-2">বর্তমানে কোনো শ্রেণির ভর্তি আবেদন গ্রহণ করা হচ্ছে না।</div>
                                            @endif
                                        @endisset
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="session" name="session" value="{{ $school->admissionAcademicYear->name ?? '' }}" readonly required>
                                            <label for="session">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    
                                    
                                    
                                    <!-- Student Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('name_bn') is-invalid @enderror" id="name_bn" name="name_bn" placeholder="শিক্ষার্থীর নাম (বাংলায়)" value="{{ old('name_bn') }}" required>
                                            <label for="name_bn">শিক্ষার্থীর নাম (বাংলায়) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('name_bn') {{ $message }} @else দয়া করে শিক্ষার্থীর বাংলা নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" placeholder="Student's Name (English)" value="{{ old('name_en') }}" required>
                                            <label for="name_en">শিক্ষার্থীর নাম (ইংরেজিতে) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('name_en') {{ $message }} @else দয়া করে শিক্ষার্থীর ইংরেজি নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Father's Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('father_name_bn') is-invalid @enderror" id="father_name_bn" name="father_name_bn" placeholder="পিতার নাম (বাংলায়)" value="{{ old('father_name_bn') }}" required>
                                            <label for="father_name_bn">পিতার নাম (বাংলায়) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('father_name_bn') {{ $message }} @else দয়া করে পিতার বাংলা নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('father_name_en') is-invalid @enderror" id="father_name_en" name="father_name_en" placeholder="Father's Name (English)" value="{{ old('father_name_en') }}" required>
                                            <label for="father_name_en">পিতার নাম (ইংরেজিতে) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('father_name_en') {{ $message }} @else দয়া করে পিতার ইংরেজি নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Mother's Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('mother_name_bn') is-invalid @enderror" id="mother_name_bn" name="mother_name_bn" placeholder="মাতার নাম (বাংলায়)" value="{{ old('mother_name_bn') }}" required>
                                            <label for="mother_name_bn">মাতার নাম (বাংলায়) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('mother_name_bn') {{ $message }} @else দয়া করে মাতার বাংলা নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('mother_name_en') is-invalid @enderror" id="mother_name_en" name="mother_name_en" placeholder="Mother's Name (English)" value="{{ old('mother_name_en') }}" required>
                                            <label for="mother_name_en">মাতার নাম (ইংরেজিতে) <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('mother_name_en') {{ $message }} @else দয়া করে মাতার ইংরেজি নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Guardian relation before names -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select @error('guardian_relation') is-invalid @enderror" id="guardian_relation" name="guardian_relation" required>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                <option value="father" {{ old('guardian_relation')=='father' ? 'selected' : '' }}>পিতা</option>
                                                <option value="mother" {{ old('guardian_relation')=='mother' ? 'selected' : '' }}>মাতা</option>
                                                <option value="uncle" {{ old('guardian_relation')=='uncle' ? 'selected' : '' }}>চাচা/মামা</option>
                                                <option value="aunt" {{ old('guardian_relation')=='aunt' ? 'selected' : '' }}>চাচী/খালা</option>
                                                <option value="brother" {{ old('guardian_relation')=='brother' ? 'selected' : '' }}>ভাই</option>
                                                <option value="sister" {{ old('guardian_relation')=='sister' ? 'selected' : '' }}>বোন</option>
                                                <option value="other" {{ old('guardian_relation')=='other' ? 'selected' : '' }}>অন্যান্য</option>
                                            </select>
                                            <label for="guardian_relation">অভিভাবকের সম্পর্ক <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('guardian_relation') {{ $message }} @else দয়া করে সম্পর্ক নির্বাচন করুন @enderror</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('guardian_name_bn') is-invalid @enderror" id="guardian_name_bn" name="guardian_name_bn" placeholder="অভিভাবকের নাম (বাংলায়)" value="{{ old('guardian_name_bn') }}">
                                            <label for="guardian_name_bn">অভিভাবকের নাম (বাংলায়)</label>
                                            <div class="invalid-feedback">@error('guardian_name_bn') {{ $message }} @else অভিভাবকের বাংলা নাম সঠিক নয় @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('guardian_name_en') is-invalid @enderror" id="guardian_name_en" name="guardian_name_en" placeholder="Guardian's Name (English)" value="{{ old('guardian_name_en') }}">
                                            <label for="guardian_name_en">অভিভাবকের নাম (ইংরেজিতে)</label>
                                            <div class="invalid-feedback">@error('guardian_name_en') {{ $message }} @else অভিভাবকের ইংরেজি নাম সঠিক নয় @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Compact row: Photo + (Mobile, Birth Reg, DOB) beside -->
                                    <div class="col-12 mt-2">
                                        <div class="row g-3 align-items-stretch">
                                            <div class="col-md-3">
                                                <div class="text-center bg-light rounded p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                                    <div class="photo-preview-wrapper mb-2" style="width: 120px; height: 120px; border: 2px dashed #ddd; border-radius: 50%; overflow: hidden; display:flex; align-items:center; justify-content:center;">
                                                        <img id="photoPreview" src="{{ asset('images/default-avatar.png') }}" class="rounded-circle" alt="ছবি" style="width: 120px; height: 120px; object-fit: cover;">
                                                    </div>
                                                    <input type="file" class="d-none" id="photoInput" name="photo" accept="image/*" onchange="previewImage(event)" required>
                                                    <label for="photoInput" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-camera me-1"></i> ছবি আপলোড
                                                    </label>
                                                    <small class="text-muted d-block mt-1">35x45mm (≈413x531px), ≤1MB</small>
                                                </div>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <input type="tel" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" placeholder="মোবাইল নম্বর" value="{{ old('mobile') }}" required>
                                                            <label for="mobile">মোবাইল নম্বর <span class="text-danger">*</span></label>
                                                            <div class="invalid-feedback">@error('mobile') {{ $message }} @else মোবাইল 01 দিয়ে শুরু ১১ সংখ্যার হতে হবে @enderror</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <input type="text" class="form-control @error('birth_reg_no') is-invalid @enderror" id="birth_reg_no" name="birth_reg_no" placeholder="জন্ম নিবন্ধন নম্বর" value="{{ old('birth_reg_no') }}" required>
                                                            <label for="birth_reg_no">জন্ম নিবন্ধন নম্বর <span class="text-danger">*</span></label>
                                                            <div class="invalid-feedback">@error('birth_reg_no') {{ $message }} @else শুধু সংখ্যা লিখুন @enderror</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <input type="text" class="form-control datepicker @error('dob') is-invalid @enderror" id="dob" name="dob" placeholder="জন্ম তারিখ" value="{{ old('dob') }}" required>
                                                            <label for="dob">জন্ম তারিখ <span class="text-danger">*</span></label>
                                                            <div class="invalid-feedback">@error('dob') {{ $message }} @else জন্ম তারিখ নির্বাচন করুন @enderror</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-1">
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                                                <option value="">-- নির্বাচন করুন --</option>
                                                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>পুরুষ</option>
                                                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>মহিলা</option>
                                                                <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>অন্যান্য</option>
                                                            </select>
                                                            <label for="gender">লিঙ্গ <span class="text-danger">*</span></label>
                                                            <div class="invalid-feedback">@error('gender') {{ $message }} @else দয়া করে লিঙ্গ নির্বাচন করুন @enderror</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <select class="form-select @error('religion') is-invalid @enderror" id="religion" name="religion" required>
                                                                <option value="">-- নির্বাচন করুন --</option>
                                                                <option value="islam" {{ old('religion') == 'Islam' ? 'selected' : '' }}>ইসলাম</option>
                                                                <option value="hindu" {{ old('religion') == 'Hindu' ? 'selected' : '' }}>হিন্দু</option>
                                                                <option value="christian" {{ old('religion') == 'Christian' ? 'selected' : '' }}>খ্রিষ্টান</option>
                                                                <option value="buddhist" {{ old('religion') == 'Buddhist' ? 'selected' : '' }}>বৌদ্ধ</option>
                                                                <option value="other" {{ old('religion') == 'Other' ? 'selected' : '' }}>অন্যান্য</option>
                                                            </select>
                                                            <label for="religion">ধর্ম <span class="text-danger">*</span></label>
                                                            <div class="invalid-feedback">@error('religion') {{ $message }} @else দয়া করে ধর্ম নির্বাচন করুন @enderror</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <select class="form-select" id="blood_group" name="blood_group">
                                                                <option value="">-- নির্বাচন করুন --</option>
                                                                <option value="A+" {{ old('blood_group') == 'A+' ? 'selected' : '' }}>A+</option>
                                                                <option value="A-" {{ old('blood_group') == 'A-' ? 'selected' : '' }}>A-</option>
                                                                <option value="B+" {{ old('blood_group') == 'B+' ? 'selected' : '' }}>B+</option>
                                                                <option value="B-" {{ old('blood_group') == 'B-' ? 'selected' : '' }}>B-</option>
                                                                <option value="AB+" {{ old('blood_group') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                                                <option value="AB-" {{ old('blood_group') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                                                <option value="O+" {{ old('blood_group') == 'O+' ? 'selected' : '' }}>O+</option>
                                                                <option value="O-" {{ old('blood_group') == 'O-' ? 'selected' : '' }}>O-</option>
                                                            </select>
                                                            <label for="blood_group">রক্তের গ্রুপ</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    
                                </div>
                            </div>
                            
                            <!-- Address Information Section -->
                            <div class="form-section mb-4">
                                <div class="section-header bg-light p-2 mb-3 rounded">
                                    <h5 class="section-title text-dark mb-0"><i class="fas fa-home me-2"></i> ঠিকানা তথ্য</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <!-- Present Address (detailed) -->
                                    <div class="col-md-6">
                                        <h6 class="mb-3 text-primary">বর্তমান ঠিকানা</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="present_district" placeholder="জেলা" required>
                                                    <label for="present_district">জেলা</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="present_upazilla" placeholder="উপজেলা" required>
                                                    <label for="present_upazilla">উপজেলা</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="present_post_office" placeholder="ডাকঘর" required>
                                                    <label for="present_post_office">ডাকঘর</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="present_village" placeholder="গ্রাম" required>
                                                    <label for="present_village">গ্রাম</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="present_para_moholla" placeholder="পাড়া/মহল্লা">
                                                    <label for="present_para_moholla">পাড়া/মহল্লা</label>
                                                </div>
                                            </div>
                                            <input type="hidden" id="present_address" name="present_address" value="{{ old('present_address') }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Permanent Address (detailed) -->
                                    <div class="col-md-6">
                                        <h6 class="mb-3 text-primary">স্থায়ী ঠিকানা</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="same_as_present" onclick="copyPresentAddress()">
                                            <label class="form-check-label" for="same_as_present">বর্তমান ঠিকানার মতো একই</label>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="permanent_district" placeholder="জেলা" required>
                                                    <label for="permanent_district">জেলা</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="permanent_upazilla" placeholder="উপজেলা" required>
                                                    <label for="permanent_upazilla">উপজেলা</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="permanent_post_office" placeholder="ডাকঘর" required>
                                                    <label for="permanent_post_office">ডাকঘর</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="permanent_village" placeholder="গ্রাম" required>
                                                    <label for="permanent_village">গ্রাম</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="permanent_para_moholla" placeholder="পাড়া/মহল্লা">
                                                    <label for="permanent_para_moholla">পাড়া/মহল্লা</label>
                                                </div>
                                            </div>
                                            <input type="hidden" id="permanent_address" name="permanent_address" value="{{ old('permanent_address') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Previous Education Section -->
                            <div class="form-section mb-4">
                                <div class="section-header bg-light p-2 mb-3 rounded">
                                    <h5 class="section-title text-dark mb-0"><i class="fas fa-graduation-cap me-2"></i> পূর্ববর্তী শিক্ষা তথ্য</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('last_school') is-invalid @enderror" id="last_school" name="last_school" placeholder="সর্বশেষ বিদ্যালয়ের নাম" value="{{ old('last_school') }}" required>
                                            <label for="last_school">সর্বশেষ বিদ্যালয়ের নাম <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('last_school') {{ $message }} @else দয়া করে বিদ্যালয়ের নাম লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('result') is-invalid @enderror" id="result" name="result" placeholder="পরীক্ষার ফলাফল" value="{{ old('result') }}" required>
                                            <label for="result">পরীক্ষার ফলাফল <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('result') {{ $message }} @else দয়া করে পরীক্ষার ফলাফল লিখুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <select class="form-select @error('pass_year') is-invalid @enderror" id="pass_year" name="pass_year" required>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                @php $currentYear = (int) date('Y'); @endphp
                                                @for($i = 0; $i <= 10; $i++)
                                                    @php $y = $currentYear - $i; @endphp
                                                    <option value="{{ $y }}" {{ old('pass_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                            <label for="pass_year">পাশের বছর <span class="text-danger">*</span></label>
                                            <div class="invalid-feedback">@error('pass_year') {{ $message }} @else দয়া করে পাশের বছর নির্বাচন করুন @enderror</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="achievement" name="achievement" style="height: 100px">{{ old('achievement') }}</textarea>
                                            <label for="achievement">বিশেষ স্বীকৃতি / অভিজ্ঞতা (যদি থাকে)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="declaration" required>
                                    <label class="form-check-label" for="declaration">
                                        আমি ঘোষণা করছি যে উপরে প্রদত্ত সকল তথ্য সঠিক
                                    </label>
                                    <div class="invalid-feedback">আপনাকে ঘোষণাটি গ্রহণ করতে হবে</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="fas fa-paper-plane me-2"></i> আবেদন সাবমিট করুন
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/bn.js"></script>
    <script>
        // Image Preview + Client-side resize to 35x45mm (~413x531px) and <=1MB
        async function previewImage(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            if (!file) return;
            try {
                const processed = await processPassportPhoto(file, 413, 531, 1024 * 1024);
                // Replace the file in the input using DataTransfer
                const dt = new DataTransfer();
                dt.items.add(processed);
                input.files = dt.files;
                // Update preview
                const reader = new FileReader();
                reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
                reader.readAsDataURL(processed);
            } catch (e) {
                console.error('Photo process failed', e);
            }
        }

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
                // Too wide -> crop width
                sh = srcH; sw = Math.round(srcH * targetAspect);
                sx = Math.round((srcW - sw) / 2); sy = 0;
            } else {
                // Too tall -> crop height
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
            return new File([blob], 'photo.jpg', { type: 'image/jpeg' });
        }
        
        // Build a single-line address from detailed fields
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

        // Copy Present Address detailed fields to Permanent and toggle read-only
        function copyPresentAddress() {
            const checked = document.getElementById('same_as_present').checked;
            const fields = ['district','upazilla','post_office','village','para_moholla'];
            fields.forEach(function(f){
                const src = document.getElementById('present_' + f);
                const dst = document.getElementById('permanent_' + f);
                if (src && dst) {
                    if (checked) dst.value = src.value; else dst.value = '';
                    // Toggle read-only and subtle visual hint
                    dst.readOnly = checked;
                    if (checked) {
                        dst.classList.add('bg-light');
                    } else {
                        dst.classList.remove('bg-light');
                    }
                }
            });
            updateAddresses();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Datepicker Initialization
            flatpickr("#dob", {
                dateFormat: "Y-m-d",
                maxDate: "today",
                locale: "bn"
            });
            // Dynamic class fee & deadline display
            const classSelect = document.getElementById('class');
            const classMeta = document.getElementById('classMeta');
            const selectedInfo = document.getElementById('selectedClassInfo');
            function updateClassMeta(){
                if(!classSelect) return;
                const opt = classSelect.options[classSelect.selectedIndex];
                if(!opt || !opt.value){
                    if(classMeta) classMeta.textContent='';
                    if(selectedInfo){ selectedInfo.style.display='none'; selectedInfo.textContent=''; }
                    return;
                }
                const fee = opt.getAttribute('data-fee');
                const deadline = opt.getAttribute('data-deadline');
                if(classMeta){
                    classMeta.textContent = 'নির্বাচিত ফি: ' + (fee? fee+'৳':'অজানা') + (deadline? ' | শেষ তারিখ: '+deadline:'');
                }
                if(selectedInfo){
                    selectedInfo.textContent = 'আপনি '+ opt.textContent + ' নির্বাচন করেছেন';
                    selectedInfo.style.display='block';
                }
            }
            if(classSelect){ classSelect.addEventListener('change', updateClassMeta); updateClassMeta(); }
            
            // Real-time validation
            const bnRegex = /^[\u0980-\u09FF .\-]+$/;
            const enRegex = /^[A-Za-z .\-]+$/;
            const mobileRegex = /^01\d{9}$/;
            const numRegex = /^\d+$/;

            function setInvalid(el, msg) {
                if (!el) return;
                el.classList.add('is-invalid');
                const fb = el.parentElement?.querySelector('.invalid-feedback');
                if (fb && msg) fb.textContent = msg;
            }
            function clearInvalid(el) {
                if (!el) return;
                el.classList.remove('is-invalid');
            }

            function validateBn(id, label) {
                const el = document.getElementById(id);
                if (!el) return true;
                const val = el.value.trim();
                if (!val || !bnRegex.test(val)) { setInvalid(el, `${label} শুধুমাত্র বাংলায় লিখতে হবে`); return false; }
                clearInvalid(el); return true;
            }
            function validateEn(id, label) {
                const el = document.getElementById(id);
                if (!el) return true;
                const val = el.value.trim();
                if (!val || !enRegex.test(val)) { setInvalid(el, `${label} ইংরেজি অক্ষরে লিখুন`); return false; }
                clearInvalid(el); return true;
            }
            function validateMobile() {
                const el = document.getElementById('mobile');
                const val = el.value.trim();
                if (!mobileRegex.test(val)) { setInvalid(el, 'মোবাইল 01 দিয়ে শুরু ১১ সংখ্যার হতে হবে'); return false; }
                clearInvalid(el); return true;
            }
            function validateNumeric(id, label) {
                const el = document.getElementById(id);
                const val = el.value.trim();
                if (!numRegex.test(val)) { setInvalid(el, `${label} শুধুমাত্র সংখ্যা হবে`); return false; }
                clearInvalid(el); return true;
            }
            function validateRequiredSelect(id, label) {
                const el = document.getElementById(id);
                if (!el || !el.value) { if (el) setInvalid(el, `${label} নির্বাচন করুন`); return false; }
                clearInvalid(el); return true;
            }

            function applyGuardianBehavior() {
                const rel = document.getElementById('guardian_relation')?.value;
                const gEn = document.getElementById('guardian_name_en');
                const gBn = document.getElementById('guardian_name_bn');
                const fEn = document.getElementById('father_name_en');
                const fBn = document.getElementById('father_name_bn');
                const mEn = document.getElementById('mother_name_en');
                const mBn = document.getElementById('mother_name_bn');
                if (rel === 'father') {
                    if (gEn && fEn) gEn.value = fEn.value; if (gBn && fBn) gBn.value = fBn.value;
                    if (gEn) gEn.readOnly = true; if (gBn) gBn.readOnly = true;
                    clearInvalid(gEn); clearInvalid(gBn);
                } else if (rel === 'mother') {
                    if (gEn && mEn) gEn.value = mEn.value; if (gBn && mBn) gBn.value = mBn.value;
                    if (gEn) gEn.readOnly = true; if (gBn) gBn.readOnly = true;
                    clearInvalid(gEn); clearInvalid(gBn);
                } else {
                    if (gEn) gEn.readOnly = false; if (gBn) gBn.readOnly = false;
                }
            }

            const mappings = [
                ['name_bn', () => validateBn('name_bn','শিক্ষার্থীর বাংলা নাম')],
                ['name_en', () => validateEn('name_en','শিক্ষার্থীর ইংরেজি নাম')],
                ['father_name_bn', () => validateBn('father_name_bn','পিতার বাংলা নাম')],
                ['father_name_en', () => validateEn('father_name_en','পিতার ইংরেজি নাম')],
                ['mother_name_bn', () => validateBn('mother_name_bn','মাতার বাংলা নাম')],
                ['mother_name_en', () => validateEn('mother_name_en','মাতার ইংরেজি নাম')],
                ['guardian_name_bn', () => {
                    const rel = document.getElementById('guardian_relation')?.value;
                    if (rel==='father'||rel==='mother') { clearInvalid(document.getElementById('guardian_name_bn')); return true; }
                    return validateBn('guardian_name_bn','অভিভাবকের বাংলা নাম');
                }],
                ['guardian_name_en', () => {
                    const rel = document.getElementById('guardian_relation')?.value;
                    if (rel==='father'||rel==='mother') { clearInvalid(document.getElementById('guardian_name_en')); return true; }
                    return validateEn('guardian_name_en','অভিভাবকের ইংরেজি নাম');
                }],
                ['mobile', validateMobile],
                ['birth_reg_no', () => validateNumeric('birth_reg_no','জন্ম নিবন্ধন নম্বর')],
            ];

            mappings.forEach(([id, fn]) => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', fn);
                    el.addEventListener('blur', fn);
                }
            });

            document.getElementById('guardian_relation')?.addEventListener('change', function(){
                applyGuardianBehavior();
                validateRequiredSelect('guardian_relation','অভিভাবকের সম্পর্ক');
                const bnMap = mappings.find(m=>m[0]==='guardian_name_bn'); if (bnMap) bnMap[1]();
                const enMap = mappings.find(m=>m[0]==='guardian_name_en'); if (enMap) enMap[1]();
            });
            ['gender','religion','class','pass_year'].forEach(id=>{
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', ()=>validateRequiredSelect(id, document.querySelector(`label[for="${id}"]`)?.innerText || 'ফিল্ড'));
            });

            // Submit guard combining browser + custom checks
            const form = document.querySelector('form.needs-validation');
            let submitting = false;
            form.addEventListener('submit', function(event){
                if (submitting) { event.preventDefault(); event.stopPropagation(); return false; }
                let ok = true;
                mappings.forEach(([_, fn])=>{ if (!fn()) ok = false; });
                if (!validateRequiredSelect('class','ভর্তি ইচ্ছুক শ্রেণি')) ok = false;
                if (!validateRequiredSelect('gender','লিঙ্গ')) ok = false;
                if (!validateRequiredSelect('religion','ধর্ম')) ok = false;
                if (!validateRequiredSelect('pass_year','পাশের বছর')) ok = false;
                updateAddresses();
                const pa = document.getElementById('present_address')?.value?.trim();
                const pea = document.getElementById('permanent_address')?.value?.trim();
                if (!pa) ok = false;
                if (!pea) ok = false;
                if (!ok || !form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
                if (ok && form.checkValidity()) {
                    // Disable submit to prevent double submission
                    submitting = true;
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> জমা হচ্ছে...'; }
                }
            });

            // Initialize behaviors
            applyGuardianBehavior();

            // Trigger file input
            document.querySelector('.photo-preview-wrapper').addEventListener('click', function() {
                document.getElementById('photoInput').click();
            });

            // Hook up address composers
            ['present','permanent'].forEach(function(prefix){
                ['district','upazilla','post_office','village','para_moholla'].forEach(function(f){
                    const el = document.getElementById(prefix + '_' + f);
                    if (el) {
                        el.addEventListener('input', function(){
                            // Keep permanent synced if checkbox checked
                            if (prefix === 'present' && document.getElementById('same_as_present').checked) {
                                const dst = document.getElementById('permanent_' + f);
                                if (dst) dst.value = el.value;
                            }
                            updateAddresses();
                        });
                        el.addEventListener('change', function(){
                            if (prefix === 'present' && document.getElementById('same_as_present').checked) {
                                const dst = document.getElementById('permanent_' + f);
                                if (dst) dst.value = el.value;
                            }
                            updateAddresses();
                        });
                    }
                });
            });
            // Initialize read-only state if already checked and build composed addresses
            copyPresentAddress();

            // AJAX mobile uniqueness validation (debounced)
            const mobileEl = document.getElementById('mobile');
            const submitBtn = document.querySelector('button[type="submit"]');
            const validateUrl = "{{ route('admission.validate.mobile', $school->code) }}";
            let mobileTimer = null;
            function setMobileBusy(busy) {
                if (!submitBtn) return;
                submitBtn.disabled = busy;
            }
            async function checkMobileAjax() {
                const val = (mobileEl.value || '').trim();
                if (!/^01\d{9}$/.test(val)) return; // local format gate
                try {
                    setMobileBusy(true);
                    const res = await fetch(`${validateUrl}?mobile=${encodeURIComponent(val)}`, { headers: { 'Accept': 'application/json' }});
                    const data = await res.json();
                    if (data && data.ok) {
                        if (data.exists) {
                            // Mark invalid with message
                            mobileEl.classList.add('is-invalid');
                            const fb = mobileEl.parentElement?.querySelector('.invalid-feedback');
                            if (fb) fb.textContent = 'এই মোবাইল নম্বর দিয়ে এই শিক্ষাবর্ষে একটি আবেদন আছে';
                            setMobileBusy(true);
                        } else {
                            mobileEl.classList.remove('is-invalid');
                            setMobileBusy(false);
                        }
                    } else {
                        setMobileBusy(false);
                    }
                } catch (e) {
                    setMobileBusy(false);
                }
            }
            if (mobileEl) {
                mobileEl.addEventListener('input', function(){
                    // Clear immediate invalid state unless format invalid
                    const val = this.value.trim();
                    if (!/^01\d{9}$/.test(val)) {
                        this.classList.add('is-invalid');
                        const fb = this.parentElement?.querySelector('.invalid-feedback');
                        if (fb) fb.textContent = 'মোবাইল 01 দিয়ে শুরু ১১ সংখ্যার হতে হবে';
                        setMobileBusy(true);
                        return;
                    }
                    this.classList.remove('is-invalid');
                    if (mobileTimer) clearTimeout(mobileTimer);
                    mobileTimer = setTimeout(checkMobileAjax, 400);
                });
                mobileEl.addEventListener('blur', function(){
                    if (/^01\d{9}$/.test(this.value.trim())) checkMobileAjax();
                });
            }
        });
    </script>
    @endpush
</x-layout.public>
