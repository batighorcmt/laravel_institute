@extends('layouts.admin')

@section('title','Edit Section')

@section('content')

<div class="row mb-2">
    <div class="col">
        <h1 class="m-0">Edit Section</h1>
    </div>
    <div class="col text-right">
        <a href="{{ route('principal.institute.sections.index',$school) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> List
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('principal.institute.sections.update',[$school,$section]) }}">
            @csrf
            @method('put')

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Class *</label>
                    <select name="class_id" class="form-control" required>
                        @foreach($classList as $cls)
                            <option value="{{ $cls->id }}"
                                {{ old('class_id',$section->class_id)==$cls->id?'selected':'' }}>
                                {{ $cls->name }} ({{ $cls->numeric_value }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Section Name *</label>
                    <input type="text" name="name" class="form-control"
                           required value="{{ old('name',$section->name) }}">
                </div>

                <div class="form-group col-md-4">
                    <label>Class Teacher (optional)</label>

                    {{-- IMPORTANT: options are rendered server-side --}}
                    <select name="class_teacher_id" class="form-control" id="class_teacher_id"
                            data-initial="{{ old('class_teacher_id', $section->class_teacher_id) }}">
                        <option value="">-- নির্বাচন করুন --</option>
                        @foreach($activeTeachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $section->class_teacher_id == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->user->name ?? ('Teacher #'.$teacher->id) }}@if(!empty($teacher->initials)) ({{ $teacher->initials }})@endif
                            </option>
                        @endforeach
                    </select>

                    <small class="text-muted d-block mt-1">
                        A teacher can be class teacher for only one section. You may leave this blank.
                    </small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ $section->status=='active'?'selected':'' }}>Active</option>
                        <option value="inactive" {{ $section->status=='inactive'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Update
            </button>
        </form>
    </div>
</div>
    
@endsection

