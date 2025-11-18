@extends('layouts.admin')
@section('title','Take Attendance - ' . $schoolClass->name . ' ' . $section->name)
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">উপস্থিতি নিন - {{ $schoolClass->name }} {{ $section->name }}</h1>
  <a href="{{ route('principal.institute.attendance.class.index', $school) }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i> ফিরে যান
  </a>
</div>

@if($enrollments->count() > 0)
    @if($isExistingRecord)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> এই তারিখের উপস্থিতি ইতিমধ্যে রেকর্ড করা হয়েছে। আপনি এখন এটি আপডেট করতে পারেন।
        </div>
    @endif

    <!-- Mark/Update Attendance Form -->
    <form method="POST" action="{{ route('principal.institute.attendance.class.store', $school) }}" id="attendanceForm">
        @csrf
        <input type="hidden" name="class_id" value="{{ $schoolClass->id }}">
        <input type="hidden" name="section_id" value="{{ $section->id }}">

        <!-- Top Submit Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                {{ $isExistingRecord ? 'উপস্থিতি আপডেট করুন' : 'উপস্থিতি রেকর্ড করুন' }}
                <small class="text-muted">({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})</small>
            </h4>
            <button type="submit" name="mark_attendance" class="btn btn-success">
                <i class="fas fa-save"></i> {{ $isExistingRecord ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped attendance-table">
                        <thead>
                            <tr>
                                <th width="60">রোল</th>
                                <th>শিক্ষার্থীর নাম</th>
                                <!-- Attendance Header Buttons -->
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-attendance-header" data-status="present" id="select-all-present">
                                        <i class="fas fa-check-circle"></i><br>Present
                                    </button>
                                </th>
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-attendance-header" data-status="absent" id="select-all-absent">
                                        <i class="fas fa-times-circle"></i><br>Absent
                                    </button>
                                </th>
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-attendance-header" data-status="late" id="select-all-late">
                                        <i class="fas fa-clock"></i><br>Late
                                    </button>
                                </th>
                                <th width="200">মন্তব্য</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->roll_no }}</td>
                                    <td class="student-name">{{ $enrollment->student->student_name_en }}</td>

                                    <!-- Present Radio -->
                                    <td class="radio-present">
                                        <input type="radio" name="attendance[{{ $enrollment->student_id }}][status]" id="present_{{ $enrollment->student_id }}" value="present" {{ (isset($existingAttendance[$enrollment->student_id]) && $existingAttendance[$enrollment->student_id] == 'present') ? 'checked' : '' }}>
                                        <label for="present_{{ $enrollment->student_id }}" class="radio-label">
                                            <i class="fas fa-check-circle"></i>
                                        </label>
                                    </td>

                                    <!-- Absent Radio -->
                                    <td class="radio-absent">
                                        <input type="radio" name="attendance[{{ $enrollment->student_id }}][status]" id="absent_{{ $enrollment->student_id }}" value="absent" {{ (isset($existingAttendance[$enrollment->student_id]) && $existingAttendance[$enrollment->student_id] == 'absent') ? 'checked' : '' }}>
                                        <label for="absent_{{ $enrollment->student_id }}" class="radio-label">
                                            <i class="fas fa-times-circle"></i>
                                        </label>
                                    </td>

                                    <!-- Late Radio -->
                                    <td class="radio-late">
                                        <input type="radio" name="attendance[{{ $enrollment->student_id }}][status]" id="late_{{ $enrollment->student_id }}" value="late" {{ (isset($existingAttendance[$enrollment->student_id]) && $existingAttendance[$enrollment->student_id] == 'late') ? 'checked' : '' }}>
                                        <label for="late_{{ $enrollment->student_id }}" class="radio-label">
                                            <i class="fas fa-clock"></i>
                                        </label>
                                    </td>

                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="attendance[{{ $enrollment->student_id }}][remarks]" value="{{ $remarks[$enrollment->student_id] ?? '' }}" placeholder="মন্তব্য">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bottom Submit Button -->
        <div class="sticky-submit text-right mt-2">
            <button type="submit" name="mark_attendance" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> {{ $isExistingRecord ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}
            </button>
        </div>
    </form>
@else
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> এই ক্লাস এবং শাখায় কোনো শিক্ষার্থী নেই।
    </div>
@endif

<style>
.attendance-card {
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: none;
}
.attendance-card .card-header {
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
    font-weight: 600;
    border-radius: 10px 10px 0 0 !important;
}
.attendance-table th {
    background-color: #f8f9fc;
    color: #4e73df;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 10px 5px;
}
.attendance-table td {
    text-align: center;
    vertical-align: middle;
    padding: 8px 5px;
}
.radio-cell {
    width: 80px;
    text-align: center;
}
.radio-label {
    display: block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    margin: 0 auto;
    font-size: 18px;
    background-color: #e9ecef;
    color: #6c757d;
    border: 2px solid #6c757d;
}

.radio-present input[type="radio"]:checked + .radio-label {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}
.radio-absent input[type="radio"]:checked + .radio-label {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}
.radio-late input[type="radio"]:checked + .radio-label {
    background-color: #ffc107;
    color: white;
    border-color: #ffc107;
}
input[type="radio"] {
    display: none;
}
.sticky-submit {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 5px 10px;
    border-top: 1px solid #eee;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 100;
}
.btn-sm-compact {
    padding: 0.2rem 0.4rem;
    font-size: 0.8rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}
.student-name {
    text-align: left;
    padding-left: 15px !important;
}
.btn-attendance-header {
    width: 100%;
    font-size: 1rem;
    font-weight: bold;
    color: #adb5bd;
    background-color: #e9ecef;
    border: 1px solid #ced4da;
    transition: all 0.3s;
    padding: 10px 0;
}
.btn-attendance-header.active-present {
    background-color: #28a745;
    color: white;
}
.btn-attendance-header.active-absent {
    background-color: #dc3545;
    color: white;
}
.btn-attendance-header.active-late {
    background-color: #ffc107;
    color: white;
}
.required-field::after {
    content: " *";
    color: red;
}
/* Row highlight (visual status differentiation) */
tbody tr.att-row-present { background-color:#e8f7ee; }
tbody tr.att-row-absent { background-color:#fde8eb; }
tbody tr.att-row-late { background-color:#fff6e0; }
</style>

<script>
(function(){
// Prefer jQuery when available; else use the vanilla fallback below
if (typeof window.jQuery !== 'undefined') {
    window.jQuery(function ($) {
    // Deterministic list of student IDs (used for bulletproof bulk selection by element IDs)
    const studentIds = @json($enrollments->pluck('student_id')->values());
    // Remember last bulk status for fallback on submit
    let lastBulkStatus = null;

    // Apply row highlight classes based on selected radio
    function updateRowStyles() {
        $('table.attendance-table tbody tr').each(function(){
            const $tr = $(this);
            // remove previous
            $tr.removeClass('att-row-present att-row-absent att-row-late');
            const presentChecked = $tr.find('input[type="radio"][value="present"]').is(':checked');
            const absentChecked  = $tr.find('input[type="radio"][value="absent"]').is(':checked');
            const lateChecked    = $tr.find('input[type="radio"][value="late"]').is(':checked');
            if (presentChecked) { $tr.addClass('att-row-present'); }
            else if (absentChecked) { $tr.addClass('att-row-absent'); }
            else if (lateChecked) { $tr.addClass('att-row-late'); }
        });
    }
    // Update header button state (highlights) based on table selection summary
    function updateHeaderButtons() {
        const totalStudents = $('tbody tr').length;
        const presentCount = $('input[type="radio"][value="present"]:checked').length;
        const absentCount  = $('input[type="radio"][value="absent"]:checked').length;
        const lateCount    = $('input[type="radio"][value="late"]:checked').length;

        $('.btn-attendance-header').removeClass('active-present active-absent active-late');
        if (totalStudents > 0) {
            if (presentCount === totalStudents) {
                $('#select-all-present').addClass('active-present');
            } else if (absentCount === totalStudents) {
                $('#select-all-absent').addClass('active-absent');
            } else if (lateCount === totalStudents) {
                $('#select-all-late').addClass('active-late');
            }
        }
    }

    // Bulletproof "select all" by addressing inputs via their unique IDs
    $('.btn-attendance-header').on('click', function () {
        const statusToSelect = $(this).data('status'); // present | absent | late
        lastBulkStatus = statusToSelect;
        let missing = [];
        studentIds.forEach(function(id){
            const inputId = statusToSelect + '_' + id; // e.g., present_123
            const $el = $('#' + inputId);
            if ($el && $el.length) {
                $el.prop('checked', true).trigger('change');
            } else {
                missing.push(inputId);
            }
        });
        if (missing.length) { console.warn('Missing radios for:', missing); }
        updateHeaderButtons();
        updateRowStyles();
    });

    // When any radio changes, refresh header highlights
    $(document).on('change', 'input[name^="attendance["][type="radio"]', function () {
        updateHeaderButtons();
        updateRowStyles();
    });

    // Initial state
    updateHeaderButtons();
    updateRowStyles();

    // Validate that every student has one status selected before submit
    $('#attendanceForm').on('submit', function (e) {
        let allOk = true;
        $('tbody tr').each(function () {
            const hasChecked = $(this).find('input[type="radio"]').is(':checked');
            if (!hasChecked) {
                // Fallback: if a bulk status was chosen, auto-apply it here
                if (lastBulkStatus) {
                    const $target = $(this).find('input[type="radio"][name^="attendance["][value="' + lastBulkStatus + '"]');
                    if ($target.length) {
                        $target.prop('checked', true);
                    }
                }
            } else {
                $(this).removeClass('table-danger');
            }
        });
        // Re-check after fallback
        $('tbody tr').each(function () {
            const hasChecked = $(this).find('input[type="radio"]').is(':checked');
            if (!hasChecked) {
                allOk = false;
                $(this).addClass('table-danger');
            } else {
                $(this).removeClass('table-danger');
            }
        });
        updateRowStyles();
        if (!allOk) {
            e.preventDefault();
            alert('সকল শিক্ষার্থীর জন্য উপস্থিতি নির্বাচন বাধ্যতামূলক।');
        }
    });

    // Prevent Enter key from submitting while typing remarks
    $('#attendanceForm').on('keyup keypress', function (e) {
        const keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });
    });
} else {
    // Vanilla JS fallback if jQuery is not available (extra safety on slow/cached assets)
    (function(){
        const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
        let lastBulkStatus = null;
        const studentIds = @json($enrollments->pluck('student_id')->values());
        const updateHeaderButtonsPlain = () => {
            const rows = $$('#attendanceForm tbody tr');
            const total = rows.length;
        const present = $$('input[type="radio"][value="present"]:checked').length;
        const absent  = $$('input[type="radio"][value="absent"]:checked').length;
        const late    = $$('input[type="radio"][value="late"]:checked').length;
            $$('.btn-attendance-header').forEach(btn => btn.classList.remove('active-present','active-absent','active-late'));
            if (total > 0) {
                if (present === total) document.getElementById('select-all-present')?.classList.add('active-present');
                else if (absent === total) document.getElementById('select-all-absent')?.classList.add('active-absent');
                else if (late === total) document.getElementById('select-all-late')?.classList.add('active-late');
            }
        };
        function updateRowStylesPlain() {
            $$('#attendanceForm tbody tr').forEach(tr => {
                tr.classList.remove('att-row-present','att-row-absent','att-row-late');
                const present = tr.querySelector('input[type="radio"][value="present"]:checked');
                const absent  = tr.querySelector('input[type="radio"][value="absent"]:checked');
                const late    = tr.querySelector('input[type="radio"][value="late"]:checked');
                if (present) tr.classList.add('att-row-present');
                else if (absent) tr.classList.add('att-row-absent');
                else if (late) tr.classList.add('att-row-late');
            });
        }

        $$('.btn-attendance-header').forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.dataset.status;
                lastBulkStatus = status;
                const missing = [];
                studentIds.forEach(id => {
                    const inputId = status + '_' + id;
                    const el = document.getElementById(inputId);
                    if (el) {
                        el.checked = true;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        missing.push(inputId);
                    }
                });
                if (missing.length) { console.warn('Missing radios for:', missing); }
                updateHeaderButtonsPlain();
                updateRowStylesPlain();
            });
        });

        // Form validation to ensure every row has a checked radio
        const form = document.getElementById('attendanceForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                let allOk = true;
                $$('#attendanceForm tbody tr').forEach(tr => {
                    let anyChecked = $$('input[type="radio"]:checked', tr).length > 0;
                    if (!anyChecked && lastBulkStatus) {
                        const target = $$('input[type="radio"][name^="attendance["][value="' + lastBulkStatus + '"]', tr);
                        if (target.length) {
                            target[0].checked = true;
                            anyChecked = true;
                        }
                    }
                    if (!anyChecked) { allOk = false; tr.classList.add('table-danger'); }
                    else { tr.classList.remove('table-danger'); }
                });
                updateRowStylesPlain();
                if (!allOk) {
                    e.preventDefault();
                    alert('সকল শিক্ষার্থীর জন্য উপস্থিতি নির্বাচন বাধ্যতামূলক।');
                }
            });

            // Prevent Enter key from submitting while typing remarks
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        }

        // Initialize header state
        updateHeaderButtonsPlain();
        updateRowStylesPlain();
    })();
}
})();
</script>
@endsection