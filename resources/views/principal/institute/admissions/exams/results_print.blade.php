@extends('layouts.print')

@php
    $lang = request('lang','bn');
    $printTitle = $lang==='bn' ? 'ভর্তি পরীক্ষার ফলাফল' : 'Admission Exam Results';
    $examName = $lang==='bn' ? $exam->name : ($exam->name_en ?? $exam->name);
    $dateLabel = $lang==='bn' ? 'তারিখ' : 'Date';
    $printSubtitle = $examName . ($exam->exam_date ? ' | '.$dateLabel.': '.$exam->exam_date->format('d/m/Y') : '');
    
    // Sorting parameters
    $sortBy = request('sort', 'merit'); // merit or roll
    $sortOrder = request('order', 'asc'); // asc or desc
    
    // Sort results based on parameters
    if($sortBy === 'roll') {
        $sortedResults = $sortOrder === 'asc' 
            ? $results->sortBy(fn($r) => $r->application->admission_roll_no)
            : $results->sortByDesc(fn($r) => $r->application->admission_roll_no);
    } else {
        $sortedResults = $sortOrder === 'asc' 
            ? $results->sortBy('merit_position')
            : $results->sortByDesc('merit_position');
    }
@endphp

@section('title', $lang==='bn' ? 'ভর্তি পরীক্ষার ফলাফল' : 'Admission Exam Results')

@section('content')
<!-- Sorting Controls -->
<div class="no-print" style="margin-bottom:15px; padding:10px; background:#f8f9fa; border-radius:5px; display:flex; gap:15px; align-items:center; justify-content:center;">
    <div>
        <strong>{{ $lang==='bn' ? 'সাজান:' : 'Sort by:' }}</strong>
        <select id="sortBy" style="padding:5px 10px; margin-left:5px; border:1px solid #ccc; border-radius:3px;">
            <option value="merit" {{ $sortBy==='merit' ? 'selected' : '' }}>{{ $lang==='bn' ? 'মেধাক্রম' : 'Merit Position' }}</option>
            <option value="roll" {{ $sortBy==='roll' ? 'selected' : '' }}>{{ $lang==='bn' ? 'রোল নং' : 'Roll No' }}</option>
        </select>
    </div>
    <div>
        <strong>{{ $lang==='bn' ? 'ক্রম:' : 'Order:' }}</strong>
        <select id="sortOrder" style="padding:5px 10px; margin-left:5px; border:1px solid #ccc; border-radius:3px;">
            <option value="asc" {{ $sortOrder==='asc' ? 'selected' : '' }}>{{ $lang==='bn' ? 'ছোট থেকে বড়' : 'Ascending' }}</option>
            <option value="desc" {{ $sortOrder==='desc' ? 'selected' : '' }}>{{ $lang==='bn' ? 'বড় থেকে ছোট' : 'Descending' }}</option>
        </select>
    </div>
    <button onclick="applySorting()" style="padding:5px 15px; background:#007bff; color:#fff; border:none; border-radius:3px; cursor:pointer; font-weight:600;">{{ $lang==='bn' ? 'প্রয়োগ করুন' : 'Apply' }}</button>
</div>

<div class="table-responsive">
    <table class="table table-bordered mb-0">
        <thead>
            <tr>
                <th style="text-align:center;">{{ $lang==='bn' ? 'মেধাক্রম' : 'Merit Position' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'ভর্তি রোল নং' : 'Admission Roll' }}</th>
                <th>{{ $lang==='bn' ? 'নাম' : 'Name' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'মোবাইল নং' : 'Mobile' }}</th>
                @if($exam->type==='subject')
                    @foreach($exam->subjects->sortBy('display_order') as $sub)
                        <th style="text-align:center;">{{ $lang==='bn' ? $sub->subject_name : ($sub->subject_name_en ?? $sub->subject_name) }}</th>
                    @endforeach
                @endif
                <th style="text-align:center;">{{ $lang==='bn' ? 'মোট' : 'Total' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'পাস/ফেল' : 'Pass/Fail' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'ফেল বিষয়' : 'Failed Subjects' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sortedResults as $res)
                @php($app = $res->application)
                <tr>
                    <td style="text-align:center; font-weight:600;">{{ $res->merit_position }}</td>
                    <td style="text-align:center; font-weight:600;">{{ $app?->admission_roll_no }}</td>
                    <td>{{ $lang==='bn' ? ($app?->name_bn ?? $app?->name_en) : ($app?->name_en ?? $app?->name_bn) }}</td>
                    @php($m = $app?->mobile ? preg_replace('/[^0-9]/','', $app->mobile) : '')
                    @php($m = (strlen($m)===13 && substr($m,0,3)==='880') ? ('0'.substr($m,3)) : $m)
                    <td style="text-align:center;">{{ $m }}</td>
                    @if($exam->type==='subject')
                        @php($marksMap = $exam->marks()->where('application_id',$app->id)->get()->keyBy('subject_id'))
                        @foreach($exam->subjects->sortBy('display_order') as $sub)
                            <td style="text-align:center;">{{ $marksMap[$sub->id]->obtained_mark ?? 0 }}</td>
                        @endforeach
                    @endif
                    <td style="text-align:center; font-weight:800; font-size:15px;">{{ $res->total_obtained }}</td>
                    <td style="text-align:center;">
                        @if($res->is_pass)
                            <span style="background:#28a745;color:#fff;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:700;">{{ $lang==='bn' ? 'উত্তীর্ণ' : 'PASS' }}</span>
                        @else
                            <span style="background:#dc3545;color:#fff;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:700;">{{ $lang==='bn' ? 'অকৃতকার্য' : 'FAIL' }}</span>
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $res->failed_subjects_count }}</td>
                </tr>
            @endforeach
            @if($results->isEmpty())
                <tr><td colspan="{{ 7 + ($exam->type==='subject' ? $exam->subjects->count() : 0) }}" style="text-align:center;padding:20px;">{{ $lang==='bn' ? 'কোন ফলাফল নেই' : 'No results found' }}</td></tr>
            @endif
        </tbody>
    </table>
</div>

@push('print_head')
<style>
    .table{ width:100%; border-collapse:collapse; font-size:13px; }
    .table th, .table td{ border:1px solid #333; padding:8px 10px; }
    .table thead th{ background:#e8e8e8; font-weight:700; text-align:center; font-size:14px; }
    .table tbody td{ vertical-align:middle; }
    @media print {
        .table{ font-size:12px; }
        .table th, .table td{ padding:6px 8px; }
        .table thead th{ font-size:13px; }
    }
</style>
@endpush

@push('print_scripts')
<script>
function applySorting() {
    const sortBy = document.getElementById('sortBy').value;
    const sortOrder = document.getElementById('sortOrder').value;
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortBy);
    url.searchParams.set('order', sortOrder);
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
