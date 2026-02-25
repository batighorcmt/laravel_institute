@extends('layouts.admin')

@section('title', 'Mark Entry')

@section('content')
@php
    $u = Auth::user();
@endphp
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-pen mr-2"></i>নম্বর এন্ট্রি</h3>
            </div>
            <div class="card-body">
                <!-- Filters Section -->
                <form id="markEntryFilters" class="mb-4">
                    <div class="row">
                        <!-- Academic Year Filter -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="academicYear">একাডেমিক ইয়ার <span class="text-danger">*</span></label>
                                <select id="academicYear" name="academic_year_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Class Filter -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="className">শ্রেণী <span class="text-danger">*</span></label>
                                <select id="className" name="class_id" class="form-control" required>
                                    <option value="">-- একাডেমিক ইয়ার নির্বাচন করুন --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Exam Status Filter -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="examStatus">পরীক্ষার অবস্থা <span class="text-danger">*</span></label>
                                <select id="examStatus" name="status" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option value="active">সক্রিয়</option>
                                    <option value="completed">সম্পন্ন</option>
                                </select>
                            </div>
                        </div>

                        <!-- Exam Name Filter -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="examName">পরীক্ষার নাম <span class="text-danger">*</span></label>
                                <select id="examName" name="exam_id" class="form-control" required>
                                    <option value="">-- শ্রেণী এবং অবস্থা নির্বাচন করুন --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Subject Filter -->
                        <div class="col-md-3" id="subjectFilterCol">
                            <div class="form-group">
                                <label for="subjectName">বিষয় <span class="text-danger">*</span></label>
                                <select id="subjectName" name="subject_id" class="form-control">
                                    <option value="">-- পরীক্ষা নির্বাচন করুন --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="button" id="loadMarksBtn" class="btn btn-primary" disabled>
                                <i class="fas fa-search mr-2"></i>নম্বর এন্ট্রি লোড করুন
                            </button>
                            <div id="loadingIndicator" class="ml-3 d-none">
                                <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                <span>লোড হচ্ছে...</span>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Marks Entry Container (loaded via AJAX) -->
                <div id="marksEntryContainer" style="display: none;"></div>

                <!-- Default Alert -->
                <div id="noDataAlert" class="alert alert-secondary">
                    <i class="fas fa-arrow-right mr-2"></i>
                    নম্বর এন্ট্রি দেখতে উপরের ফিল্টার থেকে একাডেমিক ইয়ার, শ্রেণী, পরীক্ষার অবস্থা, পরীক্ষা এবং বিষয় নির্বাচন করুন।
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const academicYearSelect = document.getElementById('academicYear');
        const classSelect = document.getElementById('className');
        const statusSelect = document.getElementById('examStatus');
        const examSelect = document.getElementById('examName');
        const subjectSelect = document.getElementById('subjectName');
        const loadMarksBtn = document.getElementById('loadMarksBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const marksEntryContainer = document.getElementById('marksEntryContainer');
        const noDataAlert = document.getElementById('noDataAlert');
        const schoolId = {{ $school }};

        // Helper function to update button state
        function updateLoadButtonState() {
            const allSelected = academicYearSelect.value && classSelect.value && statusSelect.value && examSelect.value && subjectSelect.value;
            loadMarksBtn.disabled = !allSelected;
        }

        // Load classes...
        academicYearSelect.addEventListener('change', function() {
            if (this.value) {
                fetch(`/teacher/institute/${schoolId}/exams/get-classes?academic_year_id=${this.value}`)
                    .then(res => res.json())
                    .then(data => {
                        classSelect.innerHTML = '<option value="">-- শ্রেণী নির্বাচন করুন --</option>';
                        data.forEach(cls => {
                            classSelect.innerHTML += `<option value="${cls.id}">${cls.name}</option>`;
                        });
                        updateLoadButtonState();
                    });
            }
        });

        // Load exams...
        [classSelect, statusSelect].forEach(select => {
            select.addEventListener('change', function() {
                if (academicYearSelect.value && classSelect.value && statusSelect.value) {
                    fetch(`/teacher/institute/${schoolId}/exams/get-by-status?academic_year_id=${academicYearSelect.value}&class_id=${classSelect.value}&status=${statusSelect.value}`)
                        .then(res => res.json())
                        .then(data => {
                            examSelect.innerHTML = '<option value="">-- পরীক্ষা নির্বাচন করুন --</option>';
                            if (Array.isArray(data)) {
                                data.forEach(exam => {
                                    examSelect.innerHTML += `<option value="${exam.id}">${exam.name}</option>`;
                                });
                            }
                            updateLoadButtonState();
                        });
                }
            });
        });

        // Load subjects...
        examSelect.addEventListener('change', function() {
            if (this.value) {
                fetch(`/teacher/institute/${schoolId}/exams/get-subjects?exam_id=${this.value}&class_id=${classSelect.value}`)
                    .then(res => res.json())
                    .then(data => {
                        subjectSelect.innerHTML = '<option value="">-- বিষয় নির্বাচন করুন --</option>';
                        data.forEach(subject => {
                            subjectSelect.innerHTML += `<option value="${subject.id}">${subject.name}</option>`;
                        });
                        updateLoadButtonState();
                    });
            }
        });

        subjectSelect.addEventListener('change', updateLoadButtonState);

        // Load marks form...
        loadMarksBtn.addEventListener('click', function() {
            loadingIndicator.classList.remove('d-none');
            const params = new URLSearchParams({
                exam_id: examSelect.value,
                subject_id: subjectSelect.value,
                class_id: classSelect.value
            });

            fetch(`/teacher/institute/${schoolId}/exams/load-marks-form?${params.toString()}`)
                .then(res => res.text())
                .then(html => {
                    marksEntryContainer.innerHTML = html;
                    marksEntryContainer.style.display = 'block';
                    noDataAlert.style.display = 'none';
                })
                .finally(() => {
                    loadingIndicator.classList.add('d-none');
                });
        });

        // jQuery-based logic for delegation
        $(function() {
            const saveMarkUrl = "{{ route('teacher.institute.exams.save-mark', $school) }}";

            function recalcTotal(row) {
                let total = 0;
                row.find('.mark-input').each(function() {
                    const val = parseFloat($(this).val()) || 0;
                    total += val;
                });
                row.find('.total-marks').text(total.toFixed(2));
            }

            function saveSingleMark(studentId, callback) {
                const row = $(`tr[data-student-id="${studentId}"]`);
                if (!row.length) return;

                const statusCell = row.find('.save-status');
                statusCell.html('<i class="fas fa-spinner fa-spin text-info"></i>');

                const data = {
                    _token: "{{ csrf_token() }}",
                    exam_id: $('#examName').val(),
                    exam_subject_id: row.attr('data-exam-subject-id') || row.closest('table').parent().find('input[name="exam_subject_id"]').val(),
                    student_id: studentId,
                    creative_marks: row.find('[data-field="creative_marks"]').val(),
                    mcq_marks: row.find('[data-field="mcq_marks"]').val(),
                    practical_marks: row.find('[data-field="practical_marks"]').val(),
                    is_absent: row.find('.absent-checkbox').is(':checked') ? 1 : 0
                };

                $.post(saveMarkUrl, data)
                    .done(function(res) {
                        if (res.success) {
                            statusCell.html('<span class="badge badge-success"><i class="fas fa-check"></i></span>');
                            row.find('.total-marks').text(res.total_marks);
                        } else {
                            statusCell.html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
                            if (window.toastr) toastr.error(res.message);
                        }
                        if (callback) callback();
                    })
                    .fail(function(xhr) {
                        statusCell.html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
                        const msg = xhr.responseJSON ? xhr.responseJSON.message : "Error saving mark";
                        if (window.toastr) toastr.error(msg);
                        if (callback) callback();
                    });
            }

            // Event Delegation
            $(document).on('input', '.mark-input', function() {
                const input = $(this);
                const max = parseFloat(input.attr('max'));
                const val = parseFloat(input.val());
                const row = input.closest('tr');

                if (!isNaN(max) && val > max) {
                    if (window.toastr) toastr.warning('নম্বর সর্বোচ্চ ' + max + ' হতে পারে।');
                    input.val(max);
                }
                recalcTotal(row);
            });

            $(document).on('change', '.mark-input', function() {
                const studentId = $(this).data('student-id');
                saveSingleMark(studentId);
            });

            $(document).on('change', '.absent-checkbox', function() {
                const studentId = $(this).data('student-id');
                const row = $(this).closest('tr');
                const isAbsent = $(this).is(':checked');
                
                if (isAbsent) {
                    row.find('.mark-input').each(function() {
                        $(this).val('').prop('disabled', true);
                    });
                    row.find('.total-marks').text('0.00');
                } else {
                    row.find('.mark-input').prop('disabled', false);
                    recalcTotal(row);
                }
                saveSingleMark(studentId);
            });

            $(document).on('click', '#saveAllMarksBtn', function() {
                const btn = $(this);
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>সংরক্ষণ হচ্ছে...');
                
                let completed = 0;
                const rows = $('#marksEntryContainer tbody tr[data-student-id]');
                const total = rows.length;

                if (total === 0) {
                    btn.prop('disabled', false).html(originalHtml);
                    return;
                }

                rows.each(function() {
                    const sid = $(this).data('student-id');
                    saveSingleMark(sid, function() {
                        completed++;
                        if (completed === total) {
                            btn.prop('disabled', false).html(originalHtml);
                            if (window.toastr) toastr.success("সকল শিক্ষার্থীর নম্বর সফলভাবে সংরক্ষিত হয়েছে।");
                        }
                    });
                });
            });
        });

        // Error display helper
        function showError(message) {
            noDataAlert.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i><strong>ত্রুটি:</strong> ${message}`;
            noDataAlert.classList.remove('alert-secondary');
            noDataAlert.classList.add('alert-danger');
            noDataAlert.style.display = 'block';
            marksEntryContainer.style.display = 'none';
        }
    });

    // Make functions global if needed, but here they are used within delegation so it's fine.
</script>
