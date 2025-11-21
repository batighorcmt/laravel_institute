{{-- Obsolete: plan-level allocate view removed. Keeping stub to avoid errors if referenced inadvertently. --}}
@php( abort(404) )
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <table class="table table-sm mb-0">
                    <tbody>
                    @foreach($apps as $a)
                        <tr>
                            <td>{{ $a->id }}</td>
                            <td>{{ $a->student_name_bn ?: $a->student_name_en }}</td>
                            <td>
                                @php($alloc = $seatPlan->allocations->firstWhere('application_id',$a->id))
                                @if($alloc)
                                    <span class="badge badge-success">Allocated</span>
                                @else
                                    <button class="btn btn-sm btn-outline-primary" onclick="selectApplicant({{ $a->id }},'{{ addslashes($a->student_name_bn ?: $a->student_name_en) }}')">Select</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>রুম তালিকা</strong>
                <span id="selectedApplicant" class="text-muted">কোন আবেদনকারী নির্বাচন হয়নি</span>
            </div>
            <div class="card-body" style="max-height:460px; overflow:auto;">
                <style>
                    .seat-area{display:flex;gap:12px;align-items:flex-start;}
                    .seat-column{flex:0 0 33.33%;min-width:0;}
                    .bench{border:1px dashed #bbb;padding:6px;margin:6px 0;border-radius:6px;display:flex;justify-content:space-between;align-items:center;}
                    .seat-box{width:48%;padding:4px 4px;font-size:11px;min-height:40px;text-align:center;display:flex;flex-direction:column;justify-content:center;border:1px solid #ccc;border-radius:4px;}
                    .seat-box.assigned{background:#198754;color:#fff;border-color:#198754;}
                    @media print {body{background:#fff;} .btn, form, #selectedApplicant, .card-header button, .table button {display:none !important;} .seat-box{font-size:10px;} .bench{page-break-inside:avoid;}}
                </style>
                @foreach($seatPlan->rooms as $room)
                    <div class="mb-4 border p-2">
                        <h6 class="mb-2">রুম: {{ $room->room_no }} <small class="text-muted">{{ $room->title }}</small></h6>
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
                                                @php($existing = $seatPlan->allocations->firstWhere(fn($al)=>$al->room_id==$room->id && $al->col_no==$c && $al->bench_no==$b && $al->position==$pos))
                                                <div class="seat-box {{ $existing ? 'assigned' : '' }}">
                                                    <div class="small">B{{ $b }}-{{ $pos }}</div>
                                                    @if($existing)
                                                        <div class="small">App #{{ $existing->application_id }}</div>
                                                    @else
                                                        <button class="btn btn-xs btn-outline-secondary mt-1" type="button" onclick="allocateSeat({{ $room->id }},{{ $c }},{{ $b }},'{{ $pos }}',this)">Assign</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endfor
                                </div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<a href="{{ route('principal.institute.admissions.seat-plans.index',$school) }}" class="btn btn-secondary">ফিরে যান</a>

{{-- Removed form and scripts for obsolete allocate functionality --}}