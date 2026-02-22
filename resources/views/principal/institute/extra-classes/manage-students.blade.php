@extends('layouts.admin')

@section('title', 'Manage Students - ' . $extraClass->name)

@section('content')
<!-- Header and breadcrumb removed per request -->

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $extraClass->name }}</h3>
                <p class="mb-0 text-muted">
                    <small>
                        Class: {{ $extraClass->schoolClass->name ?? 'N/A' }} | 
                        Default Section: {{ $extraClass->section->name ?? 'N/A' }} |
                        Subject: {{ $extraClass->subject->name ?? 'N/A' }}
                    </small>
                </p>
            </div>

            <form action="{{ route('principal.institute.extra-classes.students.store', [$school, $extraClass]) }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($students->count() > 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Instructions:</strong> Select students and assign them to sections for this extra class. 
                            Students can be in different sections than their regular class section.
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-primary" id="selectAll">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="deselectAll">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="checkAll">
                                        </th>
                                        <th>Roll</th>
                                        <th>Student Name (EN)</th>
                                        <th>Group</th>
                                        <th>Regular Section</th>
                                        <th>Assign to Section <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        @php
                                            $enrollment = $enrollments->get($student->id);
                                            $isEnrolled = $enrollment !== null;
                                        @endphp
                                        <tr class="student-row">
                                            @php
                                                $groupName = $student->currentEnrollment->group->name ?? 'N/A';
                                                $badgeClass = match(trim($groupName)) {
                                                    'Science', 'বিজ্ঞান' => 'badge-info',
                                                    'Humanities', 'Humanity', 'মানবিক' => 'badge-success',
                                                    'Business Studies', 'Business', 'Commerce', 'ব্যবসায় শিক্ষা' => 'badge-warning',
                                                    'Vocational', 'ভোকেশনাল' => 'badge-danger',
                                                    default => 'badge-secondary',
                                                };
                                            @endphp
                                            <td>
                                                <input type="checkbox" 
                                                       class="student-checkbox" 
                                                       name="selected_students[]" 
                                                       value="{{ $student->id }}"
                                                       {{ $isEnrolled ? 'checked' : '' }}>
                                            </td>
                                            <td>{{ $student->currentEnrollment->roll_no ?? 'N/A' }}</td>
                                            <td>{{ $student->student_name_en ?? $student->full_name ?? $student->name }}</td>
                                            <td>
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ $groupName }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    {{ $student->currentEnrollment->section->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                      <input type="hidden" 
                                                          class="enrollment-student-id" 
                                                          name="enrollments[{{ $loop->index }}][student_id]" 
                                                          value="{{ $student->id }}"
                                                          {{ !$isEnrolled ? 'disabled' : '' }}>
                                                <select class="form-control form-control-sm section-select" 
                                                        name="enrollments[{{ $loop->index }}][assigned_section_id]"
                                                        data-student-id="{{ $student->id }}"
                                                        {{ !$isEnrolled ? 'disabled' : '' }}>
                                                    <option value="">Select Section</option>
                                                    @foreach($sections as $section)
                                                        <option value="{{ $section->id }}" 
                                                            {{ $isEnrolled && $enrollment->assigned_section_id == $section->id ? 'selected' : '' }}>
                                                            {{ $section->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            No students found in {{ $extraClass->schoolClass->name ?? 'this class' }}. 
                            Please enroll students first.
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    @if($students->count() > 0)
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Enrollments
                        </button>
                    @endif
                    <a href="{{ route('principal.institute.extra-classes.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Extra Classes
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function(){
function initManageStudents(){
    const checkAll = document.getElementById('checkAll');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');

    const studentCheckboxes = () => Array.from(document.querySelectorAll('.student-checkbox'));
    const sectionSelectForStudent = (id) => document.querySelector(`.section-select[data-student-id="${id}"]`);
    const hiddenIdForStudent = (id) => {
        const row = document.querySelector(`.section-select[data-student-id="${id}"]`);
        if (!row) return null;
        // hidden is in same row; select with closest tr then query
        const tr = row.closest('tr');
        return tr ? tr.querySelector('.enrollment-student-id') : null;
    };

    function toggleSectionSelects() {
        studentCheckboxes().forEach(cb => {
            const studentId = cb.value;
            const sectionSelect = sectionSelectForStudent(studentId);
            if (!sectionSelect) return;
            if (cb.checked) {
                sectionSelect.disabled = false;
                const hidden = hiddenIdForStudent(studentId);
                if (hidden) hidden.disabled = false;
            } else {
                sectionSelect.disabled = true;
                // Clear selection to keep intent explicit
                sectionSelect.value = '';
                const hidden = hiddenIdForStudent(studentId);
                if (hidden) hidden.disabled = true;
            }
        });
    }

    function updateCheckAllState() {
        const total = studentCheckboxes().length;
        const checked = studentCheckboxes().filter(cb => cb.checked).length;
        if (checkAll) checkAll.checked = total > 0 && total === checked;
    }

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            const checked = checkAll.checked;
            studentCheckboxes().forEach(cb => { cb.checked = checked; });
            toggleSectionSelects();
        });
    }

    studentCheckboxes().forEach(cb => {
        cb.addEventListener('change', () => {
            toggleSectionSelects();
            updateCheckAllState();
        });
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            studentCheckboxes().forEach(cb => { cb.checked = true; });
            toggleSectionSelects();
            updateCheckAllState();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            studentCheckboxes().forEach(cb => { cb.checked = false; });
            toggleSectionSelects();
            updateCheckAllState();
        });
    }

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            let valid = true;
            let errorMsg = '';

            const selected = studentCheckboxes().filter(cb => cb.checked);
            selected.forEach(cb => {
                const sectionSelect = sectionSelectForStudent(cb.value);
                if (!sectionSelect || !sectionSelect.value) {
                    valid = false;
                    if (sectionSelect) sectionSelect.classList.add('is-invalid');
                    errorMsg = 'Please assign a section for all selected students.';
                } else {
                    sectionSelect.classList.remove('is-invalid');
                }
            });

            // Allow saving with zero selected rows (clears all enrollments)
            if (selected.length === 0) {
                valid = true;
            }

            if (!valid) {
                e.preventDefault();
                alert(errorMsg);
                return false;
            }
        });
    }

    // Initialize on page load
    toggleSectionSelects();
    updateCheckAllState();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initManageStudents);
} else {
    initManageStudents();
}
})();
</script>
@endpush
@endsection
