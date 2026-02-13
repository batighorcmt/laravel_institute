@extends('layouts.print')

@section('title', $printTitle)

@push('print_head')
<style>
    @page { size: legal landscape; margin: 5mm 10mm; }
    
    .table-tabulation { border-collapse: collapse !important; margin-bottom: 20px; }
    .table-tabulation th, .table-tabulation td { 
        border: 1px solid #000 !important; 
        padding: 1px 2px !important; 
        font-size: 8pt !important; 
        line-height: 1.1 !important;
        vertical-align: middle !important;
    }
    .table-tabulation thead th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }

    /* Preview specific */
    .preview-wrapper { 
        overflow-x: auto; 
        width: 100%; 
        border: 1px solid #ddd; 
        padding-bottom: 10px;
        background: #fff;
    }
    .original { 
        width: max-content !important; 
        min-width: 100%; 
        table-layout: auto !important; 
    }
    .original th, .original td { white-space: nowrap; }

    /* Print specific */
    #printSplitContainer .table-tabulation { 
        width: 100% !important; 
        table-layout: fixed !important; 
    }
    
    .text-center { text-align: center !important; }
    .font-weight-bold { font-weight: bold !important; }
    .st-name { font-size: 8.5pt !important; overflow: hidden; text-overflow: ellipsis; }
    
    .sub-title { font-size: 7pt !important; font-weight: 700; min-height: 45px; display: flex; align-items: center; justify-content: center; flex-direction: column; }
    
    .fail-count { color: #d32f2f !important; font-weight: 800; }
    
    #printSplitContainer { display: none; }
    
    @media print {
        .no-print { display: none !important; }
        .original, .preview-wrapper { display: none !important; }
        #printSplitContainer { display: block !important; }
        #printSplitContainer .table-tabulation { margin-bottom: 30px; }
    }
</style>
@endpush

@php
    if (!function_exists('toBn')) {
        function toBn($number, $lang = 'bn') {
            if ($lang !== 'bn') return $number;
            $search = ['0','1','2','3','4','5','6','7','8','9'];
            $replace = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($search, $replace, $number);
        }
    }
@endphp

@section('content')
<div class="print-content">
    <div class="no-print mb-2" style="text-align: right;">
        <button id="btnPrint" class="btn btn-sm btn-dark"><i class="fas fa-print"></i> Print Tabulation</button>
    </div>

    @if($results->count() > 0)
        <!-- Split Container for Large Tables -->
        <div id="printSplitContainer"></div>

        <div class="preview-wrapper no-print">
            <table class="table-tabulation original" id="tabulationTable">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 25px;">{{ $lang === 'bn' ? 'ক্র:' : 'SL' }}</th>
                        <th rowspan="2" style="width: 65px;">{{ $lang === 'bn' ? 'আইডি' : 'ID' }}</th>
                        <th rowspan="2" style="width: 35px;">{{ $lang === 'bn' ? 'রোল' : 'Roll' }}</th>
                        <th rowspan="2" style="width: 140px;">{{ $lang === 'bn' ? 'নাম' : 'Name' }}</th>
                        
                        @foreach($finalSubjects as $key => $subject)
                            @php
                                $parts = [];
                                if($subject['creative_full_mark'] > 0) $parts[] = 'c';
                                if($subject['mcq_full_mark'] > 0) $parts[] = 'm';
                                if($subject['practical_full_mark'] > 0) $parts[] = 'p';
                                $parts[] = 't';
                                $parts[] = 'g';
                                $colspan = count($parts);
                            @endphp
                            <th colspan="{{ $colspan }}" class="text-center subject-group" data-sub-index="{{ $loop->index }}">
                                <div class="sub-title">
                                    {{ $subject['name'] }}<br>
                                    <small>({{ toBn($subject['total_full_mark'], $lang) }})</small>
                                </div>
                            </th>
                        @endforeach
                        
                        <th rowspan="2" class="summary-col" style="width: 35px;">{{ $lang === 'bn' ? 'ঐচ্ছিক বিষয়' : 'Opt Code' }}</th>
                        <th rowspan="2" class="summary-col" style="width: 45px;">{{ $lang === 'bn' ? 'মোট' : 'Total' }}</th>
                        <th rowspan="2" class="summary-col" style="width: 35px;">{{ $lang === 'bn' ? 'জিপিএ' : 'GPA' }}</th>
                        <th rowspan="2" class="summary-col" style="width: 25px;">{{ $lang === 'bn' ? 'গ্রেড' : 'Grd' }}</th>
                        <th rowspan="2" class="summary-col" style="width: 45px;">{{ $lang === 'bn' ? 'অবস্থা' : 'Status' }}</th>
                        <th rowspan="2" class="summary-col" style="width: 25px;">{{ $lang === 'bn' ? 'ফেল' : 'Fail' }}</th>
                    </tr>
                    <tr>
                        @foreach($finalSubjects as $key => $subject)
                            @if($subject['creative_full_mark'] > 0) <th class="text-center subgrp-{{ $loop->index }}">CQ</th> @endif
                            @if($subject['mcq_full_mark'] > 0) <th class="text-center subgrp-{{ $loop->index }}">MQ</th> @endif
                            @if($subject['practical_full_mark'] > 0) <th class="text-center subgrp-{{ $loop->index }}">PR</th> @endif
                            <th class="text-center subgrp-{{ $loop->index }}">T</th>
                            <th class="text-center subgrp-{{ $loop->index }}">G</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                        @php 
                            $st = $result->student;
                            $enroll = $st->currentEnrollment;
                            $stName = $lang === 'bn' ? ($st->student_name_bn ?: $st->student_name_en) : ($st->student_name_en ?: $st->student_name_bn);
                        @endphp
                        <tr>
                            <td class="text-center">{{ toBn($loop->iteration, $lang) }}</td>
                            <td class="text-center">{{ toBn($st->student_id, $lang) }}</td>
                            <td class="text-center">{{ $enroll ? toBn($enroll->roll_no, $lang) : '-' }}</td>
                            <td class="st-name">{{ $stName }}</td>
    
                            @foreach($finalSubjects as $key => $subject)
                                @php
                                    $resData = $result->subject_results->get($key);
                                    $grade = $resData['grade'] ?? '';
                                    $gpa = $resData['gpa'] ?? '';
                                    $total = $resData['total'] ?? '';
                                    $creative = $resData['creative'] ?? '';
                                    $mcq = $resData['mcq'] ?? '';
                                    $practical = $resData['practical'] ?? '';
                                    $isNR = ($grade === 'N/R' || ($grade === '' && empty($resData['display_only'])));
                                    $isAbsent = $resData['is_absent'] ?? false;
                                    $isNA = !empty($resData['is_not_applicable']);
                                @endphp
    
                                @if($subject['creative_full_mark'] > 0)
                                    <td class="text-center subgrp-{{ $loop->index }}">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : toBn($creative, $lang)) }}</td>
                                @endif
                                @if($subject['mcq_full_mark'] > 0)
                                    <td class="text-center subgrp-{{ $loop->index }}">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : toBn($mcq, $lang)) }}</td>
                                @endif
                                @if($subject['practical_full_mark'] > 0)
                                    <td class="text-center subgrp-{{ $loop->index }}">{{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : toBn($practical, $lang)) }}</td>
                                @endif
    
                                <td class="text-center font-weight-bold subgrp-{{ $loop->index }}">
                                    {{ ($isNA || $isNR) ? '' : ($isAbsent ? 'Ab' : toBn($total, $lang)) }}
                                </td>
                                <td class="text-center subgrp-{{ $loop->index }}">
                                    @if($isNA || $isNR || !empty($resData['display_only']))
                                        {{-- Empty --}}
                                    @else
                                        {{ toBn(number_format($gpa, 2), $lang) }}
                                    @endif
                                </td>
                            @endforeach
    
                            <td class="text-center small summary-col">{{ $result->fourth_subject_code ?? '-' }}</td>
                            <td class="text-center summary-col"><strong>{{ toBn(number_format($result->computed_total_marks ?? 0, 0), $lang) }}</strong></td>
                            <td class="text-center summary-col"><strong>{{ toBn(number_format($result->computed_gpa ?? 0, 2), $lang) }}</strong></td>
                            <td class="text-center summary-col">
                                @php $letter = $result->computed_letter ?? 'F'; @endphp
                                <strong>{{ $letter }}</strong>
                            </td>
                            <td class="text-center small summary-col">
                                @php 
                                    $status = $result->computed_status;
                                    if ($lang === 'bn') {
                                        $status = ($status === 'অকৃতকার্য' || $letter === 'F') ? 'Failed' : 'Passed';
                                        $status = ($status === 'অকৃতকার্য' || $letter === 'F') ? 'ফেল' : 'পাস';
                                    } else {
                                        $status = ($status === 'অকৃতকার্য' || $letter === 'F') ? 'Failed' : 'Passed';
                                    }
                                @endphp
                                {{ $status }}
                            </td>
                            <td class="text-center fail-count summary-col">
                                {{ $result->fail_count > 0 ? toBn($result->fail_count, $lang) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="original" style="display: flex; justify-content: space-between; margin-top: 40px; padding: 0 40px;">
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'শ্রেণি শিক্ষক' : 'Class Teacher' }}
            </div>
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'যাচাইকারী' : 'Verified By' }}
            </div>
            <div style="text-align: center; width: 150px; border-top: 1px solid #000; padding-top: 5px; font-size: 10pt;">
                {{ $lang === 'bn' ? 'প্রধান শিক্ষক' : 'Principal' }}
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            {{ $lang === 'bn' ? 'কোন তথ্য পাওয়া যায়নি।' : 'No results found.' }}
        </div>
    @endif
