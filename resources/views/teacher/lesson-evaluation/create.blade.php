@extends('layouts.admin')

@section('title', 'Lesson Evaluation')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">
        @if($lessonEvaluation)
            Update Lesson Evaluation
        @else
            Perform Lesson Evaluation
        @endif
        @if($routineEntry)
            - {{ $routineEntry->class->name }} {{ $routineEntry->section->name }}
        @endif
    </h1>
    <a href="{{ route('teacher.institute.lesson-evaluation.index', $school) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
</div>

@if($students->count() > 0)
    @if($lessonEvaluation)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You already evaluated this class. You can update below.
        </div>
    @endif

    <!-- Lesson Evaluation Form -->
    <form method="POST" action="{{ route('teacher.institute.lesson-evaluation.store', $school) }}" id="evaluationForm">
        @csrf
        <input type="hidden" name="routine_entry_id" value="{{ $routineEntry->id ?? '' }}">
        <input type="hidden" name="class_id" value="{{ $routineEntry->class_id ?? '' }}">
        <input type="hidden" name="section_id" value="{{ $routineEntry->section_id ?? '' }}">
        <input type="hidden" name="subject_id" value="{{ $routineEntry->subject_id ?? '' }}">
        <input type="hidden" name="evaluation_date" value="{{ old('evaluation_date', now()->format('Y-m-d')) }}">
        <input type="hidden" name="evaluation_time" value="{{ old('evaluation_time', now()->format('H:i')) }}">

        <!-- Class Information Card -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Class Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Class:</strong> {{ $routineEntry->class->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Section:</strong> {{ $routineEntry->section->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Subject:</strong> {{ $routineEntry->subject->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Period:</strong> {{ $routineEntry->period_number ?? 'N/A' }}
                        @if($routineEntry->start_time && $routineEntry->end_time)
                            <br><small class="text-muted">({{ \Carbon\Carbon::parse($routineEntry->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($routineEntry->end_time)->format('h:i A') }})</small>
                        @endif
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <strong>Notes:</strong>
                        <textarea name="notes" class="form-control mt-1 @error('notes') is-invalid @enderror" rows="2" 
                                  placeholder="Write lesson notes or comments...">{{ old('notes', $lessonEvaluation->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Submit Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                Students
                <small class="text-muted">({{ now()->format('d/m/Y') }})</small>
            </h4>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> {{ $lessonEvaluation ? 'Update' : 'Save' }}
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped evaluation-table">
                        <thead>
                            <tr>
                                <th width="60">Roll</th>
                                <th>Student Name</th>
                                <!-- Evaluation Header Buttons -->
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-evaluation-header" data-status="completed" id="select-all-completed">
                                        <i class="fas fa-check-circle"></i><br>Completed
                                    </button>
                                </th>
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-evaluation-header" data-status="partial" id="select-all-partial">
                                        <i class="fas fa-adjust"></i><br>Partial
                                    </button>
                                </th>
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-evaluation-header" data-status="not_done" id="select-all-not_done">
                                        <i class="fas fa-times-circle"></i><br>Not Done
                                    </button>
                                </th>
                                <th class="radio-cell">
                                    <button type="button" class="btn btn-evaluation-header" data-status="absent" id="select-all-absent">
                                        <i class="fas fa-user-slash"></i><br>Absent
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $enrollment)
                                @php
                                    $student = $enrollment->student;
                                    $existingStatus = $existingRecords->get($student->id)?->status ?? null;
                                    $oldStatus = old("statuses.{$loop->index}", $existingStatus ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $enrollment->roll_no }}</td>
                                    <td class="student-name">{{ $student->student_name_en }}</td>
                                    
                                    <input type="hidden" name="student_ids[]" value="{{ $student->id }}">

                                    <!-- Completed Radio -->
                                    <td class="radio-completed">
                                        <input type="radio" name="statuses[{{ $loop->index }}]" id="completed_{{ $student->id }}" value="completed" {{ $oldStatus === 'completed' ? 'checked' : '' }}>
                                        <label for="completed_{{ $student->id }}" class="radio-label">
                                            <i class="fas fa-check-circle"></i>
                                        </label>
                                    </td>

                                    <!-- Partial Radio -->
                                    <td class="radio-partial">
                                        <input type="radio" name="statuses[{{ $loop->index }}]" id="partial_{{ $student->id }}" value="partial" {{ $oldStatus === 'partial' ? 'checked' : '' }}>
                                        <label for="partial_{{ $student->id }}" class="radio-label">
                                            <i class="fas fa-adjust"></i>
                                        </label>
                                    </td>

                                    <!-- Not Done Radio -->
                                    <td class="radio-not_done">
                                        <input type="radio" name="statuses[{{ $loop->index }}]" id="not_done_{{ $student->id }}" value="not_done" {{ $oldStatus === 'not_done' ? 'checked' : '' }}>
                                        <label for="not_done_{{ $student->id }}" class="radio-label">
                                            <i class="fas fa-times-circle"></i>
                                        </label>
                                    </td>

                                    <!-- Absent Radio -->
                                    <td class="radio-absent">
                                        <input type="radio" name="statuses[{{ $loop->index }}]" id="absent_{{ $student->id }}" value="absent" {{ $oldStatus === 'absent' ? 'checked' : '' }}>
                                        <label for="absent_{{ $student->id }}" class="radio-label">
                                            <i class="fas fa-user-slash"></i>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bottom Submit Button -->
        <div class="sticky-submit text-right mt-2">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> {{ $lessonEvaluation ? 'Update' : 'Save' }}
            </button>
        </div>
    </form>
@else
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No students in this class/section.
    </div>
@endif

<style>
.evaluation-table th {
    background-color: #f8f9fc;
    color: #4e73df;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 10px 5px;
}
.evaluation-table td {
    text-align: center;
    vertical-align: middle;
    padding: 8px 5px;
}
.radio-cell {
    width: 100px;
    text-align: center;
}
.radio-label {
    display: block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    margin: 0 auto;
    font-size: 18px;
    background-color: #e9ecef;
    color: #6c757d;
    border: 2px solid #6c757d;
}

.radio-completed input[type="radio"]:checked + .radio-label {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}
.radio-partial input[type="radio"]:checked + .radio-label {
    background-color: #ffc107;
    color: white;
    border-color: #ffc107;
}
.radio-not_done input[type="radio"]:checked + .radio-label {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}
.radio-absent input[type="radio"]:checked + .radio-label {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}
input[type="radio"] {
    display: none;
}
.sticky-submit {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 5px 10px;
    border-top: 1px solid #eee;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 100;
}
.student-name {
    text-align: left;
    padding-left: 15px !important;
}
.btn-evaluation-header {
    width: 100%;
    font-size: 0.9rem;
    font-weight: bold;
    color: #adb5bd;
    background-color: #e9ecef;
    border: 1px solid #ced4da;
    transition: all 0.3s;
    padding: 10px 0;
}
.btn-evaluation-header.active-completed {
    background-color: #28a745;
    color: white;
}
.btn-evaluation-header.active-partial {
    background-color: #ffc107;
    color: white;
}
.btn-evaluation-header.active-not_done {
    background-color: #dc3545;
    color: white;
}
.btn-evaluation-header.active-absent {
    background-color: #6c757d;
    color: white;
}
/* Row highlight */
tbody tr.eval-row-completed { background-color:#e8f7ee; }
tbody tr.eval-row-partial { background-color:#fff6e0; }
tbody tr.eval-row-not_done { background-color:#fde8eb; }
tbody tr.eval-row-absent { background-color:#e9ecef; }
</style>

<script>
// Using Vanilla JavaScript (no jQuery dependency) - Optimized for large student lists
(function(){
    const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
    const studentIds = @json($students->pluck('student_id')->values());
    let lastBulkStatus = null;

    function updateRowStyles() {
        const rows = $$('table.evaluation-table tbody tr');
        const fragment = document.createDocumentFragment();
        
        rows.forEach(tr => {
            let newClass = '';
            if (tr.querySelector('input[type="radio"][value="completed"]:checked')) {
                newClass = 'eval-row-completed';
            } else if (tr.querySelector('input[type="radio"][value="partial"]:checked')) {
                newClass = 'eval-row-partial';
            } else if (tr.querySelector('input[type="radio"][value="not_done"]:checked')) {
                newClass = 'eval-row-not_done';
            } else if (tr.querySelector('input[type="radio"][value="absent"]:checked')) {
                newClass = 'eval-row-absent';
            }
            
            // Only update if changed
            const currentClasses = ['eval-row-completed', 'eval-row-partial', 'eval-row-not_done', 'eval-row-absent'];
            const hasClass = currentClasses.find(c => tr.classList.contains(c));
            if (hasClass !== newClass) {
                currentClasses.forEach(c => tr.classList.remove(c));
                if (newClass) tr.classList.add(newClass);
            }
        });
    }

    function updateHeaderButtons() {
        const rows = $$('tbody tr');
        const total = rows.length;
        const completedCount = $$('input[type="radio"][value="completed"]:checked').length;
        const partialCount = $$('input[type="radio"][value="partial"]:checked').length;
        const notDoneCount = $$('input[type="radio"][value="not_done"]:checked').length;
        const absentCount = $$('input[type="radio"][value="absent"]:checked').length;

        $$('.btn-evaluation-header').forEach(btn => {
            btn.classList.remove('active-completed', 'active-partial', 'active-not_done', 'active-absent');
        });

        if (total > 0) {
            if (completedCount === total) document.getElementById('select-all-completed')?.classList.add('active-completed');
            else if (partialCount === total) document.getElementById('select-all-partial')?.classList.add('active-partial');
            else if (notDoneCount === total) document.getElementById('select-all-not_done')?.classList.add('active-not_done');
            else if (absentCount === total) document.getElementById('select-all-absent')?.classList.add('active-absent');
        }
    }

    // Button click handlers (optimized for large student lists)
    $$('.btn-evaluation-header').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const status = btn.dataset.status;
            lastBulkStatus = status;
            
            // Batch update without triggering change events (much faster)
            studentIds.forEach(id => {
                const inputId = status + '_' + id;
                const el = document.getElementById(inputId);
                if (el) el.checked = true;
            });
            
            // Update UI once at the end
            updateHeaderButtons();
            updateRowStyles();
        });
    });

    // Radio change handler
    document.addEventListener('change', (e) => {
        if (e.target.type === 'radio' && e.target.name.startsWith('statuses[')) {
            updateHeaderButtons();
            updateRowStyles();
        }
    });

    // Form validation
    const form = document.getElementById('evaluationForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            let allOk = true;
            $$('tbody tr').forEach(tr => {
                let anyChecked = $$('input[type="radio"]:checked', tr).length > 0;
                if (!anyChecked && lastBulkStatus) {
                    const target = $$('input[type="radio"][name^="statuses["][value="' + lastBulkStatus + '"]', tr);
                    if (target.length) {
                        target[0].checked = true;
                        anyChecked = true;
                    }
                }
                if (!anyChecked) { 
                    allOk = false; 
                    tr.classList.add('table-danger'); 
                } else { 
                    tr.classList.remove('table-danger'); 
                }
            });
            updateRowStyles();
            if (!allOk) {
                e.preventDefault();
                alert('Selection is required for all students.');
            }
        });
    }

    // Initialize
    updateHeaderButtons();
    updateRowStyles();
})();
</script>
@endsection