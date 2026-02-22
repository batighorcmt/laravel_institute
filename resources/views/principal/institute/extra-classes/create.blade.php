@extends('layouts.admin')

@section('title', 'Create Extra Class - ' . $school->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create Extra Class</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.extra-classes.index', $school) }}">Extra Classes</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Extra Class Information</h3>
            </div>
            <form action="{{ route('principal.institute.extra-classes.store', $school) }}" method="POST">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Extra Class Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g., Advanced Math Coaching"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="academic_year_id">Academic Year</label>
                                <select class="form-control select2 @error('academic_year_id') is-invalid @enderror" 
                                        id="academic_year_id" 
                                        name="academic_year_id">
                                    <option value="">Select Academic Year</option>
                                    @if($currentYear)
                                        <option value="{{ $currentYear->id }}" selected>{{ $currentYear->name }}</option>
                                    @endif
                                </select>
                                @error('academic_year_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="class_id">Class <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('class_id') is-invalid @enderror" 
                                        id="class_id" 
                                        name="class_id" 
                                        required>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="section_id">Default Section <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('section_id') is-invalid @enderror" 
                                        id="section_id" 
                                        name="section_id" 
                                        required>
                                    <option value="">Select Section</option>
                                </select>
                                @error('section_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Students can be assigned to different sections later</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subject_id">Subject</label>
                                <select class="form-control select2 @error('subject_id') is-invalid @enderror" 
                                        id="subject_id" 
                                        name="subject_id">
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="teacher_id">Teacher</label>
                                <select class="form-control select2 @error('teacher_id') is-invalid @enderror" 
                                        id="teacher_id" 
                                        name="teacher_id">
                                    <option value="">Select Teacher</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="schedule">Schedule</label>
                                <input type="text" 
                                       class="form-control @error('schedule') is-invalid @enderror" 
                                       id="schedule" 
                                       name="schedule" 
                                       value="{{ old('schedule') }}" 
                                       placeholder="e.g., Saturday & Tuesday 4:00 PM - 6:00 PM">
                                @error('schedule')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status" 
                                        required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Enter description about this extra class">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Extra Class
                    </button>
                    <a href="{{ route('principal.institute.extra-classes.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function initSelect2() {
        if ($.fn.select2) {
            $('.select2').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%'
                    });
                }
            });
            console.log('Select2 initialized');
        } else {
            console.log('Select2 not yet available');
        }
    }

    // Initial init
    initSelect2();
    // Fallback in case Select2 loads late (CDN fallback)
    setTimeout(initSelect2, 1000);
    setTimeout(initSelect2, 3000);

    $('#class_id').on('change', function() {
        var classId = $(this).val();
        console.log('Class changed to:', classId);
        
        var sectionSelect = $('#section_id');
        var subjectSelect = $('#subject_id');
        
        // Clear existing options
        sectionSelect.empty().append('<option value="">Select Section</option>');
        subjectSelect.empty().append('<option value="">Select Subject</option>');
        
        // Trigger Select2 to show updated empty state
        sectionSelect.trigger('change');
        subjectSelect.trigger('change');
        
        if (classId) {
            // Fetch Sections
            $.ajax({
                url: "{{ route('principal.institute.meta.sections', $school) }}",
                type: 'GET',
                data: { class_id: classId },
                success: function(data) {
                    console.log('Sections received:', data);
                    $.each(data, function(index, section) {
                        sectionSelect.append('<option value="' + section.id + '">' + section.name + '</option>');
                    });
                    sectionSelect.trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch sections:', error);
                    alert('Error: Failed to fetch sections. Please refresh the page.');
                }
            });

            // Fetch Subjects
            $.ajax({
                url: "{{ route('principal.institute.meta.subjects', $school) }}",
                type: 'GET',
                data: { class_id: classId },
                success: function(data) {
                    console.log('Subjects received:', data);
                    $.each(data, function(index, subject) {
                        subjectSelect.append('<option value="' + subject.id + '">' + subject.name + '</option>');
                    });
                    subjectSelect.trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch subjects:', error);
                    alert('Error: Failed to fetch subjects. Please refresh the page.');
                }
            });
        }
    });

    // Handle initial class selection (for old input or pre-selected)
    var initialClass = $('#class_id').val();
    if (initialClass && $('#section_id option').length <= 1) {
         console.log('Triggering initial class change');
         $('#class_id').trigger('change');
    }
});
</script>
@endsection
