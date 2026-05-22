@extends('layouts.admin')
@section('title', 'Add Public Exam Info')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">পাবলিক পরীক্ষার তথ্য যুক্ত করুন</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item active">Public Exam Info</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid" id="publicExamApp">

    {{-- Toast notification --}}
    <transition name="toast-fade">
        <div v-if="toast.show" :class="['app-toast', 'app-toast--' + toast.type]">
            <i :class="toast.type === 'success' ? 'fas fa-check-circle' : (toast.type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-times-circle')" class="mr-2"></i>@{{ toast.message }}
        </div>
    </transition>

    {{-- ১. Filter Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> ফিল্টার করুন</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                        <select v-model="filters.academic_year_id" class="form-control form-control-sm">
                            <option value="">-- নির্বাচন করুন --</option>
                            @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">শ্রেণি <span class="text-danger">*</span></label>
                        <select v-model="filters.class_id" class="form-control form-control-sm">
                            <option value="">-- নির্বাচন করুন --</option>
                            @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">পাবলিক পরীক্ষার নাম <span class="text-danger">*</span></label>
                        <select v-model="filters.public_exam_name" class="form-control form-control-sm">
                            <option value="">-- নির্বাচন করুন --</option>
                            @foreach($publicExams as $pe)
                            <option value="{{ $pe->short_name }}">{{ $pe->short_name }} — {{ $pe->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">স্ট্যাটাস</label>
                        <select v-model="filters.status" class="form-control form-control-sm">
                            <option value="active">সক্রিয়</option>
                            <option value="inactive">নিষ্ক্রিয়</option>
                            <option value="graduated">উত্তীর্ণ</option>
                            <option value="transferred">স্থানান্তরিত</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button @click="loadStudents" class="btn btn-primary btn-sm mb-2 w-100" :disabled="loading">
                        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-search'"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ২. Common Info Apply Panel (দেখাবে শুধু লোডের পরে) --}}
    <div class="card card-warning card-outline" v-if="students.length > 0">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-magic mr-1"></i> সাধারণ তথ্য একসাথে প্রয়োগ করুন</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Board</label>
                        <select v-model="bulk.board" class="form-control form-control-sm">
                            <option value="">-- বোর্ড --</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Chittagong">Chittagong</option>
                            <option value="Sylhet">Sylhet</option>
                            <option value="Comilla">Comilla</option>
                            <option value="Barisal">Barisal</option>
                            <option value="Jashore">Jashore</option>
                            <option value="Dinajpur">Dinajpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Madrasah">Madrasah</option>
                            <option value="Technical">Technical</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Year</label>
                        <input v-model="bulk.exam_year" class="form-control form-control-sm" placeholder="e.g. 2025" maxlength="4">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Session</label>
                        <input v-model="bulk.session" class="form-control form-control-sm" placeholder="e.g. 2023-24" maxlength="20">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Result</label>
                        <input v-model="bulk.result" class="form-control form-control-sm" placeholder="GPA 5.00" maxlength="255">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Cand. Type</label>
                        <select v-model="bulk.candidate_type" class="form-control form-control-sm">
                            <option value="">-- বাছাই --</option>
                            <option value="Regular">Regular</option>
                            <option value="Irregular">Irregular</option>
                            <option value="Private">Private</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Centre</label>
                        <input v-model="bulk.center_name" class="form-control form-control-sm" placeholder="Centre name">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold d-block">প্রয়োগ করুন</label>
                        <div class="btn-group w-100">
                            <button @click="applyToAll" class="btn btn-warning btn-sm" title="সকল শিক্ষার্থীর জন্য প্রয়োগ">
                                <i class="fas fa-users mr-1"></i> সবার
                            </button>
                            <button @click="applyToSelected" class="btn btn-info btn-sm" :disabled="selectedIds.length === 0" :title="selectedIds.length + ' জন নির্বাচিতের জন্য প্রয়োগ'">
                                <i class="fas fa-check-square mr-1"></i> নির্বাচিত
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                শুধু পূরণ করা ফিল্ডগুলো প্রয়োগ হবে — খালি ফিল্ড বিদ্যমান তথ্য পরিবর্তন করবে না।
                <span v-if="selectedIds.length > 0" class="ml-2 text-info font-weight-bold"><i class="fas fa-check-square"></i> @{{ selectedIds.length }} জন নির্বাচিত</span>
            </p>
        </div>
    </div>

    {{-- ৩. Student Table --}}
    <div class="card" v-if="students.length > 0">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users mr-1"></i>
                শিক্ষার্থী তালিকা — <strong>@{{ filters.public_exam_name }}</strong>
                <span class="badge badge-info ml-2">@{{ students.length }} জন</span>
                <span v-if="selectedIds.length > 0" class="badge badge-warning ml-1">@{{ selectedIds.length }} নির্বাচিত</span>
            </h3>
            <div class="card-tools">
                <button @click="printView" class="btn btn-info btn-sm mr-1" :disabled="students.length === 0" title="প্রতি শিক্ষার্থী আলাদা পৃষ্ঠায়">
                    <i class="fas fa-print mr-1"></i> প্রিন্ট
                </button>
                <button @click="printTableView" class="btn btn-outline-info btn-sm mr-1" :disabled="students.length === 0" title="সকল শিক্ষার্থী একটি টেবিলে">
                    <i class="fas fa-table mr-1"></i> Table Print
                </button>
                <button @click="openIdCardModal" class="btn btn-primary btn-sm mr-1" :disabled="students.length === 0" title="আইডি কার্ড তৈরি করুন">
                    <i class="fas fa-id-card mr-1"></i> আইডি কার্ড
                </button>
                <button @click="saveAll" class="btn btn-success btn-sm" :disabled="bulkSaving">
                    <i :class="bulkSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'" class="mr-1"></i>
                    @{{ bulkSaving ? 'সেভ হচ্ছে...' : 'সব সেভ করুন' }}
                </button>
                <button @click="saveSelected" class="btn btn-primary btn-sm ml-1" :disabled="selectedIds.length === 0 || bulkSaving">
                    <i class="fas fa-save mr-1"></i> নির্বাচিত সেভ (<span>@{{ selectedIds.length }}</span>)
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover mb-0" style="font-size:12.5px;">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width:36px;">
                                <input type="checkbox" @change="toggleSelectAll" :checked="allSelected" title="সব নির্বাচন করুন">
                            </th>
                            <th class="text-center" style="width:34px;">#</th>
                            <th style="min-width:150px;">নাম / পিতার নাম</th>
                            <th class="text-center" style="width:55px;">রোল</th>
                            <th style="min-width:105px;">Board</th>
                            <th style="min-width:95px;">Roll No.</th>
                            <th style="min-width:115px;">Reg. No.</th>
                            <th style="min-width:100px;">GPA / ফলাফল</th>
                            <th style="min-width:80px;">Year</th>
                            <th style="min-width:95px;">Session</th>
                            <th style="min-width:115px;">Cand. Type</th>
                            <th style="min-width:125px;">Centre</th>
                            <th class="text-center" style="width:60px;">সেভ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(student, idx) in students" :key="student.id" :class="{'table-active': isSelected(student.id)}">
                            <td class="text-center align-middle">
                                <input type="checkbox" :value="student.id" v-model="selectedIds">
                            </td>
                            <td class="text-center align-middle">@{{ idx + 1 }}</td>
                            <td class="align-middle">
                                <div class="font-weight-bold">@{{ student.name }}</div>
                                <div class="small text-muted text-uppercase">@{{ student.father_name }}</div>
                            </td>
                            <td class="text-center align-middle">@{{ student.roll_no }}</td>
                            <td>
                                <select v-model="student.board" class="form-control form-control-sm">
                                    <option value="">-- --</option>
                                    <option value="Dhaka">Dhaka</option>
                                    <option value="Rajshahi">Rajshahi</option>
                                    <option value="Chittagong">Chittagong</option>
                                    <option value="Sylhet">Sylhet</option>
                                    <option value="Comilla">Comilla</option>
                                    <option value="Barisal">Barisal</option>
                                    <option value="Jashore">Jashore</option>
                                    <option value="Dinajpur">Dinajpur</option>
                                    <option value="Mymensingh">Mymensingh</option>
                                    <option value="Madrasah">Madrasah</option>
                                    <option value="Technical">Technical</option>
                                </select>
                            </td>
                            <td><input v-model="student.roll_no_pub" class="form-control form-control-sm" placeholder="Roll No."></td>
                            <td><input v-model="student.reg_no" class="form-control form-control-sm" placeholder="Reg. No."></td>
                            <td><input v-model="student.result" class="form-control form-control-sm" placeholder="GPA / ফলাফল"></td>
                            <td><input v-model="student.exam_year" class="form-control form-control-sm" placeholder="Year" maxlength="4"></td>
                            <td><input v-model="student.session" class="form-control form-control-sm" placeholder="2023-24" maxlength="20"></td>
                            <td>
                                <select v-model="student.candidate_type" class="form-control form-control-sm">
                                    <option value="">-- --</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Irregular">Irregular</option>
                                    <option value="Private">Private</option>
                                </select>
                            </td>
                            <td><input v-model="student.center_name" class="form-control form-control-sm" placeholder="Centre"></td>
                            <td class="text-center align-middle">
                                <button @click="saveOne(student)" class="btn btn-success btn-sm px-2" :disabled="student.saving" title="সেভ করুন">
                                    <i :class="student.saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-lightbulb text-warning mr-1"></i>
            টিপ: উপরের Common Info প্যানেল থেকে সাধারণ তথ্য সবার জন্য একসাথে পূরণ করুন, এরপর Reg. No. / Roll No. আলাদা করে দিয়ে "সব সেভ করুন" বাটনে ক্লিক করুন।
        </div>
    </div>

    <div class="alert alert-info" v-if="searched && students.length === 0 && !loading">
        <i class="fas fa-info-circle mr-1"></i> কোনো শিক্ষার্থী পাওয়া যায়নি।
    </div>

    {{-- ৪. ID Card Magic Modal --}}
    <div class="modal fade" id="idCardModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title font-weight-bold" style="font-size:16px;"><i class="fas fa-id-card mr-2"></i> আইডি কার্ড জেনারেটর ও কাস্টমাইজেশন</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="row no-gutters">
                        {{-- Settings Pane --}}
                        <div class="col-md-3 border-right bg-light" style="max-height: 80vh; overflow-y: auto; padding: 15px;">
                            <h6 class="font-weight-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-cog mr-1"></i> ডিজাইন সেটিংস</h6>
                            
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold mb-1">ওরিয়েন্টেশন</label>
                                <select v-model="idCardSettings.orientation" class="form-control form-control-sm">
                                    <option value="portrait">Portrait (লম্বালম্বি)</option>
                                    <option value="landscape">Landscape (আড়াআড়ি)</option>
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label class="small font-weight-bold mb-1">ব্যাকগ্রাউন্ড ছবি (ঐচ্ছিক)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="bgUpload" @change="handleBgUpload" accept="image/*">
                                    <label class="custom-file-label custom-file-label-sm" for="bgUpload" style="font-size:11px; overflow:hidden;">@{{ idCardSettings.bgName || 'বাছাই করুন' }}</label>
                                </div>
                            </div>

                            <h6 class="small font-weight-bold text-muted mt-3 mb-2">কার্ড সাইজ ও মার্জিন (mm)</h6>
                            <div class="row no-gutters">
                                <div class="col-6 pr-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Width</label><input type="number" v-model.number="idCardSettings.card_width" class="form-control form-control-sm"></div></div>
                                <div class="col-6 pl-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Height</label><input type="number" v-model.number="idCardSettings.card_height" class="form-control form-control-sm"></div></div>
                                <div class="col-12"><div class="form-group mb-2"><label class="x-small d-block mb-0 text-primary font-weight-bold">Content Offset (Top)</label><input type="number" step="0.5" v-model.number="idCardSettings.content_padding_top" class="form-control form-control-sm" title="উপরের ডিজাইন স্কিপ করার জন্য"></div></div>
                                <div class="col-6 pr-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Margin L</label><input type="number" v-model.number="idCardSettings.margin_left" class="form-control form-control-sm"></div></div>
                                <div class="col-6 pl-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Margin R</label><input type="number" v-model.number="idCardSettings.margin_right" class="form-control form-control-sm"></div></div>
                            </div>

                            <h6 class="small font-weight-bold text-muted mt-2 mb-2">ফন্ট ও স্টাইল</h6>
                            <div class="row no-gutters">
                                <div class="col-6 pr-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Name Size</label><input type="number" v-model.number="idCardSettings.name_font_size" class="form-control form-control-sm"></div></div>
                                <div class="col-6 pl-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Name Color</label><input type="color" v-model="idCardSettings.name_color" class="form-control form-control-sm p-0 border-0 h-auto"></div></div>
                                <div class="col-6 pr-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Detail Size</label><input type="number" v-model.number="idCardSettings.details_font_size" class="form-control form-control-sm"></div></div>
                                <div class="col-6 pl-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Detail Color</label><input type="color" v-model="idCardSettings.details_color" class="form-control form-control-sm p-0 border-0 h-auto"></div></div>
                                <div class="col-6 pr-1"><div class="form-group mb-2"><label class="x-small d-block mb-0">Row Gap</label><input type="number" step="0.1" v-model.number="idCardSettings.row_spacing" class="form-control form-control-sm"></div></div>
                            </div>

                            <h6 class="small font-weight-bold text-muted mt-3 mb-2">প্রদর্শিত ফিল্ডসমূহ</h6>
                            <div class="row no-gutters mb-2">
                                <div class="col-6 mb-1 text-truncate" v-for="field in availableFields" :key="field.key" :title="field.label">
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        <input type="checkbox" class="custom-control-input" :id="'chk_'+field.key" :value="field.key" v-model="idCardSettings.fields">
                                        <label class="custom-control-label" :for="'chk_'+field.key" style="font-size: 11px; cursor:pointer;">@{{ field.label }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="signToggle" v-model="idCardSettings.show_principal_signature">
                                <label class="custom-control-label small font-weight-bold" for="signToggle">Principal Signature</label>
                            </div>
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="showHeaderToggle" v-model="idCardSettings.show_school_header">
                                <label class="custom-control-label small font-weight-bold" for="showHeaderToggle">স্কুলের নাম (হেডার)</label>
                            </div>

                            <hr class="my-3">
                            <button @click="saveIdCardSettings" class="btn btn-primary btn-sm btn-block" :disabled="settingsSaving">
                                <i :class="settingsSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i> সেটিংস সেভ করুন
                            </button>
                            <button @click="idCardListVisible = true" class="btn btn-warning btn-sm btn-block mt-2 font-weight-bold">
                                <i class="fas fa-sync-alt mr-1"></i> জেনারেট / তালিকা দেখুন
                            </button>
                        </div>

                        {{-- Preview & List Pane --}}
                        <div class="col-md-9 bg-dark d-flex flex-column" style="height: 80vh;">
                            {{-- Preview Area --}}
                            <div v-if="!idCardListVisible" class="flex-grow-1 d-flex align-items-center justify-content-center p-4">
                                <div class="preview-wrap" style="transform: scale(1.2);">
                                    <div class="id-card-preview" :style="idCardPreviewStyle">
                                        {{-- School Header Simulation --}}
                                        <div v-if="idCardSettings.show_school_header" style="width:100%; text-align:center; padding: 5px; margin-bottom: 5px; border-bottom: 1px solid #ddd; background: rgba(255,255,255,0.7);">
                                            <div style="font-weight:900; font-size:12px; color:#222;">{{ $school->name ?? 'YOUR SCHOOL NAME' }}</div>
                                            <div style="font-size:8px; font-weight:500;">{{ $school->address ?? 'School Address Goes Here' }}</div>
                                        </div>

                                        {{-- Gradient Photo Border Simulation --}}
                                        <div class="photo-box-preview" :style="{
                                            marginBottom: (idCardSettings.row_spacing * 2) + 'mm',
                                            padding: '2px',
                                            background: 'linear-gradient(45deg, #fbc02d, #f57c00, #d32f2f)',
                                            lineHeight: 0
                                        }">
                                            <div style="background:#fff; border:0.5px solid #fff;" :style="{width: idCardSettings.photo_width+'mm', height: idCardSettings.photo_height+'mm'}"></div>
                                        </div>
                                        <div class="details-preview w-100">
                                            <div class="name-preview font-weight-bold text-center" :style="{fontSize: idCardSettings.name_font_size+'px', color: idCardSettings.name_color, marginBottom: idCardSettings.row_spacing+'mm', fontWeight: '900'}">MD. STUDENT NAME</div>
                                            
                                            <div class="px-3">
                                                <table style="width:100%; font-family: sans-serif; font-weight: 500;" :style="{fontSize: idCardSettings.details_font_size+'px', color: idCardSettings.details_color}">
                                                    <tr v-for="field in idCardSettings.fields" :key="field" :style="{height: (idCardSettings.row_spacing * 3) + 'px'}">
                                                        <td style="white-space:nowrap; padding-right:5px; width:45%;">@{{ getFieldLabel(field) }}:</td>
                                                        <td>@{{ getFieldMockValue(field) }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Bottom Left ID Row --}}
                                        <div style="position: absolute; bottom: 8mm; left: 5mm; display: flex; align-items: center; font-family: sans-serif;">
                                            <span style="font-weight: 900;" :style="{fontSize: (idCardSettings.details_font_size + 1)+'px'}">ID No. : </span>
                                            <span style="font-weight: 900; color: #d32f2f; margin-left: 4px;" :style="{fontSize: (idCardSettings.details_font_size + 1)+'px'}">4090438</span>
                                        </div>

                                        {{-- Signature Simulation --}}
                                        <div v-if="idCardSettings.show_principal_signature" style="position:absolute; bottom:8mm; right:5mm; text-align:center;">
                                            <div style="font-size: 8px; font-weight: 900; color: #444;">Principal</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="position:absolute; top:10px; right:10px;" class="badge badge-info shadow-sm">Live Preview</div>
                            </div>

                            {{-- Student List Area --}}
                            <div v-else class="flex-grow-1 bg-white p-3 d-flex flex-column" style="overflow:hidden;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="font-weight-bold text-primary mb-0"><i class="fas fa-check-square mr-1"></i> শিক্ষার্থী নির্বাচন করুন</h6>
                                    <div>
                                        <span class="mr-3 font-weight-bold">নির্বাচিত: <span class="text-primary">@{{ selectedIdCards.length }}</span> জন</span>
                                        <button @click="idCardListVisible = false" class="btn btn-secondary btn-xs"><i class="fas fa-edit"></i> ডিজাইন পরিবর্তন</button>
                                        <button @click="printIdCards" class="btn btn-success btn-sm ml-2" :disabled="selectedIdCards.length === 0">
                                            <i class="fas fa-print mr-1"></i> প্রিন্ট ভিউ
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive flex-grow-1">
                                    <table class="table table-sm table-bordered table-striped" style="font-size:13px;">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th class="text-center" style="width: 40px;">
                                                    <input type="checkbox" @change="toggleSelectAllId" :checked="allIdSelected">
                                                </th>
                                                <th>নাম ও আইডি</th>
                                                <th class="text-center">রোল</th>
                                                <th>পরীক্ষা</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="s in students" :key="'idm-'+s.id">
                                                <td class="text-center align-middle">
                                                    <input type="checkbox" :value="s.id" v-model="selectedIdCards">
                                                </td>
                                                <td>
                                                    <strong>@{{ s.name }}</strong>
                                                    <small class="d-block text-muted">@{{ s.student_id }}</small>
                                                </td>
                                                <td class="text-center align-middle">@{{ s.roll_no }}</td>
                                                <td class="align-middle text-muted">@{{ filters.public_exam_name }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.min.js"></script>
<script>
new Vue({
    el: '#publicExamApp',
    data: {
        filters: {
            academic_year_id: '',
            class_id: '',
            public_exam_name: '',
            status: 'active',
        },
        bulk: {
            board: '',
            exam_year: '',
            session: '',
            candidate_type: '',
            center_name: '',
            result: '',
        },
        students: [],
        selectedIds: [],
        loading: false,
        searched: false,
        bulkSaving: false,
        toast: { show: false, message: '', type: 'success' },
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        schoolId: {{ $school->id }},
        
        // ID Card related
        availableFields: [
            { key: 'class', label: 'Class/Exam' },
            { key: 'roll', label: 'Roll No.' },
            { key: 'reg_no', label: 'Reg. No.' },
            { key: 'session', label: 'Session' },
            { key: 'center', label: 'Center' },
            { key: 'dob', label: 'Date of Birth' },
            { key: 'blood_group', label: 'Blood Group' },
            { key: 'father_name', label: 'Father\'s Name' },
            { key: 'mother_name', label: 'Mother\'s Name' },
            { key: 'mobile', label: 'Mobile No.' }
        ],
        idCardSettings: {
            orientation: 'portrait',
            background_image: '',
            bgName: '',
            card_width: 54,
            card_height: 86,
            photo_width: 21,
            photo_height: 25,
            margin_top: 5,
            margin_bottom: 5,
            margin_left: 5,
            margin_right: 5,
            content_padding_top: 32,
            name_font_size: 11,
            name_color: '#d32f2f', // Red matching image
            details_font_size: 10,
            details_color: '#000000',
            row_spacing: 1.2,
            show_principal_signature: true,
            fields: ['class', 'roll', 'reg_no', 'center'],
            show_school_header: false
        },
        idCardListVisible: false,
        selectedIdCards: [],
        settingsSaving: false,
    },
    computed: {
        allSelected() {
            return this.students.length > 0 && this.selectedIds.length === this.students.length;
        },
        allIdSelected() {
            return this.students.length > 0 && this.selectedIdCards.length === this.students.length;
        },
        idCardPreviewStyle() {
            let s = this.idCardSettings;
            return {
                width: s.card_width + 'mm',
                height: s.card_height + 'mm',
                paddingTop: s.content_padding_top + 'mm',
                paddingLeft: s.margin_left + 'mm',
                paddingRight: s.margin_right + 'mm',
                paddingBottom: s.margin_bottom + 'mm',
                backgroundImage: s.background_image ? `url(${s.background_image})` : 'none',
                backgroundSize: '100% 100%',
                backgroundRepeat: 'no-repeat',
                position: 'relative',
                border: '1px solid #000',
                backgroundColor: '#fff',
                margin: '0 auto',
                boxSizing: 'border-box',
                overflow: 'hidden',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center'
            };
        }
    },
    methods: {
        showToast(message, type) {
            this.toast = { show: true, message, type: type || 'success' };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        isSelected(id) {
            return this.selectedIds.includes(id);
        },

        toggleSelectAll(e) {
            this.selectedIds = e.target.checked ? this.students.map(s => s.id) : [];
        },

        getFieldLabel(key) {
            const field = this.availableFields.find(f => f.key === key);
            return field ? field.label : key;
        },
        
        getFieldMockValue(key) {
            const mocks = {
                'class': 'SSC-2026',
                'roll': '130572',
                'reg_no': '2313840315',
                'session': '2024-2025',
                'center': 'Gangni-476',
                'dob': '12/05/2010',
                'blood_group': 'B+',
                'father_name': 'MD. ABDUL KARIM',
                'mother_name': 'SAHIDA BEGUM',
                'mobile': '01711000000'
            };
            return mocks[key] || '-';
        },

        // Apply bulk common values to ALL students
        applyToAll() {
            this.students.forEach(s => this._applyBulk(s));
            this.showToast('সব শিক্ষার্থীর তথ্য আপডেট করা হয়েছে। এখন সেভ করুন।', 'warning');
        },

        // Apply to only checked students
        applyToSelected() {
            if (!this.selectedIds.length) return;
            this.students.forEach(s => {
                if (this.selectedIds.includes(s.id)) this._applyBulk(s);
            });
            this.showToast(this.selectedIds.length + ' জন নির্বাচিত শিক্ষার্থীর তথ্য আপডেট করা হয়েছে।', 'warning');
        },

        _applyBulk(student) {
            if (this.bulk.board)          student.board          = this.bulk.board;
            if (this.bulk.exam_year)      student.exam_year      = this.bulk.exam_year;
            if (this.bulk.session)        student.session        = this.bulk.session;
            if (this.bulk.candidate_type) student.candidate_type = this.bulk.candidate_type;
            if (this.bulk.center_name)    student.center_name    = this.bulk.center_name;
            if (this.bulk.result)         student.result         = this.bulk.result;
        },

        // Print view (individual per student)
        printView() {
            if (!this.filters.academic_year_id || !this.filters.class_id || !this.filters.public_exam_name) {
                this.showToast('অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি ও পাবলিক পরীক্ষার নাম নির্বাচন করুন।', 'warning');
                return;
            }
            const query = new URLSearchParams(this.filters).toString();
            const url = `{{ route("principal.institute.students.public-exam-info.print", $school) }}?${query}`;
            window.open(url, '_blank');
        },

        // Print table view (all students in one table)
        printTableView() {
            if (!this.filters.academic_year_id || !this.filters.class_id || !this.filters.public_exam_name) {
                this.showToast('অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি ও পাবলিক পরীক্ষার নাম নির্বাচন করুন।', 'warning');
                return;
            }
            const query = new URLSearchParams(this.filters).toString();
            const url = `{{ route("principal.institute.students.public-exam-info.print-table", $school) }}?${query}`;
            window.open(url, '_blank');
        },

        // ID Card Modal
        async openIdCardModal() {
            this.selectedIdCards = [...this.selectedIds]; 
            this.idCardListVisible = false;
            await this.loadIdCardSettings();
            $('#idCardModal').modal('show');
        },

        async loadIdCardSettings() {
            try {
                const res = await fetch('{{ route("principal.institute.students.public-exam-info.id-card-settings.load", $school) }}');
                const data = await res.json();
                if (data.settings) {
                    if (!Array.isArray(data.settings.fields)) {
                        data.settings.fields = ['class', 'roll', 'reg_no', 'center'];
                    }
                    // Update settings but keep defaults for missing fields
                    Object.assign(this.idCardSettings, data.settings);
                }
            } catch (e) {
                console.error('Failed to load settings', e);
            }
        },

        async saveIdCardSettings() {
            this.settingsSaving = true;
            try {
                const res = await fetch('{{ route("principal.institute.students.public-exam-info.id-card-settings.save", $school) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify(this.idCardSettings)
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('সেটিংস সফলভাবে সংরক্ষিত হয়েছে।', 'success');
                }
            } catch (e) {
                this.showToast('সেটিংস সেভ করতে ব্যর্থ।', 'danger');
            } finally {
                this.settingsSaving = false;
            }
        },

        handleBgUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.idCardSettings.bgName = file.name;
            const reader = new FileReader();
            reader.onload = (f) => {
                this.idCardSettings.background_image = f.target.result;
            };
            reader.readAsDataURL(file);
        },

        toggleSelectAllId(e) {
            this.selectedIdCards = e.target.checked ? this.students.map(s => s.id) : [];
        },

        printIdCards() {
            if (this.selectedIdCards.length === 0) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("principal.institute.students.public-exam-info.id-card-print", $school) }}';
            form.target = '_blank';

            const addField = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            };

            addField('_token', this.csrfToken);
            addField('student_ids', JSON.stringify(this.selectedIdCards));
            addField('exam_name', this.filters.public_exam_name);
            addField('settings', JSON.stringify(this.idCardSettings));

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        async loadStudents() {
            if (!this.filters.academic_year_id || !this.filters.class_id || !this.filters.public_exam_name) {
                this.showToast('অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি ও পাবলিক পরীক্ষার নাম নির্বাচন করুন।', 'warning');
                return;
            }
            this.loading = true;
            this.searched = false;
            this.students = [];
            this.selectedIds = [];
            try {
                const res = await fetch('{{ route("principal.institute.students.public-exam-info.load", $school) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(this.filters),
                });
                const data = await res.json();
                if (data.students) {
                    this.students = data.students.map(s => ({ ...s, saving: false }));
                }
                this.showToast(this.students.length + ' জন শিক্ষার্থী লোড হয়েছে।', 'success');
            } catch (e) {
                this.showToast('শিক্ষার্থী লোড করতে সমস্যা হয়েছে।', 'danger');
            } finally {
                this.loading = false;
                this.searched = true;
            }
        },

        async saveOne(student) {
            student.saving = true;
            const ok = await this._save(student);
            student.saving = false;
            if (ok) this.showToast(student.name + ' — তথ্য সফলভাবে সংরক্ষিত।', 'success');
            else     this.showToast(student.name + ' — সেভ করতে ব্যর্থ।', 'danger');
        },

        async saveAll() {
            this.bulkSaving = true;
            let success = 0, failed = 0;
            for (const s of this.students) {
                s.saving = true;
                const ok = await this._save(s);
                s.saving = false;
                ok ? success++ : failed++;
            }
            this.bulkSaving = false;
            const msg = success + ' জনের তথ্য সেভ হয়েছে' + (failed ? ', ' + failed + ' জন ব্যর্থ।' : '।');
            this.showToast(msg, failed ? 'warning' : 'success');
        },

        async saveSelected() {
            if (!this.selectedIds.length) return;
            this.bulkSaving = true;
            let success = 0, failed = 0;
            const targets = this.students.filter(s => this.selectedIds.includes(s.id));
            for (const s of targets) {
                s.saving = true;
                const ok = await this._save(s);
                s.saving = false;
                ok ? success++ : failed++;
            }
            this.bulkSaving = false;
            const msg = success + ' জনের তথ্য সেভ হয়েছে' + (failed ? ', ' + failed + ' জন ব্যর্থ।' : '।');
            this.showToast(msg, failed ? 'warning' : 'success');
        },

        async _save(student) {
            try {
                const url = `/principal/institute/${this.schoolId}/students/${student.id}/public-exam-info/save`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        exam_name:      student.exam_name,
                        board:          student.board,
                        roll_no:        student.roll_no_pub,
                        reg_no:         student.reg_no,
                        exam_year:      student.exam_year,
                        session:        student.session,
                        candidate_type: student.candidate_type,
                        center_name:    student.center_name,
                        result:         student.result,
                    }),
                });
                const data = await res.json();
                return !!data.success;
            } catch (e) {
                return false;
            }
        },
    },
});
</script>

<style>
/* Toast */
.app-toast {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 290px;
    max-width: 400px;
    padding: 13px 20px;
    border-radius: 8px;
    color: #fff;
    font-size: 13.5px;
    font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,.18);
}
.app-toast--success { background: #28a745; }
.app-toast--warning { background: #e0a800; color: #222 !important; }
.app-toast--danger  { background: #dc3545; }
.toast-fade-enter-active, .toast-fade-leave-active { transition: opacity .35s, transform .35s; }
.toast-fade-enter, .toast-fade-leave-to { opacity: 0; transform: translateX(30px); }
/* Highlight selected row */
.table-active td { background: #fff8e7 !important; }

.x-small { font-size: 10px; }
.id-card-preview {
    box-shadow: 0 5px 25px rgba(0,0,0,0.5);
    transition: all 0.3s ease;
}
</style>
@endpush
