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
                                <select class="form-control @error('academic_year_id') is-invalid @enderror" 
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
                                <select class="form-control @error('class_id') is-invalid @enderror" 
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
                                <select class="form-control @error('section_id') is-invalid @enderror" 
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
                                <select class="form-control @error('subject_id') is-invalid @enderror" 
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
                                <select class="form-control @error('teacher_id') is-invalid @enderror" 
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
                                <select class="form-control @error('status') is-invalid @enderror" 
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

@push('scripts')
<script>
(function(){
    function initDependentSections(){
        const classSelect = document.getElementById('class_id');
        const sectionSelect = document.getElementById('section_id');
        if (!classSelect || !sectionSelect) return;

        const metaUrl = "{{ route('principal.institute.meta.sections', $school) }}";
        const oldSection = "{{ old('section_id') }}";

        function clearSections(){
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
        }

        async function loadSectionsFor(classId, preselect){
            if (!classId){ clearSections(); return; }
            try{
                const res = await fetch(metaUrl + '?class_id=' + encodeURIComponent(classId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                clearSections();
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (preselect && String(preselect) === String(s.id)) opt.selected = true;
                    sectionSelect.appendChild(opt);
                });
            }catch(e){
                clearSections();
            }
        }

        classSelect.addEventListener('change', function(){
            loadSectionsFor(this.value, null);
        });

        // Initial population if a class is already selected
        const initialClass = classSelect.value;
        if (initialClass){
            loadSectionsFor(initialClass, oldSection);
        } else {
            clearSections();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDependentSections);
    } else {
        initDependentSections();
    }
})();
</script>
@endpush
