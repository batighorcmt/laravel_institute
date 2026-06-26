@extends('layouts.admin')

@section('title', 'পরীক্ষা সম্পাদনা করুন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">পরীক্ষা সম্পাদনা করুন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">সম্পাদনা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য সম্পাদনা করুন</h3>
            </div>
            <form action="{{ route('principal.institute.exams.update', [$school, $exam]) }}" method="POST" id="examEditForm">
                @csrf
                @method('PUT')
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
                                <label for="academic_year_id">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $exam->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class_id">শ্রেণি <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $exam->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6" id="section_wrapper">
                            <div class="form-group">
                                <label for="section_ids">শাখা (ঐচ্ছিক)</label>
                                <select name="section_ids[]" id="section_ids" class="form-control select2 @error('section_ids') is-invalid @enderror" multiple="multiple" data-placeholder="-- সকল শাখা --">
                                    @php $selectedSections = old('section_ids', $exam->section_ids ?? []); @endphp
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ in_array($section->id, $selectedSections) ? 'selected' : '' }}>
                                            {{ $section->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">শাখা নির্বাচন না করলে সকল শিক্ষার্থীর জন্য পরীক্ষা হবে। একাধিক শাখা নির্বাচন করা যাবে।</small>
                                @error('section_ids')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6" id="group_wrapper" style="{{ count($groups) > 0 ? '' : 'display:none;' }}">
                            <div class="form-group">
                                <label for="group_ids">বিভাগ/ গ্রুপ (ঐচ্ছিক)</label>
                                <select name="group_ids[]" id="group_ids" class="form-control select2 @error('group_ids') is-invalid @enderror" multiple="multiple" data-placeholder="-- সকল গ্রুপ --">
                                    @php $selectedGroups = old('group_ids', $exam->group_ids ?? []); @endphp
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ in_array($group->id, $selectedGroups) ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">গ্রুপ নির্বাচন না করলে সকল শিক্ষার্থীর জন্য পরীক্ষা হবে। একাধিক গ্রুপ নির্বাচন করা যাবে।</small>
                                @error('group_ids')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">পরীক্ষার নাম (ইংরেজি) <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $exam->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name_bn">পরীক্ষার নাম (বাংলা)</label>
                                <input type="text" name="name_bn" id="name_bn" class="form-control @error('name_bn') is-invalid @enderror" value="{{ old('name_bn', $exam->name_bn) }}">
                                @error('name_bn')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="exam_type">পরীক্ষার ধরন</label>
                                <select name="exam_type" id="exam_type" class="form-control @error('exam_type') is-invalid @enderror">
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option value="Half Yearly" {{ old('exam_type', $exam->exam_type) == 'Half Yearly' ? 'selected' : '' }}>Half Yearly</option>
                                    <option value="Final" {{ old('exam_type', $exam->exam_type) == 'Final' ? 'selected' : '' }}>Final</option>
                                    <option value="Monthly" {{ old('exam_type', $exam->exam_type) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                                @error('exam_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_subjects_without_fourth">মোট বিষয় (৪র্থ বাদে)</label>
                                <input type="number" name="total_subjects_without_fourth" id="total_subjects_without_fourth" class="form-control @error('total_subjects_without_fourth') is-invalid @enderror" value="{{ old('total_subjects_without_fourth', $exam->total_subjects_without_fourth) }}" min="1" placeholder="e.g., 6">
                                <small class="text-muted">GPA হিসাবের জন্য ব্যবহার করা হবে (৪র্থ/ঐচ্ছিক বিষয় বাদে)</small>
                                @error('total_subjects_without_fourth')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="public_exam_id">পাবলিক পরীক্ষা ফরমেট</label>
                                <select name="public_exam_id" id="public_exam_id" class="form-control @error('public_exam_id') is-invalid @enderror">
                                    <option value="">-- সাধারণ পরীক্ষা --</option>
                                    @foreach($publicExams as $publicExam)
                                        <option value="{{ $publicExam->id }}" {{ old('public_exam_id', $exam->public_exam_id) == $publicExam->id ? 'selected' : '' }}>
                                            {{ $publicExam->short_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">পাবলিক পরীক্ষার শর্ট নামের তালিকা</small>
                                @error('public_exam_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">শুরুর তারিখ</label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $exam->start_date ? $exam->start_date->format('Y-m-d') : '') }}">
                                @error('start_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">শেষের তারিখ</label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $exam->end_date ? $exam->end_date->format('Y-m-d') : '') }}">
                                @error('end_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">অবস্থা <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', $exam->status) == 'draft' ? 'selected' : '' }}>খসড়া</option>
                                    <option value="active" {{ old('status', $exam->status) == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                                    <option value="completed" {{ old('status', $exam->status) == 'completed' ? 'selected' : '' }}>সম্পন্ন</option>
                                    <option value="cancelled" {{ old('status', $exam->status) == 'cancelled' ? 'selected' : '' }}>বাতিল</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">বিবরণ</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $exam->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="fas fa-save"></i> আপডেট করুন
                    </button>
                    <a href="{{ route('principal.institute.exams.show', [$school, $exam]) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function waitForJQuery(fn) {
    if (typeof window.$ !== 'undefined' && window.$.fn && window.$.fn.select2) { fn(); }
    else { setTimeout(function(){ waitForJQuery(fn); }, 30); }
})(function() {
$(document).ready(function() {
    let isSubmitting = false;
    
    $('#examEditForm').on('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        isSubmitting = true;
        $('#updateBtn').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> প্রক্রিয়াধীন...');
        
        setTimeout(function() {
            isSubmitting = false;
            $('#updateBtn').prop('disabled', false)
                .html('<i class="fas fa-save"></i> আপডেট করুন');
        }, 5000);
    });

    const oldSectionIds = @json(old('section_ids', $exam->section_ids ?? []));
    const oldGroupIds = @json(old('group_ids', $exam->group_ids ?? []));

    // Helper: safely destroy and init select2
    function initSelect2(selector, placeholder) {
        try { $(selector).select2('destroy'); } catch(e) {}
        $(selector).select2({ width: '100%', allowClear: true, placeholder: placeholder });
    }
    function populateSelect2(selector, items, placeholder, selectedVals = []) {
        try { $(selector).select2('destroy'); } catch(e) {}
        var opts = '';
        const stringSelectedVals = selectedVals.map(String);
        $.each(items, function(i, item) { 
            const isSelected = stringSelectedVals.includes(String(item.id)) ? 'selected' : '';
            opts += '<option value="' + item.id + '" ' + isSelected + '>' + item.name + '</option>'; 
        });
        $(selector).html(opts);
        try { $(selector).select2({ width: '100%', allowClear: true, placeholder: placeholder }); } catch(e) {}
    }
    initSelect2('#section_ids', '-- সকল শাখা --');
    initSelect2('#group_ids',   '-- সকল গ্রুপ --');

    $('#class_id').on('change', function() {
        const classId = $(this).val();
        if (!classId) {
            populateSelect2('#section_ids', [], '-- সকল শাখা --', []);
            populateSelect2('#group_ids',   [], '-- সকল গ্রুপ --', []);
            $('#group_wrapper').hide();
            return;
        }

        $.get('/principal/institute/{{ $school->id }}/exams/fetch-subjects', { class_id: classId })
            .done(function(response) {
                const sections  = response.sections || [];
                const groupsRaw = response.groups || [];
                const groups    = Array.isArray(groupsRaw) ? groupsRaw : Object.values(groupsRaw);

                populateSelect2('#section_ids', sections, '-- সকল শাখা --', oldSectionIds);

                if (groups.length > 0) {
                    populateSelect2('#group_ids', groups, '-- সকল গ্রুপ --', oldGroupIds);
                    $('#group_wrapper').show();
                } else {
                    populateSelect2('#group_ids', [], '-- সকল গ্রুপ --', []);
                    $('#group_wrapper').hide();
                }
            });
    });

    // Trigger on load to ensure correct sections/groups are loaded 
    // especially if old('class_id') differs from $exam->class_id
    if ($('#class_id').val()) {
        $('#class_id').trigger('change');
    }
});
}); // end waitForJQuery
</script>
@endpush
@endsection
