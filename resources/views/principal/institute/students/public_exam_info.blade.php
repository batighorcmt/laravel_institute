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
                            <option value="Jessore">Jessore</option>
                            <option value="Dinajpur">Dinajpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Madrasah">Madrasah</option>
                            <option value="Technical">Technical</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Exam Year</label>
                        <input v-model="bulk.exam_year" class="form-control form-control-sm" placeholder="e.g. 2025" maxlength="4">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Session</label>
                        <input v-model="bulk.session" class="form-control form-control-sm" placeholder="e.g. 2023-24" maxlength="20">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Candidate Type</label>
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
                                <i class="fas fa-users mr-1"></i> সবার জন্য
                            </button>
                            <button @click="applyToSelected" class="btn btn-info btn-sm" :disabled="selectedIds.length === 0" :title="selectedIds.length + ' জন নির্বাচিতের জন্য প্রয়োগ'">
                                <i class="fas fa-check-square mr-1"></i> নির্বাচিতদের
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
                <button @click="printView" class="btn btn-info btn-sm mr-1" :disabled="students.length === 0">
                    <i class="fas fa-print mr-1"></i> প্রিন্ট
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
                            <th style="min-width:130px;">নাম / ID</th>
                            <th class="text-center" style="width:55px;">রোল</th>
                            <th style="min-width:105px;">Board</th>
                            <th style="min-width:95px;">Roll No.</th>
                            <th style="min-width:115px;">Reg. No.</th>
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
                                <strong>@{{ student.name }}</strong>
                                <small class="d-block text-muted">@{{ student.student_id }}</small>
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
                                    <option value="Jessore">Jessore</option>
                                    <option value="Dinajpur">Dinajpur</option>
                                    <option value="Mymensingh">Mymensingh</option>
                                    <option value="Madrasah">Madrasah</option>
                                    <option value="Technical">Technical</option>
                                </select>
                            </td>
                            <td><input v-model="student.roll_no_pub" class="form-control form-control-sm" placeholder="Roll No."></td>
                            <td><input v-model="student.reg_no" class="form-control form-control-sm" placeholder="Reg. No."></td>
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
        },
        students: [],
        selectedIds: [],
        loading: false,
        searched: false,
        bulkSaving: false,
        toast: { show: false, message: '', type: 'success' },
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        schoolId: {{ $school->id }},
    },
    computed: {
        allSelected() {
            return this.students.length > 0 && this.selectedIds.length === this.students.length;
        },
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
        },

        // Print view
        printView() {
            if (!this.filters.academic_year_id || !this.filters.class_id || !this.filters.public_exam_name) {
                this.showToast('অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি ও পাবলিক পরীক্ষার নাম নির্বাচন করুন।', 'warning');
                return;
            }
            const query = new URLSearchParams(this.filters).toString();
            const url = `{{ route("principal.institute.students.public-exam-info.print", $school) }}?${query}`;
            window.open(url, '_blank');
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
</style>
@endpush
