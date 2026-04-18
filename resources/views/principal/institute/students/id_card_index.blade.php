@extends('layouts.admin')

@section('title', 'শিক্ষার্থী আইডি কার্ড')

@section('content')
    <div id="idCardApp">
        {{-- ১. হেডার অংশ --}}
        <div class="content-header">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">শিক্ষার্থী আইডি কার্ড ব্যবস্থাপনা</h1>
                </div>
            </div>
        </div>

        {{-- ২. টোস্ট নোটিফিকেশন --}}
        <div v-if="toast.show" :class="['app-toast', 'app-toast--' + toast.type]">
            <i class="fas fa-info-circle mr-2"></i> @{{ toast.message }}
        </div>

        {{-- ৩. ফিল্টার কার্ড --}}
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>শিক্ষাবর্ষ</label>
                            <select v-model="filters.academic_year_id" class="form-control form-control-sm">
                                <option value="">নির্বাচন করুন</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>শ্রেণি</label>
                            <select v-model="filters.class_id" class="form-control form-control-sm">
                                <option value="">নির্বাচন করুন</option>
                                <option v-for="cls in schoolClasses" :value="cls.id" :key="cls.id">@{{ cls.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>শাখা</label>
                            <select v-model="filters.section_id" class="form-control form-control-sm"
                                :disabled="!availableSections.length">
                                <option value="">সকল শাখা</option>
                                <option v-for="sec in availableSections" :value="sec.id" :key="sec.id">@{{ sec.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button @click="loadStudents" class="btn btn-primary btn-sm mb-3 w-100" :disabled="loading">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-search'"></i> খুঁজুন
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ৪. শিক্ষার্থী তালিকা --}}
        <div class="card shadow-sm mt-3" v-if="students.length > 0">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-bold">শিক্ষার্থী তালিকা (@{{ students.length }})</h3>
                    <button @click="openIdCardModal" class="btn btn-success btn-sm" :disabled="selectedIds.length === 0">
                        <i class="fas fa-id-card"></i> আইডি কার্ড জেনারেট করুন (@{{ selectedIds.length }})
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" style="width:50px;">
                                    <input type="checkbox" @change="toggleSelectAll" :checked="allSelected">
                                </th>
                                <th>রোল</th>
                                <th>শিক্ষার্থীর নাম</th>
                                <th>শ্রেণি</th>
                                <th>শাখা</th>
                                <th>গ্রুপ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="student.id">
                                <td class="text-center">
                                    <input type="checkbox" :value="student.id" v-model="selectedIds">
                                </td>
                                <td>@{{ getStudentRoll(student) }}</td>
                                <td>
                                    <strong>@{{ student.student_name_bn || student.student_name_en }}</strong>
                                    <br><small class="text-muted">ID: @{{ student.student_id }}</small>
                                </td>
                                <td>@{{ getStudentClass(student) }}</td>
                                <td>@{{ getStudentSection(student) }}</td>
                                <td>@{{ getStudentGroup(student) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="alert alert-info mt-3" v-else-if="searched && !loading">
            <i class="fas fa-info-circle"></i> কোনো শিক্ষার্থী পাওয়া যায়নি। দয়া করে ফিল্টার পরিবর্তন করে পুনরায় চেষ্টা করুন।
        </div>

        {{-- ৫. কনফিগারেশন মডাল --}}
        <div class="modal fade" id="idCardModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">আইডি কার্ড কাস্টমাইজেশন</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="row no-gutters" style="min-height: 500px;">
                            <!-- বাম পাশ: সেটিংস -->
                            <div class="col-md-4 border-right p-3 bg-light" style="max-height: 70vh; overflow-y: auto;">
                                <h6 class="text-bold mb-3 border-bottom pb-2">কার্ড সাইজ ও ওরিয়েন্টেশন</h6>
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <label class="small">ওরিয়েন্টেশন</label>
                                        <select v-model="idCardSettings.orientation" class="form-control form-control-sm">
                                            <option value="portrait">লম্বালম্বি (Portrait)</option>
                                            <option value="landscape">আড়াআড়ি (Landscape)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <label class="small">ভাষা (Language)</label>
                                        <select v-model="idCardSettings.language" class="form-control form-control-sm">
                                            <option value="bn">বাংলা (Bengali)</option>
                                            <option value="en">ইংরেজি (English)</option>
                                        </select>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">প্রস্থ (mm)</label>
                                        <input type="number" v-model="idCardSettings.card_width" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">উচ্চতা (mm)</label>
                                        <input type="number" v-model="idCardSettings.card_height" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-12 mb-2">
                                        <label class="small">কন্টেন্ট প্যাডিং টপ (mm)</label>
                                        <input type="number" v-model="idCardSettings.content_padding_top" class="form-control form-control-sm">
                                    </div>
                                </div>

                                <h6 class="text-bold mt-3 mb-3 border-bottom pb-2">মার্জিন সেটিংস (mm)</h6>
                                <div class="row">
                                    <div class="col-3 mb-2">
                                        <label class="small">Top</label>
                                        <input type="number" v-model="idCardSettings.margin_top" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label class="small">Bottom</label>
                                        <input type="number" v-model="idCardSettings.margin_bottom" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label class="small">Left</label>
                                        <input type="number" v-model="idCardSettings.margin_left" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label class="small">Right</label>
                                        <input type="number" v-model="idCardSettings.margin_right" class="form-control form-control-sm">
                                    </div>
                                </div>

                                <h6 class="text-bold mt-3 mb-3 border-bottom pb-2">ফটো ও ফন্ট স্টাইল</h6>
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="small">ফটোর প্রস্থ (mm)</label>
                                        <input type="number" v-model="idCardSettings.photo_width" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">ফটোর উচ্চতা (mm)</label>
                                        <input type="number" v-model="idCardSettings.photo_height" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">নামের ফন্ট (pt)</label>
                                        <input type="number" v-model="idCardSettings.name_font_size" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">বডির ফন্ট (pt)</label>
                                        <input type="number" v-model="idCardSettings.details_font_size" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">বডির রঙ</label>
                                        <input type="color" v-model="idCardSettings.details_color" class="form-control form-control-sm p-0 h-auto">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">আইডি নং ফন্ট (pt)</label>
                                        <input type="number" v-model="idCardSettings.id_no_font_size" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">আইডি নং রঙ</label>
                                        <input type="color" v-model="idCardSettings.id_no_color" class="form-control form-control-sm p-0 h-auto">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">নামের রঙ</label>
                                        <input type="color" v-model="idCardSettings.name_color" class="form-control form-control-sm p-0 h-auto">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="small">লাইনের গুরুত্ব</label>
                                        <input type="number" step="0.1" v-model="idCardSettings.row_spacing" class="form-control form-control-sm">
                                    </div>
                                </div>

                                <h6 class="text-bold mt-3 mb-3 border-bottom pb-2">ব্যাকগ্রাউন্ড ইমেজ</h6>
                                <div class="form-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" @change="handleBgUpload"
                                            accept="image/*">
                                        <label class="custom-file-label small">@{{ idCardSettings.bgName || 'ইমেজ সিলেক্ট করুন' }}</label>
                                    </div>
                                </div>

                                <h6 class="text-bold mt-4 mb-3 border-bottom pb-2">প্রদর্শনযোগ্য তথ্য</h6>
                                <div class="row">
                                    <div class="col-6 mb-2" v-for="field in availableFields" :key="field.key">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" :id="'chk_'+field.key"
                                                :value="field.key" v-model="idCardSettings.fields">
                                            <label class="custom-control-label" :for="'chk_'+field.key">@{{ getFieldLabel(field.key) }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ডান পাশ: লাইভ প্রিভিউ -->
                            <div class="col-md-8 p-4 d-flex flex-column bg-secondary justify-content-center align-items-center" style="background-color: #555 !important;">
                                <div class="preview-wrap shadow-lg" :style="idCardPreviewStyle">
                                    <!-- ছবি (Absolute Position for exact MM mapping) -->
                                    <div :style="{
                                        width: idCardSettings.photo_width+'mm', 
                                        height: idCardSettings.photo_height+'mm', 
                                        border: '1px solid #ddd', 
                                        backgroundColor: '#f8f9fa', 
                                        marginBottom: '3mm',
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                        overflow: 'hidden'
                                    }">
                                        <i class="fas fa-user text-muted fa-3x"></i>
                                    </div>
                                    <!-- নাম -->
                                    <div :style="{
                                        fontSize: idCardSettings.name_font_size+'pt', 
                                        color: idCardSettings.name_color, 
                                        fontWeight: 'bold', 
                                        marginBottom: '2mm',
                                        textAlign: 'center',
                                        width: '100%'
                                    }">
                                        @{{ idCardSettings.language === 'en' ? 'Student Full Name' : 'শিক্ষার্থীর পূর্ণ নাম' }}
                                    </div>
                                    <!-- তথ্য সারি -->
                                    <div class="w-100 mt-2">
                                        <div v-for="fieldKey in idCardSettings.fields" :key="fieldKey" 
                                             class="d-flex" 
                                             :style="{
                                                fontSize: idCardSettings.details_font_size+'pt', 
                                                color: idCardSettings.details_color, 
                                                lineHeight: idCardSettings.row_spacing,
                                                marginBottom: '0.5mm'
                                             }">
                                            <div style="width: 35%; padding-right: 5px;" :style="{
                                                fontSize: fieldKey === 'student_id' ? idCardSettings.id_no_font_size+'pt' : 'inherit',
                                                color: fieldKey === 'student_id' ? idCardSettings.id_no_color : 'inherit'
                                            }">@{{ getFieldLabel(fieldKey) }}:</div>
                                            <div style="width: 65%;" :style="{
                                                fontSize: fieldKey === 'student_id' ? idCardSettings.id_no_font_size+'pt' : 'inherit',
                                                color: fieldKey === 'student_id' ? idCardSettings.id_no_color : 'inherit',
                                                fontWeight: fieldKey === 'student_id' ? 'bold' : 'normal'
                                            }">@{{ getFieldMockValue(fieldKey) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-white small">নমুনা আইডি কার্ড প্রিভিউ (বর্ডার কাটার দাগ হিসেবে থাকবে)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ করুন</button>
                        <button type="button" class="btn btn-primary" @click="saveIdCardSettings"
                            :disabled="settingsSaving">
                            <i v-if="settingsSaving" class="fas fa-spinner fa-spin"></i> সেভ করুন
                        </button>
                        <button type="button" class="btn btn-success" @click="printIdCards">
                            <i class="fas fa-print"></i> প্রিন্ট করুন
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Vue({
                el: '#idCardApp',
                data: function() {
                    return {
                        filters: {
                            academic_year_id: '',
                            class_id: '',
                            section_id: '',
                            status: 'active'
                        },
                        schoolClasses: @json($classes),
                        students: [],
                        selectedIds: [],
                        loading: false,
                        searched: false,
                        toast: { show: false, message: '', type: 'success' },
                        csrfToken: '{{ csrf_token() }}',
                        schoolId: '{{ $school->id }}',

                        availableFields: [
                            { key: 'class', label_en: 'Class', label_bn: 'শ্রেণি' },
                            { key: 'section', label_en: 'Section', label_bn: 'শাখা' },
                            { key: 'group', label_en: 'Group', label_bn: 'গ্রুপ' },
                            { key: 'roll', label_en: 'Roll No.', label_bn: 'রোল' },
                            { key: 'reg_no', label_en: 'Reg. No.', label_bn: 'রেজি নং' },
                            { key: 'session', label_en: 'Session', label_bn: 'সেশন' },
                            { key: 'dob', label_en: 'DOB', label_bn: 'জন্ম তারিখ' },
                            { key: 'blood_group', label_en: 'Blood Group', label_bn: 'রক্তের গ্রুপ' },
                            { key: 'father_name', label_en: 'Father', label_bn: 'পিতার নাম' },
                            { key: 'mother_name', label_en: 'Mother', label_bn: 'মাতার নাম' },
                            { key: 'mobile', label_en: 'Mobile No.', label_bn: 'মোবাইল নং' },
                            { key: 'student_id', label_en: 'Student ID', label_bn: 'আইডি নং' }
                        ],
                        idCardSettings: {
                            orientation: 'portrait',
                            language: 'bn',
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
                            name_color: '#d32f2f',
                            details_font_size: 10,
                            details_color: '#000000',
                            id_no_font_size: 10,
                            id_no_color: '#d32f2f',
                            row_spacing: 1.2,
                            show_principal_signature: true,
                            fields: ['class', 'roll', 'section', 'blood_group'],
                            show_school_header: false
                        },
                        settingsSaving: false
                    };
                },
                computed: {
                    availableSections: function() {
                        var self = this;
                        if (!this.filters.class_id) return [];
                        var cls = this.schoolClasses.find(function(c) {
                            return c.id == self.filters.class_id;
                        });
                        return (cls && Array.isArray(cls.sections)) ? cls.sections : [];
                    },
                    allSelected: function() {
                        return this.students.length > 0 && this.selectedIds.length === this.students.length;
                    },
                    idCardPreviewStyle: function() {
                        var s = this.idCardSettings;
                        return {
                            width: s.card_width + 'mm',
                            height: s.card_height + 'mm',
                            paddingTop: s.content_padding_top + 'mm',
                            paddingLeft: s.margin_left + 'mm',
                            paddingRight: s.margin_right + 'mm',
                            paddingBottom: s.margin_bottom + 'mm',
                            backgroundImage: s.background_image ? 'url(' + s.background_image + ')' : 'none',
                            backgroundSize: '100% 100%',
                            backgroundRepeat: 'no-repeat',
                            position: 'relative',
                            border: '1px solid #000',
                            backgroundColor: '#fff',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            overflow: 'hidden',
                            boxSizing: 'border-box'
                        };
                    }
                },
                watch: {
                    'filters.class_id': function() {
                        this.filters.section_id = '';
                    }
                },
                methods: {
                    showToast: function(message, type) {
                        var self = this;
                        this.toast = { show: true, message: message, type: type || 'success' };
                        setTimeout(function() { self.toast.show = false; }, 4000);
                    },
                    toggleSelectAll: function(e) {
                        this.selectedIds = e.target.checked ? this.students.map(function(s) { return s.id; }) : [];
                    },
                    getFieldLabel: function(key) {
                        var field = this.availableFields.find(function(f) { return f.key === key; });
                        if (!field) return key;
                        return this.idCardSettings.language === 'en' ? field.label_en : field.label_bn;
                    },
                    getFieldMockValue: function(key) {
                        var isEn = this.idCardSettings.language === 'en';
                        var mocks = { 
                            'class': isEn ? 'Class Ten' : 'দশম শ্রেণি', 
                            'section': isEn ? 'A' : 'ক', 
                            'group': isEn ? 'Science' : 'বিজ্ঞান', 
                            'roll': isEn ? '1' : '১', 
                            'reg_no': isEn ? '12345678' : '১২৩৪৫৬৭৮', 
                            'session': isEn ? '2024-25' : '২০২৪-২৫', 
                            'dob': isEn ? '01/01/2010' : '০১/০১/২০১০', 
                            'blood_group': 'B+', 
                            'father_name': isEn ? 'Sample Father' : 'বাবার নাম', 
                            'mother_name': isEn ? 'Sample Mother' : 'মায়ের নাম', 
                            'mobile': isEn ? '01711000000' : '০১৭১১০০০০০০',
                            'student_id': '20240001'
                        };
                        return mocks[key] || '-';
                    },
                    getStudentEnrollment: function(student) {
                        return (student && Array.isArray(student.enrollments) && student.enrollments.length > 0) ? student.enrollments[0] : null;
                    },
                    getStudentRoll: function(student) { var e = this.getStudentEnrollment(student); return e ? e.roll_no : '-'; },
                    getStudentClass: function(student) { var e = this.getStudentEnrollment(student); return (e && e.class) ? e.class.name : '-'; },
                    getStudentSection: function(student) { var e = this.getStudentEnrollment(student); return (e && e.section) ? e.section.name : '-'; },
                    getStudentGroup: function(student) { var e = this.getStudentEnrollment(student); return (e && e.group) ? e.group.name : '-'; },
                    getStudentYear: function(student) { var e = this.getStudentEnrollment(student); return (e && e.academic_year) ? e.academic_year.name : '-'; },

                    loadStudents: function() {
                        var self = this;
                        this.loading = true;
                        this.searched = false;
                        this.students = [];
                        this.selectedIds = [];
                        
                        fetch('{{ route("principal.institute.students.id-cards.load", $school) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify(this.filters)
                        })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.students) self.students = data.students;
                            self.showToast(self.students.length + ' জন শিক্ষার্থী লোড হয়েছে।');
                        })
                        .catch(function() { self.showToast('সার্ভার এরর!', 'danger'); })
                        .finally(function() { self.loading = false; self.searched = true; });
                    },

                    openIdCardModal: function() {
                        var self = this;
                        this.loadIdCardSettings().then(function() {
                            $('#idCardModal').modal('show');
                        });
                    },

                    loadIdCardSettings: function() {
                        var self = this;
                        return fetch('{{ route("principal.institute.students.id-cards.settings.load", $school) }}')
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.settings) {
                                if (typeof data.settings.fields === 'string') {
                                    try { data.settings.fields = JSON.parse(data.settings.fields); } catch(e) {}
                                }
                                Object.assign(self.idCardSettings, data.settings);
                            }
                        });
                    },

                    saveIdCardSettings: function() {
                        var self = this;
                        this.settingsSaving = true;
                        fetch('{{ route("principal.institute.students.id-cards.settings.save", $school) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                            body: JSON.stringify(this.idCardSettings)
                        })
                        .then(function(res) { return res.json(); })
                        .then(function(data) { if (data.success) self.showToast('সেভ হয়েছে।'); })
                        .catch(function() { self.showToast('ব্যর্থ!', 'danger'); })
                        .finally(function() { self.settingsSaving = false; });
                    },

                    handleBgUpload: function(e) {
                        var self = this;
                        var file = e.target.files[0];
                        if (!file) return;
                        this.idCardSettings.bgName = file.name;
                        var reader = new FileReader();
                        reader.onload = function(f) { self.idCardSettings.background_image = f.target.result; };
                        reader.readAsDataURL(file);
                    },

                    printIdCards: function() {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("principal.institute.students.id-cards.print", $school) }}';
                        form.target = '_blank';
                        
                        var inputs = [
                            { name: '_token', value: this.csrfToken },
                            { name: 'student_ids', value: JSON.stringify(this.selectedIds) },
                            { name: 'settings', value: JSON.stringify(this.idCardSettings) }
                        ];

                        inputs.forEach(function(i) {
                            var el = document.createElement('input');
                            el.type = 'hidden'; el.name = i.name; el.value = i.value;
                            form.appendChild(el);
                        });

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                },
                mounted: function() {
                    console.log("Student ID Card Module Ready");
                }
            });
        });
    </script>

    <style>
        .app-toast {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 12px 20px; border-radius: 4px; color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: 500;
        }
        .app-toast--success { background-color: #28a745; }
        .app-toast--danger { background-color: #dc3545; }
        .preview-wrap { border-radius: 4px; box-sizing: border-box; }
    </style>
@endpush