</div>
@endsection

@push('print_scripts')
<script>
(function(){
    const btn = document.getElementById('btnPrint');
    const table = document.getElementById('tabulationTable');
    const container = document.getElementById('printSplitContainer');
    const signatures = document.querySelector('.original[style*="flex"]');
    
    if (!btn || !table || !container) return;

    btn.addEventListener('click', function(){
        const groups = Array.from(table.querySelectorAll('thead .subject-group'));
        const n = groups.length; 
        
        if (n <= 12) {
            table.classList.remove('original');
            window.print();
            table.classList.add('original');
            return;
        }

        const mid = Math.max(0, Math.ceil(n/2));
        
        function buildClone(hideStart, hideEnd, showSummary = true){
            const clone = table.cloneNode(true);
            clone.id = '';
            clone.classList.remove('original');

            // Hide subjects outside range
            for(let i=0; i<n; i++){
                const keep = (i >= hideStart && i < hideEnd);
                clone.querySelectorAll('.subgrp-'+i).forEach(el => { el.style.display = keep ? '' : 'none'; });
                const groupHeader = clone.querySelector('.subject-group[data-sub-index="'+i+'"]');
                if (groupHeader) groupHeader.style.display = keep ? '' : 'none';
            }

            // Hide summary columns if not requested
            if (!showSummary) {
                clone.querySelectorAll('.summary-col').forEach(el => { el.style.display = 'none'; });
            }

            // Recompute colspans
            const headerGroups = Array.from(clone.querySelectorAll('thead .subject-group'));
            headerGroups.forEach(th => {
                if (th.style.display === 'none') return;
                const idx = th.getAttribute('data-sub-index');
                const visible = clone.querySelectorAll('thead tr:nth-child(2) .subgrp-' + idx);
                let count = 0;
                visible.forEach(v => { if (v.style.display !== 'none') count++; });
                th.setAttribute('colspan', count);
            });

            return clone;
        }

        container.innerHTML = '';
        
        // Part 1: Left Half (No summary)
        const left = buildClone(0, mid, false);
        container.appendChild(left);
        
        const brk = document.createElement('div'); 
        brk.style.pageBreakAfter = 'always'; 
        container.appendChild(brk);
        
        // Part 2: Right Half (With summary)
        const right = buildClone(mid, n, true);
        container.appendChild(right);
        
        // Append signatures to the last part
        if (signatures) {
            const sigClone = signatures.cloneNode(true);
            sigClone.classList.remove('original');
            container.appendChild(sigClone);
        }

        window.print();
    });
})();
</script>
@endpush
