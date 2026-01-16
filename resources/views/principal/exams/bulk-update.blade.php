@extends('layouts.admin')

@section('title', 'Bulk Update Exam')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Bulk Update: {{ $exam->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.show', [$school, $exam]) }}">বিস্তারিত</a></li>
                    <li class="breadcrumb-item active">Bulk Update</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য ও বিষয়সমূহ আপডেট করুন</h3>
            </div>
            <form action="{{ route('principal.institute.exams.bulk-update.store', [$school, $exam]) }}" method="POST" id="bulkUpdateForm">
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

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">পরীক্ষার নাম <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $exam->name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="exam_type">পরীক্ষার ধরন</label>
                                <select name="exam_type" id="exam_type" class="form-control">
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option value="Half Yearly" {{ old('exam_type', $exam->exam_type) == 'Half Yearly' ? 'selected' : '' }}>Half Yearly</option>
                                    <option value="Final" {{ old('exam_type', $exam->exam_type) == 'Final' ? 'selected' : '' }}>Final</option>
                                    <option value="Monthly" {{ old('exam_type', $exam->exam_type) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_subjects_without_fourth">মোট বিষয় (৪র্থ বাদে)</label>
                                <input type="number" name="total_subjects_without_fourth" id="total_subjects_without_fourth" class="form-control" value="{{ old('total_subjects_without_fourth', $exam->total_subjects_without_fourth) }}" min="1" placeholder="e.g., 6">
                                <small class="text-muted">GPA হিসাবের জন্য</small>
                            </div>
                        </div>
                    </div>

                    @if($exam->examSubjects->count() > 0)
                        <h5 class="mb-3">বিষয়ভিত্তিক নম্বর আপডেট করুন</h5>
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
                                        <th>শিক্ষক</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exam->examSubjects->sortBy('display_order') as $index => $examSubject)
                                        <tr>
                                            <td>
                                                {{ $examSubject->subject->name ?? 'N/A' }}
                                                <input type="hidden" name="exam_subject_id[]" value="{{ $examSubject->id }}">
                                                <input type="hidden" name="subject_id[]" value="{{ $examSubject->subject_id }}">
                                            </td>
                                            <td>
                                                <input type="date" name="exam_date[]" class="form-control" value="{{ $examSubject->exam_date ? $examSubject->exam_date->format('Y-m-d') : '' }}">
                                            </td>
                                            <td>
                                                <input type="date" name="mark_entry_deadline[]" class="form-control" value="{{ $examSubject->mark_entry_deadline ? $examSubject->mark_entry_deadline->format('Y-m-d') : '' }}">
                                            </td>
                                            <td>
                                                <input type="time" name="exam_time[]" class="form-control" value="{{ $examSubject->exam_start_time ? \Carbon\Carbon::parse($examSubject->exam_start_time)->format('H:i') : '' }}">
                                            </td>
                                            <td>
                                                <input type="number" name="creative_marks[]" class="form-control creative_marks" min="0" value="{{ $examSubject->creative_full_mark }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="objective_marks[]" class="form-control objective_marks" min="0" value="{{ $examSubject->mcq_full_mark }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="practical_marks[]" class="form-control practical_marks" min="0" value="{{ $examSubject->practical_full_mark }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control total_marks" value="{{ $examSubject->total_full_mark }}" readonly>
                                            </td>
                                            <td>
                                                <input type="number" name="creative_pass[]" class="form-control" min="0" value="{{ $examSubject->creative_pass_mark }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="objective_pass[]" class="form-control" min="0" value="{{ $examSubject->mcq_pass_mark }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="practical_pass[]" class="form-control" min="0" value="{{ $examSubject->practical_pass_mark }}" required>
                                            </td>
                                            <td>
                                                <select name="pass_type[]" class="form-control">
                                                    <option value="total" {{ $examSubject->pass_type == 'combined' ? 'selected' : '' }}>Total Marks</option>
                                                    <option value="individual" {{ $examSubject->pass_type == 'each' ? 'selected' : '' }}>Individual</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="teacher_id[]" class="form-control teacher-select">
                                                    <option value="">-- নির্বাচন করুন --</option>
                                                    @foreach($teachers as $teacher)
                                                        <option value="{{ $teacher->user_id }}" {{ $examSubject->teacher_id == $teacher->user_id ? 'selected' : '' }}>
                                                            {{ $teacher->full_name }}
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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> এই পরীক্ষায় এখনো কোনো বিষয় যুক্ত করা হয়নি।
                        </div>
                    @endif
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
$(document).ready(function() {
    // Update totals when marks change
    $(document).on('input', '.creative_marks, .objective_marks, .practical_marks', function() {
        const $row = $(this).closest('tr');
        const c = parseInt($row.find('.creative_marks').val()) || 0;
        const o = parseInt($row.find('.objective_marks').val()) || 0;
        const p = parseInt($row.find('.practical_marks').val()) || 0;
        $row.find('.total_marks').val(c + o + p);
    });

    // Initialize totals on page load
    $('.creative_marks, .objective_marks, .practical_marks').each(function() {
        $(this).trigger('input');
    });

    let isSubmitting = false;
    $('#bulkUpdateForm').on('submit', function(e) {
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
});
</script>
@endpush
@endsection
