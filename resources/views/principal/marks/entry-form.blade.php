@extends('layouts.admin')

@section('title', 'নম্বর Entry - ' . $examSubject->subject->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">নম্বর Entry: {{ $examSubject->subject->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.marks.index', $school) }}">নম্বর Entry</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}">{{ $exam->name }}</a></li>
                    <li class="breadcrumb-item active">{{ $examSubject->subject->name }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Subject Info Card -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    {{ $exam->name }} | {{ $examSubject->subject->name }} | {{ $exam->class->name }}
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light">সৃজনশীল: {{ $examSubject->creative_full_mark }}</span>
                    <span class="badge badge-light">MCQ: {{ $examSubject->mcq_full_mark }}</span>
                    <span class="badge badge-light">ব্যবহারিক: {{ $examSubject->practical_full_mark }}</span>
                    <span class="badge badge-warning">মোট: {{ $examSubject->total_full_mark }}</span>
                </div>
            </div>
        </div>

        <!-- Mark Entry Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">শিক্ষার্থীদের নম্বর Entry করুন</h3>
            </div>
            <div class="card-body">
                <div id="message-container"></div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">ক্রমিক</th>
                                <th width="10%">রোল</th>
                                <th width="20%">শিক্ষার্থীর নাম</th>
                                @if($examSubject->creative_full_mark > 0)
                                    <th width="12%">সৃজনশীল ({{ $examSubject->creative_full_mark }})</th>
                                @endif
                                @if($examSubject->mcq_full_mark > 0)
                                    <th width="12%">MCQ ({{ $examSubject->mcq_full_mark }})</th>
                                @endif
                                @if($examSubject->practical_full_mark > 0)
                                    <th width="12%">ব্যবহারিক ({{ $examSubject->practical_full_mark }})</th>
                                @endif
                                <th width="10%">মোট</th>
                                <th width="8%">গ্রেড</th>
                                <th width="8%">অনুপস্থিত</th>
                                <th width="8%">অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $mark = $marks->get($student->id);
                                @endphp
                                <tr data-student-id="{{ $student->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->student_id }}</td>
                                    <td>{{ $student->student_name_en }}</td>
                                    
                                    @if($examSubject->creative_full_mark > 0)
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm mark-input" 
                                                   data-field="creative_marks"
                                                   data-student-id="{{ $student->id }}"
                                                   value="{{ $mark->creative_marks ?? '' }}" 
                                                   min="0" 
                                                   max="{{ $examSubject->creative_full_mark }}"
                                                   step="0.01"
                                                   {{ $mark && $mark->is_absent ? 'disabled' : '' }}>
                                        </td>
                                    @endif

                                    @if($examSubject->mcq_full_mark > 0)
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm mark-input" 
                                                   data-field="mcq_marks"
                                                   data-student-id="{{ $student->id }}"
                                                   value="{{ $mark->mcq_marks ?? '' }}" 
                                                   min="0" 
                                                   max="{{ $examSubject->mcq_full_mark }}"
                                                   step="0.01"
                                                   {{ $mark && $mark->is_absent ? 'disabled' : '' }}>
                                        </td>
                                    @endif

                                    @if($examSubject->practical_full_mark > 0)
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm mark-input" 
                                                   data-field="practical_marks"
                                                   data-student-id="{{ $student->id }}"
                                                   value="{{ $mark->practical_marks ?? '' }}" 
                                                   min="0" 
                                                   max="{{ $examSubject->practical_full_mark }}"
                                                   step="0.01"
                                                   {{ $mark && $mark->is_absent ? 'disabled' : '' }}>
                                        </td>
                                    @endif

                                    <td class="total-marks">{{ $mark->total_marks ?? '-' }}</td>
                                    <td class="grade">{{ $mark->letter_grade ?? '-' }}</td>
                                    <td>
                                        <input type="checkbox" 
                                               class="absent-checkbox" 
                                               data-student-id="{{ $student->id }}"
                                               {{ $mark && $mark->is_absent ? 'checked' : '' }}>
                                    </td>
                                    <td class="save-status">
                                        @if($mark)
                                            <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-minus"></i></span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-success" id="saveAllBtn">
                        <i class="fas fa-save"></i> সকল নম্বর সংরক্ষণ করুন
                    </button>
                    <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> ফিরে যান
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    const saveUrl = "{{ route('principal.institute.marks.save', [$school, $exam, $examSubject]) }}";

    // Auto-save on input change
    $('.mark-input').on('change', function() {
        const studentId = $(this).data('student-id');
        saveMark(studentId);
    });

    // Handle absent checkbox
    $('.absent-checkbox').on('change', function() {
        const studentId = $(this).data('student-id');
        const isAbsent = $(this).is(':checked');
        const row = $(this).closest('tr');

        if (isAbsent) {
            row.find('.mark-input').prop('disabled', true).val('');
        } else {
            row.find('.mark-input').prop('disabled', false);
        }

        saveMark(studentId);
    });

    // Save All button
    $('#saveAllBtn').on('click', function() {
        let saveCount = 0;
        const totalStudents = $('tbody tr').length;

        $('tbody tr').each(function() {
            const studentId = $(this).data('student-id');
            saveMark(studentId, function() {
                saveCount++;
                if (saveCount === totalStudents) {
                    showMessage('success', 'সকল শিক্ষার্থীর নম্বর সফলভাবে সংরক্ষণ করা হয়েছে!');
                }
            });
        });
    });

    function saveMark(studentId, callback) {
        const row = $('tr[data-student-id="' + studentId + '"]');
        const isAbsent = row.find('.absent-checkbox').is(':checked');

        const data = {
            _token: '{{ csrf_token() }}',
            student_id: studentId,
            is_absent: isAbsent ? 1 : 0
        };

        if (!isAbsent) {
            row.find('.mark-input').each(function() {
                const field = $(this).data('field');
                const value = $(this).val();
                if (value !== '') {
                    data[field] = value;
                }
            });
        }

        $.ajax({
            url: saveUrl,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    row.find('.save-status').html('<span class="badge badge-success"><i class="fas fa-check"></i></span>');
                    
                    // Update total and grade if provided
                    if (response.total_marks !== undefined) {
                        row.find('.total-marks').text(response.total_marks);
                    }
                    if (response.letter_grade) {
                        row.find('.grade').text(response.letter_grade);
                    }

                    if (callback) callback();
                }
            },
            error: function(xhr) {
                row.find('.save-status').html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
                showMessage('danger', 'নম্বর সংরক্ষণে সমস্যা হয়েছে: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }

    function showMessage(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#message-container').html(alertHtml);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    }
});
</script>
@endpush
@endsection
