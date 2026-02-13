@extends('layouts.admin')

@section('title', 'নতুন পরীক্ষা তৈরি করুন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">নতুন পরীক্ষা তৈরি করুন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">নতুন পরীক্ষা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য</h3>
            </div>
            <form action="{{ route('principal.institute.exams.store', $school) }}" method="POST" id="examForm">
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
                                <label for="academic_year_id">শিক্ষাবর্ষ <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
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
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">পরীক্ষার নাম (ইংরেজি) <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name_bn">পরীক্ষার নাম (বাংলা)</label>
                                <input type="text" name="name_bn" id="name_bn" class="form-control @error('name_bn') is-invalid @enderror" value="{{ old('name_bn') }}">
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
                                    <option value="Half Yearly" {{ old('exam_type') == 'Half Yearly' ? 'selected' : '' }}>Half Yearly</option>
                                    <option value="Final" {{ old('exam_type') == 'Final' ? 'selected' : '' }}>Final</option>
                                    <option value="Monthly" {{ old('exam_type') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
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
                                <input type="number" name="total_subjects_without_fourth" id="total_subjects_without_fourth" class="form-control @error('total_subjects_without_fourth') is-invalid @enderror" value="{{ old('total_subjects_without_fourth') }}" min="1" placeholder="e.g., 6">
                                <small class="text-muted">GPA হিসাবের জন্য ব্যবহার করা হবে (৪র্থ/ঐচ্ছিক বিষয় বাদে)</small>
                                @error('total_subjects_without_fourth')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="subjectsContainer" style="display:none;">
                        <h5 class="mt-4 mb-2">বিষয়ভিত্তিক নম্বর নির্ধারণ করুন</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>বিষয়</th>
                                        <th style="min-width:110px;">পরীক্ষার তারিখ</th>
                                        <th style="min-width:130px;">নম্বর Entry শেষ তারিখ</th>
                                        <th style="min-width:90px;">পরীক্ষার সময়</th>
                                        <th>সৃজনশীল</th>
                                        <th>MCQ</th>
                                        <th>ব্যবহারিক</th>
                                        <th>মোট</th>
                                        <th>সৃজনশীল পাস</th>
                                        <th>MCQ পাস</th>
                                        <th>ব্যবহারিক পাস</th>
                                        <th>পাস টাইপ</th>
                                        <th>Combine Group</th>
                                        <th>শিক্ষক</th>
                                    </tr>
                                </thead>
                                <tbody id="subjectsTableBody">
                                    <!-- Will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">শুরুর তারিখ</label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                                @error('start_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">শেষের তারিখ</label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                                @error('end_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">অবস্থা <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>খসড়া</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>সম্পন্ন</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>বাতিল</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">বিবরণ</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> সংরক্ষণ করুন
                    </button>
                    <a href="{{ route('principal.institute.exams.index', $school) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    const TEACHERS = @json($teachers->map(fn($t) => ['id' => $t->user_id, 'name' => $t->full_name]));
    const teacherOptions = TEACHERS.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
    const classSelect = $('#class_id');
    const subjectsContainer = $('#subjectsContainer');
    const subjectsTableBody = $('#subjectsTableBody');
    const submitBtn = $('#submitBtn');

    classSelect.on('change', function() {
        const classId = $(this).val();
        if (!classId) {
            subjectsContainer.hide();
            submitBtn.hide();
            return;
        }

        $.get('{{ route("principal.institute.exams.fetch-subjects", $school) }}', { class_id: classId })
            .done(function(data) {
                if (data.length === 0) {
                    subjectsTableBody.html('<tr><td colspan="13" class="text-center text-danger">এই শ্রেণির জন্য কোনো বিষয় পাওয়া যায়নি।</td></tr>');
                    subjectsContainer.show();
                    submitBtn.hide();
                    return;
                }

                let rows = '';
                data.forEach(function(subject) {
                    const hasC = Number(subject.has_creative) === 1;
                    const hasO = Number(subject.has_objective) === 1;
                    const hasP = Number(subject.has_practical) === 1;
                    const passType = 'total';

                    rows += `
                        <tr>
                            <td>
                                ${subject.subject_name}
                                <input type="hidden" name="subject_id[]" value="${subject.id}">
                            </td>
                            <td><input type="date" name="exam_date[]" class="form-control"></td>
                            <td><input type="date" name="mark_entry_deadline[]" class="form-control"></td>
                            <td><input type="time" name="exam_time[]" class="form-control"></td>
                            <td>
                                <input type="number" name="creative_marks[]" class="form-control creative_marks" min="0" value="0" ${hasC ? '' : 'disabled'} required>
                                ${hasC ? '' : '<input type="hidden" name="creative_marks[]" value="0">'}
                            </td>
                            <td>
                                <input type="number" name="objective_marks[]" class="form-control objective_marks" min="0" value="0" ${hasO ? '' : 'disabled'} required>
                                ${hasO ? '' : '<input type="hidden" name="objective_marks[]" value="0">'}
                            </td>
                            <td>
                                <input type="number" name="practical_marks[]" class="form-control practical_marks" min="0" value="0" ${hasP ? '' : 'disabled'} required>
                                ${hasP ? '' : '<input type="hidden" name="practical_marks[]" value="0">'}
                            </td>
                            <td><input type="number" class="form-control total_marks" value="0" readonly></td>
                            <td>
                                <input type="number" name="creative_pass[]" class="form-control" min="0" value="0" ${hasC ? '' : 'disabled'} required>
                                ${hasC ? '' : '<input type="hidden" name="creative_pass[]" value="0">'}
                            </td>
                            <td>
                                <input type="number" name="objective_pass[]" class="form-control" min="0" value="0" ${hasO ? '' : 'disabled'} required>
                                ${hasO ? '' : '<input type="hidden" name="objective_pass[]" value="0">'}
                            </td>
                            <td>
                                <input type="number" name="practical_pass[]" class="form-control" min="0" value="0" ${hasP ? '' : 'disabled'} required>
                                ${hasP ? '' : '<input type="hidden" name="practical_pass[]" value="0">'}
                            </td>
                            <td>
                                <select name="pass_type[]" class="form-control">
                                    <option value="total" ${passType === 'total' ? 'selected' : ''}>Total Marks</option>
                                    <option value="individual" ${passType === 'individual' ? 'selected' : ''}>Individual</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="combine_group[]" class="form-control" placeholder="Group">
                            </td>
                            <td>
                                <select name="teacher_id[]" class="form-control teacher-select">
                                    <option value=""></option>
                                    ${teacherOptions}
                                </select>
                            </td>
                        </tr>
                    `;
                });

                subjectsTableBody.html(rows);
                subjectsContainer.show();
                submitBtn.show();

                // Update totals when marks change
                $(document).on('input', '.creative_marks, .objective_marks, .practical_marks', updateTotals);
                updateTotals();
            })
            .fail(function() {
                subjectsTableBody.html('<tr><td colspan="13" class="text-center text-danger">বিষয় লোড করতে সমস্যা হয়েছে।</td></tr>');
                subjectsContainer.show();
                submitBtn.hide();
            });
    });

    function updateTotals() {
        subjectsTableBody.find('tr').each(function() {
            const $row = $(this);
            const c = parseInt($row.find('.creative_marks').val()) || 0;
            const o = parseInt($row.find('.objective_marks').val()) || 0;
            const p = parseInt($row.find('.practical_marks').val()) || 0;
            $row.find('.total_marks').val(c + o + p);
        });
    }

    let isSubmitting = false;
    $('#examForm').on('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        isSubmitting = true;
        submitBtn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> প্রক্রিয়াধীন...');
        
        setTimeout(function() {
            isSubmitting = false;
            submitBtn.prop('disabled', false)
                .html('<i class="fas fa-save"></i> সংরক্ষণ করুন');
        }, 5000);
    });
});
</script>
@endpush
@endsection
