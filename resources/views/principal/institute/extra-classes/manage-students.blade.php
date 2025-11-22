@extends('layouts.admin')

@section('title', 'Manage Students - ' . $extraClass->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Manage Students</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.extra-classes.index', $school) }}">Extra Classes</a></li>
                    <li class="breadcrumb-item active">Manage Students</li>
                </ol>
            </div>
        </div>
    </div>
</div>

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
                                        <th>Student Name</th>
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
                                            <td>
                                                <input type="checkbox" 
                                                       class="student-checkbox" 
                                                       name="selected_students[]" 
                                                       value="{{ $student->id }}"
                                                       {{ $isEnrolled ? 'checked' : '' }}>
                                            </td>
                                            <td>{{ $student->currentEnrollment->roll_no ?? 'N/A' }}</td>
                                            <td>{{ $student->name }}</td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    {{ $student->currentEnrollment->section->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <input type="hidden" name="enrollments[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
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
$(document).ready(function() {
    // Check all checkbox functionality
    $('#checkAll').on('change', function() {
        $('.student-checkbox').prop('checked', this.checked);
        toggleSectionSelects();
    });

    // Individual checkbox change
    $('.student-checkbox').on('change', function() {
        toggleSectionSelects();
        updateCheckAllState();
    });

    // Select all button
    $('#selectAll').on('click', function() {
        $('.student-checkbox').prop('checked', true);
        toggleSectionSelects();
        updateCheckAllState();
    });

    // Deselect all button
    $('#deselectAll').on('click', function() {
        $('.student-checkbox').prop('checked', false);
        toggleSectionSelects();
        updateCheckAllState();
    });

    function toggleSectionSelects() {
        $('.student-checkbox').each(function() {
            const studentId = $(this).val();
            const sectionSelect = $(`.section-select[data-student-id="${studentId}"]`);
            
            if ($(this).is(':checked')) {
                sectionSelect.prop('disabled', false);
                if (!sectionSelect.val()) {
                    sectionSelect.find('option:eq(1)').prop('selected', true);
                }
            } else {
                sectionSelect.prop('disabled', true);
            }
        });
    }

    function updateCheckAllState() {
        const total = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#checkAll').prop('checked', total === checked && total > 0);
    }

    // Form validation
    $('form').on('submit', function(e) {
        let valid = true;
        let errorMsg = '';

        $('.student-checkbox:checked').each(function() {
            const studentId = $(this).val();
            const sectionSelect = $(`.section-select[data-student-id="${studentId}"]`);
            
            if (!sectionSelect.val()) {
                valid = false;
                sectionSelect.addClass('is-invalid');
                errorMsg = 'Please assign a section for all selected students.';
            } else {
                sectionSelect.removeClass('is-invalid');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(errorMsg);
            return false;
        }
    });

    // Initialize on page load
    toggleSectionSelects();
    updateCheckAllState();
});
</script>
@endpush
@endsection
