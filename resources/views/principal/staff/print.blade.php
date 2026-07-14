@extends('layouts.print')

@section('title', $printTitle ?? 'কর্মচারী তালিকা')

@section('content')
<style>
    .staff-print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 8px;
    }
    .staff-print-table th,
    .staff-print-table td {
        border: 1px solid #333;
        padding: 4px 6px;
        vertical-align: middle;
    }
    .staff-print-table thead th {
        background: #e8e8e8;
        font-weight: 700;
        text-align: center;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .staff-print-table td.text-left { text-align: left; }
    .staff-print-table td.text-center { text-align: center; }
    .staff-print-photo {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    .staff-print-photo-placeholder {
        width: 32px;
        height: 32px;
        border: 1px dashed #999;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #666;
    }
</style>

<h3 style="text-align:center;margin-bottom:2px;">{{ $printTitle }}</h3>
<p style="text-align:center;margin-top:0;color:#555;">{{ $printSubtitle }}</p>

@php
    $isBn = ($lang ?? 'bn') === 'bn';

    if (!function_exists('bnNum')) {
        function bnNum($num, $isBn) {
            if (!$isBn || $num === null) return $num;
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($en, $bn, $num);
        }
    }
@endphp

<table class="staff-print-table">
    <thead>
        <tr>
            <th style="width:4%">ক্রমিক</th>
            @if(in_array('col-photo', $columns)) <th style="width:5%">ছবি</th> @endif
            @if(in_array('col-name-bn', $columns)) <th>নাম (বাংলা)</th> @endif
            @if(in_array('col-name-en', $columns)) <th>নাম (English)</th> @endif
            @if(in_array('col-designation', $columns)) <th>পদবী</th> @endif
            @if(in_array('col-mobile', $columns)) <th>মোবাইল</th> @endif
            @if(in_array('col-joining', $columns)) <th>যোগদানের তারিখ</th> @endif
            @if(in_array('col-address', $columns)) <th>ঠিকানা</th> @endif
        </tr>
    </thead>
    <tbody>
        @forelse($staff as $s)
            <tr>
                <td class="text-center">{{ bnNum($loop->iteration, $isBn) }}</td>
                @if(in_array('col-photo', $columns))
                    <td class="text-center">
                        @if($s->photo_url)
                            <img src="{{ $s->photo_url }}" alt="" class="staff-print-photo">
                        @else
                            <span class="staff-print-photo-placeholder">N/A</span>
                        @endif
                    </td>
                @endif
                @if(in_array('col-name-bn', $columns)) <td class="text-left">{{ $s->full_name_bn ?: '—' }}</td> @endif
                @if(in_array('col-name-en', $columns)) <td class="text-left">{{ $s->full_name ?: '—' }}</td> @endif
                @if(in_array('col-designation', $columns)) <td class="text-left">{{ $s->designationRef?->name_bn ?: $s->designationRef?->name_en ?: '—' }}</td> @endif
                @if(in_array('col-mobile', $columns)) <td class="text-center">{{ bnNum($s->phone, $isBn) ?: '—' }}</td> @endif
                @if(in_array('col-joining', $columns)) <td class="text-center">{{ $s->joining_date ? bnNum($s->joining_date->format('d/m/Y'), $isBn) : '—' }}</td> @endif
                @if(in_array('col-address', $columns)) <td class="text-left">{{ $s->address ?: '—' }}</td> @endif
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center">কোনো কর্মচারী নেই</td>
            </tr>
        @endforelse
    </tbody>
</table>

<script>
    window.onload = function() {
        window.print();
    }
</script>
@endsection
