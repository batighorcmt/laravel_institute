@extends('layouts.admin')

@section('title', 'New Homework')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">Add New Homework</h1>
    <a href="{{ route('teacher.institute.homework.index', $school) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-book"></i> Homework Information
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('teacher.institute.homework.store', $school) }}" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select from Class Routine <span class="text-danger">*</span></label>
                        <select name="routine_entry" id="routine_entry" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($routineEntries as $entry)
                                <option value="{{ $entry->id }}" 
                                    data-class="{{ $entry->class_id }}"
                                    data-section="{{ $entry->section_id }}"
                                    data-subject="{{ $entry->subject_id }}"
                                    {{ $routineEntry && $routineEntry->id == $entry->id ? 'selected' : '' }}>
                                    {{ $entry->schoolClass->name }} - {{ $entry->section->name }} - {{ $entry->subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Homework Date <span class="text-danger">*</span></label>
                        <input type="date" name="homework_date" class="form-control" value="{{ old('homework_date', \Carbon\Carbon::today()->format('Y-m-d')) }}" required readonly style="background-color: #e9ecef;">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Submission Date</label>
                        <input type="date" name="submission_date" class="form-control" value="{{ old('submission_date') }}" min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <input type="hidden" name="class_id" id="class_id" value="{{ $routineEntry->class_id ?? old('class_id') }}">
            <input type="hidden" name="section_id" id="section_id" value="{{ $routineEntry->section_id ?? old('section_id') }}">
            <input type="hidden" name="subject_id" id="subject_id" value="{{ $routineEntry->subject_id ?? old('subject_id') }}">

            <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g., Math Book p.56 Exercise 3.1" required>
            </div>

            <div class="form-group">
                <label>Description <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="5" placeholder="Write homework details..." required>{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Attach File</label>
                <input type="file" name="attachment" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <small class="form-text text-muted">Max 5MB (PDF, DOC, DOCX, JPG, PNG)</small>
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.card-header.bg-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}
</style>

<script>
document.getElementById('routine_entry').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('class_id').value = selected.dataset.class || '';
    document.getElementById('section_id').value = selected.dataset.section || '';
    document.getElementById('subject_id').value = selected.dataset.subject || '';
});
</script>
@endsection
