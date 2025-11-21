@extends('layouts.admin')
@section('title','রুম ভিত্তিক সীট বরাদ্দ')
@section('content')
<h4 class="mb-3 d-flex justify-content-between align-items-center">রুম ভিত্তিক সীট বরাদ্দ: {{ $seatPlan->name }} | রুম: {{ $room->room_no }} <small class="text-muted">{{ $room->title }}</small></h4>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>সীট গ্রিড (রুম)</strong>
                <div>
                    <a href="{{ route('principal.institute.admissions.seat-plans.rooms.print',[$school,$seatPlan,$room]) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
                </div>
            </div>
            <div class="card-body" style="max-height:460px; overflow:auto;">
                <style>
                    .seat-area{display:flex;gap:12px;align-items:flex-start;}
                    .seat-column{flex:0 0 33.33%;min-width:0;}
                    .bench{border:1px dashed #bbb;padding:6px;margin:6px 0;border-radius:6px;display:flex;justify-content:space-between;align-items:center;}
                    .seat-box{width:48%;padding:4px 4px;font-size:11px;min-height:60px;text-align:center;display:flex;flex-direction:column;justify-content:center;border:1px solid #ccc;border-radius:4px;}
                    .seat-box.assigned{background:#198754;color:#fff;border-color:#198754;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;}
                    .seat-roll{font-size:14px;font-weight:700;line-height:1;margin:0;}
                    .seat-roll-big{font-size:22px;font-weight:800;line-height:1;margin:2px 0 4px 0;color:#fff;}
                    .seat-name{font-size:12px;font-weight:600;margin:0 0 4px 0;line-height:1.15;color:#f8f9fa;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
                    .seat-missing{font-size:12px;font-weight:700;color:#ffc107;}
                    .seat-code{font-size:10px;font-weight:600;color:#555;}
                    @media print {body{background:#fff;} .btn, form[action*='allocations.delete'] button, #selectedApplicant, .card-header button {display:none !important;} .bench{page-break-inside:avoid;} }
                </style>
                @php
                    $totalPositions = 0; $cols = $room->columns_count;
                    for($ci=1;$ci<=$cols;$ci++){ $totalPositions += (int)$room['col'.$ci.'_benches'] * 2; }
                    $assignedCount = $room->allocations->count();
                    $remainingCount = $totalPositions - $assignedCount;
                @endphp
                <div class="mb-2 p-2 border rounded bg-light small d-flex justify-content-between align-items-center">
                    <span><strong>মোট সীট:</strong> {{ $totalPositions }}</span>
                    <span><strong>অ্যাসাইন:</strong> {{ $assignedCount }}</span>
                    <span><strong>বাকি:</strong> {{ $remainingCount }}</span>
                </div>
                <div class="seat-area">
                @php($cols = $room->columns_count)
                @for($c=1;$c<=$cols;$c++)
                    @php($benches = $room['col'.$c.'_benches'])
                    <div class="seat-column">
                        @php($lang = request('lang','bn'))
                        <div class="text-center font-weight-bold mb-1">
                            @if($c==1) {{ $lang==='bn' ? 'বাম সারি' : 'Left Column' }}
                            @elseif($c==2) {{ $lang==='bn' ? 'মধ্য সারি' : 'Middle Column' }}
                            @else {{ $lang==='bn' ? 'ডান সারি' : 'Right Column' }}
                            @endif
                        </div>
                        @for($b=1;$b<=$benches;$b++)
                            <div class="bench">
                                @foreach(['L','R'] as $pos)
                                    @php($existing = $room->allocations->firstWhere(fn($al)=>$al->col_no==$c && $al->bench_no==$b && $al->position==$pos))
                                    <div class="seat-box {{ $existing ? 'assigned' : '' }}">
                                        @if($existing)
                                            @php($stu = $appMap[$existing->application_id] ?? null)
                                            @if($stu)
                                                <div class="seat-roll-big">{{ $stu->admission_roll_no }}</div>
                                                <div class="seat-name">{{ $stu->name_bn ?? $stu->name_en }}</div>
                                            @else
                                                <div class="seat-name">Unknown</div>
                                            @endif
                                            <form method="POST" action="{{ route('principal.institute.admissions.seat-plans.rooms.allocations.delete', [$school,$seatPlan,$room,$existing]) }}" onsubmit="return confirm('অপসারণ করবেন?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-xs btn-outline-warning mt-1">Unassign</button>
                                            </form>
                                        @else
                                            <div class="seat-code">B{{ $b }}-{{ $pos }}</div>
                                            <button class="btn btn-xs btn-outline-secondary mt-1" type="button" onclick="openAssignModal({{ $c }},{{ $b }},'{{ $pos }}')">Assign</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                @endfor
                </div>
            </div>
        </div>
    </div>
</div>
<a href="{{ route('principal.institute.admissions.seat-plans.rooms',[$school,$seatPlan]) }}" class="btn btn-secondary">রুম তালিকায় ফিরে যান</a>

<form id="allocForm" method="POST" action="{{ route('principal.institute.admissions.seat-plans.rooms.allocate.store',[$school,$seatPlan,$room]) }}" class="d-none">
        @csrf
        <input type="hidden" name="application_id" id="fApp">
        <input type="hidden" name="col_no" id="fCol">
        <input type="hidden" name="bench_no" id="fBench">
        <input type="hidden" name="position" id="fPos">
</form>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">সীট অ্যাসাইন</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeAssignModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                        <label>আবেদনকারী নির্বাচন করুন (Roll - নাম)</label>
                        <select id="appSelect" class="form-control">
                                <option value="">-- নির্বাচন করুন --</option>
                                @foreach($availableApps as $ap)
                                        <option value="{{ $ap->id }}">{{ $ap->admission_roll_no }} - {{ $ap->name_bn ?? $ap->name_en }}</option>
                                @endforeach
                        </select>
                </div>
                <div id="seatInfo" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">বন্ধ করুন</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssign()">Assign</button>
            </div>
        </div>
    </div>
</div>
<script>
let pendingSeat = {col:null,bench:null,pos:null};
function openAssignModal(col,bench,pos){
    pendingSeat = {col,bench,pos};
    document.getElementById('seatInfo').textContent = 'Seat: Column '+col+' Bench '+bench+' Position '+pos;
    // Show modal (Bootstrap 4 assumed); if not loaded fallback
    const m = document.getElementById('assignModal');
    if(window.jQuery && jQuery.fn.modal){ jQuery(m).modal('show'); } else { m.style.display='block'; m.classList.add('show'); }
}
function closeAssignModal(){
    const m = document.getElementById('assignModal');
    if(window.jQuery && jQuery.fn.modal){ jQuery(m).modal('hide'); } else { m.style.display='none'; m.classList.remove('show'); }
}
function confirmAssign(){
    const sel = document.getElementById('appSelect');
    const appId = sel.value;
    if(!appId){ alert('একজন আবেদনকারী নির্বাচন করুন'); return; }
    document.getElementById('fApp').value=appId;
    document.getElementById('fCol').value=pendingSeat.col;
    document.getElementById('fBench').value=pendingSeat.bench;
    document.getElementById('fPos').value=pendingSeat.pos;
    document.getElementById('allocForm').submit();
}
</script>
@endsection