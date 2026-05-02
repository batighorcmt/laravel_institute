@extends('layouts.print')

@section('suppress_header', true)

@section('title', $lang === 'bn' ? 'লেসন ইভালুয়েশন রিপোর্ট' : 'Lesson Evaluation Report')

@section('content')
    @php
        $toBn = function($number) use ($lang) {
            if ($lang !== 'bn') return $number;
            $bn_digits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace(['0','1','2','3','4','5','6','7','8','9'], $bn_digits, $number);
        };

        $filters = [];
        if($fromDate || $toDate) {
            $dateRange = "";
            if($fromDate && $toDate) $dateRange = ($lang === 'bn' ? 'তারিখ: ' : 'Date: ') . $toBn($fromDate) . ' - ' . $toBn($toDate);
            elseif($fromDate) $dateRange = ($lang === 'bn' ? 'তারিখ হতে: ' : 'From Date: ') . $toBn($fromDate);
            elseif($toDate) $dateRange = ($lang === 'bn' ? 'তারিখ পর্যন্ত: ' : 'To Date: ') . $toBn($toDate);
            $filters[] = $dateRange;
        }

        if($request->filled('class_id')) {
            $c = \App\Models\SchoolClass::find($request->class_id);
            if($c) $filters[] = ($lang === 'bn' ? 'শ্রেণি: ' : 'Class: ') . ($lang === 'bn' ? ($c->bangla_name ?: $c->name) : $c->name);
        }
        if($request->filled('section_id')) {
            $s = \App\Models\Section::find($request->section_id);
            if($s) $filters[] = ($lang === 'bn' ? 'শাখা: ' : 'Section: ') . ($lang === 'bn' ? ($s->bangla_name ?: $s->name) : $s->name);
        }
        if($request->filled('subject_id')) {
            $sub = \App\Models\Subject::find($request->subject_id);
            if($sub) $filters[] = ($lang === 'bn' ? 'বিষয়: ' : 'Subject: ') . ($lang === 'bn' ? ($sub->bangla_name ?: $sub->name) : $sub->name);
        }
        if($request->filled('teacher_id')) {
            $t = \App\Models\Teacher::find($request->teacher_id);
            if($t) {
                $name = ($lang === 'bn' ? ($t->full_name_bn ?: $t->full_name) : $t->full_name);
                if ($t->initials) $name .= " [{$t->initials}]";
                $filters[] = ($lang === 'bn' ? 'শিক্ষক: ' : 'Teacher: ') . $name;
            }
        }
    @endphp

    @php
        $logoUrl = asset('images/default-logo.png');
        if(isset($school) && $school && $school->logo){
            $candidates = ['uploads/schools/'.$school->logo, 'storage/schools/'.$school->logo, 'storage/'.$school->logo];
            foreach($candidates as $c){ 
                if(file_exists(public_path($c))){ $logoUrl = asset($c); break; }
            }
        }
    @endphp

    {{-- Custom Header with logo (absolute left) and border under filters --}}
    <div style="border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 15px; position: relative;">
        @if($logoUrl)
            <div style="position: absolute; left: 5px; top: 0;">
                <img src="{{ $logoUrl }}" style="width: 75px; height: 75px; object-fit: contain;">
            </div>
        @endif

        <div style="text-align: center;">
            <h1 style="margin: 0; font-size: 26px; font-weight: 800;">{{ $lang==='bn' ? ($school->name_bn ?? $school->name) : ($school->name ?? $school->name_bn) }}</h1>
            @php $addr = $lang==='bn' ? ($school->address_bn ?? $school->address) : ($school->address ?? $school->address_bn); @endphp
            @if($addr)
                <div style="font-size: 15px; font-weight: 500;">{{ $addr }}</div>
            @endif
            <h2 style="margin: 5px 0 0 0; font-size: 20px; font-weight: 700;">{{ $lang === 'bn' ? 'লেসন ইভালুয়েশন রিপোর্ট' : 'Lesson Evaluation Report' }}</h2>
        </div>
        
        @if(count($filters) > 0)
            <div style="text-align: center; margin-top: 8px;">
                <p style="margin: 0; font-weight: bold; font-size: 13px;">{{ implode(' | ', $filters) }}</p>
            </div>
        @endif
    </div>

    <style>
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .print-table th, .print-table td {
            border: 1px solid #333;
            padding: 4px;
            text-align: left;
        }
        .print-table th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            text-align: center;
        }
        .badge-completed { background-color: #d4edda !important; color: #155724 !important; }
        .badge-partial { background-color: #fff3cd !important; color: #856404 !important; }
        .badge-not_done { background-color: #f8d7da !important; color: #721c24 !important; }
        .badge-absent { background-color: #e2e3e5 !important; color: #383d41 !important; }
    </style>

    @php
        $showDate = true; 
        $showTeacher = !$request->filled('teacher_id');
        $showClass = !$request->filled('class_id');
        $showSection = !$request->filled('section_id');
        $showSubject = !$request->filled('subject_id');
        $totalCols = 6 + ($showDate?1:0) + ($showTeacher?1:0) + ($showClass?1:0) + ($showSection?1:0) + ($showSubject?1:0);
    @endphp

    <table class="print-table">
        <thead>
            <tr>
                <th style="width: 30px;">{{ $lang === 'bn' ? 'ক্রম' : 'SL' }}</th>
                @if($showDate) <th>{{ $lang === 'bn' ? 'তারিখ' : 'Date' }}</th> @endif
                @if($showTeacher) <th>{{ $lang === 'bn' ? 'শিক্ষক' : 'Teacher' }}</th> @endif
                @if($showClass) <th>{{ $lang === 'bn' ? 'শ্রেণি' : 'Class' }}</th> @endif
                @if($showSection) <th>{{ $lang === 'bn' ? 'শাখা' : 'Section' }}</th> @endif
                @if($showSubject) <th>{{ $lang === 'bn' ? 'বিষয়' : 'Subject' }}</th> @endif
                <th style="width:35px; text-align:center;">{{ $lang === 'bn' ? 'মোট' : 'Total' }}</th>
                <th style="width:60px; text-align:center;">{{ $lang === 'bn' ? 'পড়া হয়েছে' : 'Done' }}</th>
                <th style="width:45px; text-align:center;">{{ $lang === 'bn' ? 'আংশিক' : 'Partial' }}</th>
                <th style="width:55px; text-align:center;">{{ $lang === 'bn' ? 'পড়া হয়নি' : 'Not Done' }}</th>
                <th style="width:55px; text-align:center;">{{ $lang === 'bn' ? 'অনুপস্থিত' : 'Absent' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluations as $idx => $ev)
                @php 
                    $stats = $ev->getCompletionStats(); 
                @endphp
                <tr>
                    <td style="text-align:center;">{{ $toBn($idx + 1) }}</td>
                    @if($showDate) 
                        <td>
                            {{ $toBn(optional($ev->evaluation_date)->format('d-m-Y') ?: '-') }}<br>
                            <small>{{ $ev->evaluation_time ? $toBn($ev->evaluation_time->format('h:i A')) : '' }}</small>
                        </td> 
                    @endif
                    @if($showTeacher) 
                        <td>
                            {{ $lang === 'bn' ? ($ev->teacher->full_name_bn ?: $ev->teacher->full_name) : $ev->teacher->full_name }}
                            @if($ev->teacher->initials) [{{ $ev->teacher->initials }}] @endif
                        </td> 
                    @endif
                    @if($showClass) 
                        <td>{{ $lang === 'bn' ? ($ev->class->bangla_name ?: $ev->class->name) : $ev->class->name }}</td> 
                    @endif
                    @if($showSection) 
                        <td>{{ $lang === 'bn' ? ($ev->section->bangla_name ?: $ev->section->name) : $ev->section->name }}</td> 
                    @endif
                    @if($showSubject) 
                        <td>
                            {{ $lang === 'bn' ? ($ev->subject->bangla_name ?: $ev->subject->name) : $ev->subject->name }}
                            @if($ev->notes)
                                <div style="font-size: 8px; color: #555; line-height:1.2;">{{ $ev->notes }}</div>
                            @endif
                        </td> 
                    @endif
                    <td style="text-align:center;">{{ $toBn($stats['total']) }}</td>
                    <td style="text-align:center;" class="badge-completed">{{ $toBn($stats['completed']) }}</td>
                    <td style="text-align:center;" class="badge-partial">{{ $toBn($stats['partial']) }}</td>
                    <td style="text-align:center;" class="badge-not_done">{{ $toBn($stats['not_done']) }}</td>
                    <td style="text-align:center;" class="badge-absent">{{ $toBn($stats['absent']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalCols }}" style="text-align: center;">
                        {{ $lang === 'bn' ? 'কোন তথ্য পাওয়া যায়নি' : 'No evaluations found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 11px; color: #666;">
        <p>{{ $lang === 'bn' ? 'রিপোর্ট তৈরির সময়: ' : 'Report Generated At: ' }} {{ $toBn(now()->format('Y-m-d H:i:s')) }}</p>
    </div>
@endsection
