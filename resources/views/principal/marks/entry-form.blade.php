@extends('layouts.admin')

@section('title', 'নম্বর Entry - ' . $examSubject->subject->name)

@section('content')
<style>
    .grade-column { display: none; }
    /* Ensure mark inputs show at least 3 digits and are touch-friendly on mobile */
    .mark-input {
        min-width: 3ch;
        width: 6rem;
        box-sizing: border-box;
    }
    .table-responsive { -webkit-overflow-scrolling: touch; }
    @media (max-width: 576px) {
        .mark-input { width: 4.2rem; padding: .25rem .35rem; }
        .table th, .table td { padding: .35rem; font-size: .95rem; }
    }
</style>
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
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">{{ $exam->name }} | {{ $examSubject->subject->name }} | {{ $exam->class->name }}</h3>
                <div class="card-tools">
                    <span class="badge badge-light">সৃজনশীল: {{ $examSubject->creative_full_mark }}</span>
                    <span class="badge badge-light">MCQ: {{ $examSubject->mcq_full_mark }}</span>
                    <span class="badge badge-light">ব্যবহারিক: {{ $examSubject->practical_full_mark }}</span>
                    <span class="badge badge-warning">মোট: {{ $examSubject->total_full_mark }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">শিক্ষার্থীদের নম্বর Entry করুন</h3></div>
            <div class="card-body">
                <div id="message-container"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">ক্রমিক</th>
                                <th width="10%">শাখা</th>
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
                                <th width="8%" class="grade-column">গ্রেড</th>
                                <th width="8%">অনুপস্থিত</th>
                                <th width="8%">অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $enrollment)
                                @php $mark = $marks->get($enrollment->student->id); @endphp
                                <tr data-student-id="{{ $enrollment->student->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                        <td>{{ $enrollment->section->name ?? '-' }}</td>
                                        <td>{{ $enrollment->roll_no ?? '-' }}</td>
                                        <td>{{ $enrollment->student->student_name_en }}</td>
                                    @if($examSubject->creative_full_mark > 0)
                                        <td><input type="number" class="form-control form-control-sm mark-input" data-field="creative_marks" data-student-id="{{ $enrollment->student->id }}" value="{{ $mark->creative_marks ?? '' }}" min="0" max="{{ $examSubject->creative_full_mark }}" step="0.01" {{ $mark && $mark->is_absent ? 'disabled' : '' }}></td>
                                    @endif
                                    @if($examSubject->mcq_full_mark > 0)
                                        <td><input type="number" class="form-control form-control-sm mark-input" data-field="mcq_marks" data-student-id="{{ $enrollment->student->id }}" value="{{ $mark->mcq_marks ?? '' }}" min="0" max="{{ $examSubject->mcq_full_mark }}" step="0.01" {{ $mark && $mark->is_absent ? 'disabled' : '' }}></td>
                                    @endif
                                    @if($examSubject->practical_full_mark > 0)
                                        <td><input type="number" class="form-control form-control-sm mark-input" data-field="practical_marks" data-student-id="{{ $enrollment->student->id }}" value="{{ $mark->practical_marks ?? '' }}" min="0" max="{{ $examSubject->practical_full_mark }}" step="0.01" {{ $mark && $mark->is_absent ? 'disabled' : '' }}></td>
                                    @endif
                                    <td class="total-marks">{{ $mark->total_marks ?? '-' }}</td>
                                    <td class="grade grade-column">{{ $mark->letter_grade ?? '-' }}</td>
                                    <td><input type="checkbox" class="absent-checkbox" data-student-id="{{ $enrollment->student->id }}" {{ $mark && $mark->is_absent ? 'checked' : '' }}></td>
                                    <td class="save-status">@if($mark)<span class="badge badge-success"><i class="fas fa-check"></i></span>@else<span class="badge badge-secondary"><i class="fas fa-minus"></i></span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-success" id="saveAllBtn"><i class="fas fa-save"></i> সকল নম্বর সংরক্ষণ করুন</button>
                    <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> ফিরে যান</a>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function waitForjQ(){
    if (!window.jQuery) return setTimeout(waitForjQ,50);
    (function($){
        $(function(){
            const saveUrl = "{{ route('principal.institute.marks.save', [$school, $exam, $examSubject]) }}";

            function recalcRow(row){
                let total=0, any=false;
                row.find('.mark-input').each(function(){ const v=$(this).val(); if(v!==''){ any=true; total+=parseFloat(v)||0; } });
                row.find('.total-marks').text(any?total.toFixed(2):'-');
            }

            $('tbody tr').each(function(){ recalcRow($(this)); });

            $('table').on('input change','.mark-input',function(){
                const input=$(this), maxAttr=input.attr('max'), max=(typeof maxAttr!=='undefined'&&maxAttr!=='')?parseFloat(maxAttr):NaN, raw=input.val(), value=raw===''? '': parseFloat(raw);
                if(!isNaN(max) && value!=='' && value>max){ showMessage('warning','নম্বর সর্বোচ্চ '+max+' হতে পারে।'); input.val(max); input.addClass('is-invalid'); setTimeout(()=>input.removeClass('is-invalid'),2000); }
                const row=input.closest('tr'); recalcRow(row); saveMark(input.data('student-id'));
            });

            $('table').on('change','.absent-checkbox',function(){ const chk=$(this), row=chk.closest('tr'), isAbsent=chk.is(':checked'); if(isAbsent){ row.find('.mark-input').each(function(){ $(this).val(''); $(this).prop('disabled',true); }); row.addClass('text-muted'); row.find('.total-marks').text('0.00'); row.find('.grade').text('-'); } else { row.find('.mark-input').prop('disabled',false); row.removeClass('text-muted'); recalcRow(row); } saveMark(chk.data('student-id')); });

            $('#saveAllBtn').on('click',function(){ const rows=$('tbody tr'), totalStudents=rows.length; let completed=0; if(totalStudents===0){ showMessage('info','কোনও শিক্ষার্থী পাওয়া যায়নি।'); return; } rows.each(function(){ const sid=$(this).data('student-id'); saveMark(sid, function(){ completed++; if(completed===totalStudents) showMessage('success','সকল শিক্ষার্থীর নম্বর সফলভাবে সংরক্ষণ করা হয়েছে!'); }); }); });

            function saveMark(studentId, callback){ const row=$('tr[data-student-id="'+studentId+'"]'); if(!row.length){ if(callback) callback(); return; } const isAbsent=row.find('.absent-checkbox').is(':checked'); const data={ _token:'{{ csrf_token() }}', student_id:studentId, is_absent:isAbsent?1:0 }; if(!isAbsent){ row.find('.mark-input').each(function(){ data[$(this).data('field')] = $(this).val()===''? null: $(this).val(); }); } row.find('.save-status').html('<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i></span>'); $.ajax({ url: saveUrl, method:'POST', data:data }) .done(function(response){ if(response && response.success){ row.find('.save-status').html('<span class="badge badge-success"><i class="fas fa-check"></i></span>'); if(response.total_marks!==undefined) row.find('.total-marks').text(response.total_marks); else recalcRow(row); row.find('.grade').text(response.letter_grade||'-'); } else { row.find('.save-status').html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>'); showMessage('danger',(response&&response.message)?response.message:'সংরক্ষণে সমস্যা হয়েছে'); } }) .fail(function(xhr){ row.find('.save-status').html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>'); showMessage('danger','নম্বর সংরক্ষণে সমস্যা হয়েছে: '+(xhr.responseJSON && xhr.responseJSON.message?xhr.responseJSON.message:'Unknown error')); }) .always(function(){ if(callback) callback(); }); }

            function showMessage(type,message,autoHide=true){ const alertHtml='\n<div class="alert alert-'+type+' alert-dismissible fade show" role="alert">'+message+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>\n'; $('#message-container').html(alertHtml); $('html,body').animate({ scrollTop:0 },'slow'); if(autoHide) setTimeout(function(){ $('#message-container').html(''); },4000); }
        });
    })(jQuery);
})();
</script>
@endpush

@endsection
