@extends('layouts.admin')
@section('title','নম্বর উত্তোলন')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">নম্বর উত্তোলন: {{ $exam->name }} ({{ $exam->type==='subject'?'বিষয়ভিত্তিক':'সামগ্রিক' }})</h4>
    <a href="{{ route('principal.institute.admissions.exams.index',$school) }}" class="btn btn-sm btn-secondary">ফিরে যান</a>
</div>
@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
<div class="card mb-3">
    <div class="card-body p-0">
        <form method="POST" action="{{ route('principal.institute.admissions.exams.marks.store',[$school,$exam]) }}">
            @csrf
            <div class="marks-scroll-wrapper" style="overflow-x:auto; position:relative;">
                <table class="table table-bordered table-sm mb-0 marks-table" style="min-width:900px;">
                    <thead class="thead-light">
                        <tr>
                            <th class="sticky-col" style="white-space:nowrap; left:0; z-index:5; background:#f8f9fa;">Roll</th>
                            <th class="sticky-col" style="white-space:nowrap; left:90px; z-index:5; background:#f8f9fa;">নাম</th>
                            @if($exam->type==='subject')
                                @foreach($exam->subjects->sortBy('display_order') as $sub)
                                    <th class="text-center" style="min-width:80px;">
                                        {{ $sub->subject_name }}<br><small>FM: {{ $sub->full_mark }}{{ $sub->pass_mark ? ' / PM: '.$sub->pass_mark : '' }}</small>
                                    </th>
                                @endforeach
                            @else
                                @php($overallMax = 100)
                                <th style="min-width:120px;" class="text-center">মোট নম্বর<br><small>FM: {{ $overallMax }}</small></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($apps as $ap)
                        <tr>
                            <td class="sticky-col" style="left:0; background:#fff;">{{ $ap->admission_roll_no }}</td>
                            <td class="sticky-col" style="left:90px; background:#fff; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $ap->name_bn ?? $ap->name_en }}</td>
                            @if($exam->type==='subject')
                                @foreach($exam->subjects->sortBy('display_order') as $sub)
                                    @php($existing = optional($marks->get($ap->id))->firstWhere('subject_id',$sub->id))
                                    <td class="p-1">
                                        <input type="number" name="marks[{{ $ap->id }}][{{ $sub->id }}]" value="{{ $existing?->obtained_mark }}" min="0" max="{{ $sub->full_mark }}" class="form-control form-control-sm" />
                                    </td>
                                @endforeach
                            @else
                                @php($existing = optional($marks->get($ap->id))->first())
                                <td class="p-1">
                                    <input type="number" name="overall[{{ $ap->id }}]" value="{{ $existing?->obtained_mark }}" min="0" max="{{ $overallMax }}" class="form-control form-control-sm" />
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 2 + ($exam->type==='subject' ? $exam->subjects->count() : 1) }}" class="text-center py-4">কোনো গ্রহণকৃত আবেদনকারী নেই</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2 border-top">
                <button class="btn btn-primary">নম্বর সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>
<style>
/* Freeze first two columns */
.marks-scroll-wrapper { max-height: calc(100vh - 280px); }
.marks-table th, .marks-table td { white-space: nowrap; }
.marks-table .sticky-col { position: sticky; }
@media (max-width: 900px){
  .marks-table { min-width:100%; }
}
</style>
<script>
// Real-time clamp for mark inputs (respects min/max attributes)
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.marks-table input[type="number"]').forEach(function(inp){
        inp.addEventListener('input', function(){
            var min = (this.min !== '' ? parseInt(this.min, 10) : null);
            var max = (this.max !== '' ? parseInt(this.max, 10) : null);
            var val = this.value === '' ? '' : parseInt(this.value, 10);
            if (val === '') { return; }
            if (min !== null && val < min) { this.value = min; return; }
            if (max !== null && val > max) { this.value = max; return; }
        });
    });
});
</script>
@endsection