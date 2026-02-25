<!-- Status Messages and Deadline -->
<div class="row mb-3">
    <div class="col-12">
        @if($isCompleted)
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>সতর্কতা:</strong> এই পরীক্ষাটি সম্পন্ন হয়েছে। আপনি শুধুমাত্র নম্বর দেখতে পারবেন।
            </div>
        @elseif($isOverdue)
            <div class="alert alert-danger mb-0">
                <i class="fas fa-times-circle mr-2"></i>
                <strong>নম্বর এন্ট্রি বন্ধ:</strong> এই পরীক্ষার সময়সীমা শেষ হয়েছে ({{ $examSubject->mark_entry_deadline ? $examSubject->mark_entry_deadline->format('d M Y, h:i A') : 'N/A' }})।
            </div>
        @else
            <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                <span>
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>সক্রিয়:</strong> আপনি নম্বর এন্ট্রি করতে পারবেন।
                </span>
                @if($examSubject->mark_entry_deadline)
                    <span class="badge badge-light p-2 text-dark">
                        <i class="fas fa-clock mr-1"></i>
                        শেষ সময়: {{ $examSubject->mark_entry_deadline->format('d M Y, h:i A') }}
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header p-2">
        <h3 class="card-title text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            {{ $exam->name }} | {{ $subject->name }} | পূর্ণমান: {{ $examSubject->total_full_mark }}
        </h3>
        <div class="card-tools">
            @if($examSubject->creative_full_mark > 0) <span class="badge badge-light border">সৃজনশীল: {{ $examSubject->creative_full_mark }}</span> @endif
            @if($examSubject->mcq_full_mark > 0) <span class="badge badge-light border">MCQ: {{ $examSubject->mcq_full_mark }}</span> @endif
            @if($examSubject->practical_full_mark > 0) <span class="badge badge-light border">ব্যবহারিক: {{ $examSubject->practical_full_mark }}</span> @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div id="form-message-container"></div>
        <div class="table-responsive">
            <input type="hidden" name="exam_subject_id" value="{{ $examSubject->id }}">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead class="bg-light">
                    <tr>
                        <th width="5%" class="text-center">ক্রমিক</th>
                        <th width="10%" class="text-center">শাখা</th>
                        <th width="8%" class="text-center">রোল</th>
                        <th width="25%">শিক্ষার্থীর নাম</th>
                        @if($examSubject->creative_full_mark > 0) <th width="12%" class="text-center">সৃজনশীল ({{ $examSubject->creative_full_mark }})</th> @endif
                        @if($examSubject->mcq_full_mark > 0) <th width="12%" class="text-center">MCQ ({{ $examSubject->mcq_full_mark }})</th> @endif
                        @if($examSubject->practical_full_mark > 0) <th width="12%" class="text-center">ব্যবহারিক ({{ $examSubject->practical_full_mark }})</th> @endif
                        <th width="10%" class="text-center">মোট ({{ $examSubject->total_full_mark }})</th>
                        <th width="8%" class="text-center">অনুপস্থিত</th>
                        <th width="5%" class="text-center">অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        @php $mark = $marks->get($enrollment->student->id); @endphp
                        <tr data-student-id="{{ $enrollment->student->id }}" data-exam-subject-id="{{ $examSubject->id }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $enrollment->section->name ?? '-' }}</td>
                            <td class="text-center font-weight-bold">{{ $enrollment->roll_no }}</td>
                            <td>{{ $enrollment->student->student_name_bn ?: $enrollment->student->student_name_en }}</td>
                            
                            @if($examSubject->creative_full_mark > 0)
                                <td>
                                    <input type="number" class="form-control form-control-sm mark-input text-center" 
                                           data-field="creative_marks" data-student-id="{{ $enrollment->student->id }}" 
                                           value="{{ $mark->creative_marks ?? '' }}" min="0" max="{{ $examSubject->creative_full_mark }}" 
                                           step="0.01" {{ (!$canEnter || ($mark && $mark->is_absent)) ? 'disabled' : '' }}>
                                </td>
                            @endif

                            @if($examSubject->mcq_full_mark > 0)
                                <td>
                                    <input type="number" class="form-control form-control-sm mark-input text-center" 
                                           data-field="mcq_marks" data-student-id="{{ $enrollment->student->id }}" 
                                           value="{{ $mark->mcq_marks ?? '' }}" min="0" max="{{ $examSubject->mcq_full_mark }}" 
                                           step="0.01" {{ (!$canEnter || ($mark && $mark->is_absent)) ? 'disabled' : '' }}>
                                </td>
                            @endif

                            @if($examSubject->practical_full_mark > 0)
                                <td>
                                    <input type="number" class="form-control form-control-sm mark-input text-center" 
                                           data-field="practical_marks" data-student-id="{{ $enrollment->student->id }}" 
                                           value="{{ $mark->practical_marks ?? '' }}" min="0" max="{{ $examSubject->practical_full_mark }}" 
                                           step="0.01" {{ (!$canEnter || ($mark && $mark->is_absent)) ? 'disabled' : '' }}>
                                </td>
                            @endif

                            <td class="text-center font-weight-bold total-marks">{{ $mark->total_marks ?? '0.00' }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="absent-checkbox" data-student-id="{{ $enrollment->student->id }}" 
                                       {{ $mark && $mark->is_absent ? 'checked' : '' }} {{ !$canEnter ? 'disabled' : '' }}>
                            </td>
                            <td class="text-center save-status">
                                @if($mark)
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-minus"></i></span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100%" class="text-center p-3 text-muted">এই শ্রেণীর জন্য কোন শিক্ষার্থী পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($canEnter && $enrollments->count() > 0)
    <div class="card-footer p-2 text-right">
        <button type="button" class="btn btn-primary btn-sm" id="saveAllMarksBtn">
            <i class="fas fa-save mr-1"></i> সকল নম্বর সংরক্ষণ করুন
        </button>
    </div>
    @endif
</div>
