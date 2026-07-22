@extends('layouts.admin')
@section('title', 'Documents: অভিভাবক সাক্ষাৎকার টোকেন')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
            <h5 class="mb-0"><i class="fas fa-id-badge mr-2"></i>অভিভাবক সাক্ষাৎকার টোকেন জেনারেটর</h5>
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                    <select class="form-control" id="academicYear" required>
                        <option value="">-- শিক্ষাবর্ষ নির্বাচন করুন --</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                {{ $year->name_bn ?: $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">শ্রেণি <span class="text-danger">*</span></label>
                    <select class="form-control" id="classSelect" required>
                        <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->bangla_name ?: $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">শাখা</label>
                    <select class="form-control" id="sectionSelect">
                        <option value="">-- সকল শাখা --</option>
                    </select>
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary btn-block" id="btnFilter" style="height: calc(1.5em + .75rem + 2px);">
                        <i class="fas fa-search mr-1"></i> সার্চ করুন
                    </button>
                </div>
            </div>

            <!-- Loader -->
            <div id="loadingIndicator" class="text-center my-5" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">লোড হচ্ছে...</span>
                </div>
                <p class="mt-2 text-muted">শিক্ষার্থীর তালিকা লোড করা হচ্ছে...</p>
            </div>

            <!-- Instruction Alert -->
            <div id="instructionAlert" class="alert alert-info border-0 shadow-sm mt-4 d-flex align-items-center" style="border-radius: 8px;">
                <div class="mr-3 text-info"><i class="fas fa-info-circle fa-2x"></i></div>
                <div>
                    <strong>নির্দেশনা:</strong> সাক্ষাৎকার টোকেন প্রিন্ট করার জন্য অনুগ্রহ করে শিক্ষাবর্ষ ও শ্রেণি নির্বাচন করে "সার্চ করুন" বাটনে ক্লিক করুন।
                </div>
            </div>

            <!-- Student List Section (Hidden by Default) -->
            <div id="studentListContainer" class="mt-4" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold text-secondary mb-0"><i class="fas fa-users mr-1"></i> শিক্ষার্থীর তালিকা (<span id="studentCount">0</span>)</h6>
                    <button type="button" class="btn btn-success px-4" id="btnBulkPrint" style="border-radius: 30px;">
                        <i class="fas fa-print mr-1"></i> নির্বাচিত সকলের টোকেন একসাথে প্রিন্ট
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle mb-0" style="border-radius: 8px; overflow: hidden;">
                        <thead class="thead-light">
                            <tr>
                                <th width="50" style="vertical-align: middle;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="checkAll">
                                        <label class="custom-control-label" for="checkAll"></label>
                                    </div>
                                </th>
                                <th width="100" style="vertical-align: middle;">রোল নম্বর</th>
                                <th width="150" style="vertical-align: middle;">শিক্ষার্থী আইডি</th>
                                <th style="text-align: left; vertical-align: middle; padding-left: 20px;">শিক্ষার্থীর নাম</th>
                                <th width="150" style="vertical-align: middle;">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interview Date Modal -->
<div class="modal fade" id="interviewDateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 10px;">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-calendar-alt mr-2"></i>সাক্ষাৎকারের তারিখ নির্বাচন করুন</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">সাক্ষাৎকারের তারিখ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="interviewDateInput" required>
                    <small class="text-muted">নির্বাচিত তারিখটি সকল টোকেনে বসে যাবে। সাক্ষাৎকারের সময় খালি থাকবে, হাতে লিখে দেওয়া হবে।</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                <button type="button" class="btn btn-success" id="btnConfirmPrint"><i class="fas fa-print mr-1"></i> প্রিন্ট করুন</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const academicYearSel = document.getElementById('academicYear');
    const classSel = document.getElementById('classSelect');
    const sectionSel = document.getElementById('sectionSelect');
    const btnFilter = document.getElementById('btnFilter');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const instructionAlert = document.getElementById('instructionAlert');
    const studentListContainer = document.getElementById('studentListContainer');
    const studentTableBody = document.getElementById('studentTableBody');
    const studentCount = document.getElementById('studentCount');
    const checkAll = document.getElementById('checkAll');
    const btnBulkPrint = document.getElementById('btnBulkPrint');
    const interviewDateModal = $('#interviewDateModal');
    const interviewDateInput = document.getElementById('interviewDateInput');
    const btnConfirmPrint = document.getElementById('btnConfirmPrint');
    let pendingStudentIds = '';

    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const loadStudentsUrl = @json(route('principal.institute.documents.interview_token.load-students', $school));
    const printUrlTemplate = @json(route('principal.institute.documents.interview_token.print', $school));

    // Handle Class Selection Change - Load Sections
    classSel.addEventListener('change', function() {
        const classId = this.value;
        sectionSel.innerHTML = '<option value="">-- সকল শাখা --</option>';
        if (classId) {
            fetch(`${sectionsUrl}?class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(sec => {
                        const opt = document.createElement('option');
                        opt.value = sec.id;
                        opt.textContent = sec.name;
                        sectionSel.appendChild(opt);
                    });
                })
                .catch(err => console.error('Failed to load sections:', err));
        }
    });

    // Handle Load Students
    function fetchStudents() {
        const yearId = academicYearSel.value;
        const classId = classSel.value;
        const sectionId = sectionSel.value;

        if (!yearId || !classId) {
            alert('অনুগ্রহ করে শিক্ষাবর্ষ ও শ্রেণি নির্বাচন করুন।');
            return;
        }

        studentTableBody.innerHTML = '';
        studentListContainer.style.display = 'none';
        instructionAlert.style.display = 'none';
        loadingIndicator.style.display = 'block';
        checkAll.checked = false;

        const url = `${loadStudentsUrl}?academic_year_id=${yearId}&class_id=${classId}&section_id=${sectionId}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                if (data.students && data.students.length > 0) {
                    studentCount.textContent = data.students.length;

                    data.students.forEach(student => {
                        const tr = document.createElement('tr');

                        tr.innerHTML = `
                            <td style="vertical-align: middle;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input student-checkbox" id="check-${student.id}" value="${student.id}">
                                    <label class="custom-control-label" for="check-${student.id}"></label>
                                </div>
                            </td>
                            <td style="vertical-align: middle;" class="font-weight-bold">${toBanglaNum(student.roll_no)}</td>
                            <td style="vertical-align: middle;">${student.student_id}</td>
                            <td style="text-align: left; vertical-align: middle; padding-left: 20px;">
                                <span class="font-weight-bold text-dark">${student.name}</span>
                            </td>
                            <td style="vertical-align: middle;">
                                <button type="button" class="btn btn-sm btn-primary px-3 btn-print-single" data-id="${student.id}">
                                    <i class="fas fa-print mr-1"></i> প্রিন্ট
                                </button>
                            </td>
                        `;
                        studentTableBody.appendChild(tr);
                    });

                    studentListContainer.style.display = 'block';

                    document.querySelectorAll('.btn-print-single').forEach(btn => {
                        btn.addEventListener('click', function() {
                            pendingStudentIds = this.getAttribute('data-id');
                            interviewDateInput.value = '';
                            interviewDateModal.modal('show');
                        });
                    });
                } else {
                    studentListContainer.style.display = 'none';
                    instructionAlert.style.display = 'block';
                    instructionAlert.className = 'alert alert-warning border-0 shadow-sm mt-4 d-flex align-items-center';
                    instructionAlert.innerHTML = `
                        <div class="mr-3 text-warning"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                        <div>
                            এই শ্রেণির জন্য কোনো সক্রিয় শিক্ষার্থী পাওয়া যায়নি।
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                loadingIndicator.style.display = 'none';
                instructionAlert.style.display = 'block';
                instructionAlert.className = 'alert alert-danger border-0 shadow-sm mt-4 d-flex align-items-center';
                instructionAlert.innerHTML = `
                    <div class="mr-3 text-danger"><i class="fas fa-times-circle fa-2x"></i></div>
                    <div>
                        সার্ভার থেকে শিক্ষার্থীর তথ্য লোড করতে ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।
                    </div>
                `;
            });
    }

    btnFilter.addEventListener('click', fetchStudents);

    if (academicYearSel.value && classSel.value) {
        fetchStudents();
    }

    checkAll.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.student-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
    });

    btnBulkPrint.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
        if (checkedBoxes.length === 0) {
            alert('অনুগ্রহ করে অন্তত একজন শিক্ষার্থী নির্বাচন করুন।');
            return;
        }

        pendingStudentIds = Array.from(checkedBoxes).map(cb => cb.value).join(',');
        interviewDateInput.value = '';
        interviewDateModal.modal('show');
    });

    btnConfirmPrint.addEventListener('click', function() {
        if (!interviewDateInput.value) {
            alert('অনুগ্রহ করে সাক্ষাৎকারের তারিখ নির্বাচন করুন।');
            return;
        }
        if (!pendingStudentIds) {
            return;
        }

        const yearId = academicYearSel.value;
        const classId = classSel.value;

        const printUrl = `${printUrlTemplate}?academic_year_id=${yearId}&class_id=${classId}&student_ids=${pendingStudentIds}&interview_date=${interviewDateInput.value}`;
        window.open(printUrl, '_blank');
        interviewDateModal.modal('hide');
        pendingStudentIds = '';
    });

    function toBanglaNum(num) {
        if (num === null || num === undefined) return '';
        const numStr = String(num);
        const eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return numStr.split('').map(char => {
            const index = eng.indexOf(char);
            return index !== -1 ? bn[index] : char;
        }).join('');
    }
});
</script>
@endpush
