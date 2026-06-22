@extends('layouts.print')

@section('title', $printTitle ?? 'শিক্ষক তালিকা')

@section('content')
<style>
    .teacher-print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 8px;
    }
    .teacher-print-table th,
    .teacher-print-table td {
        border: 1px solid #333;
        padding: 4px 6px;
        vertical-align: middle;
    }
    .teacher-print-table thead th {
        background: #e8e8e8;
        font-weight: 700;
        text-align: center;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .teacher-print-table td.text-left { text-align: left; }
    .teacher-print-table td.text-center { text-align: center; }
    .teacher-print-photo {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    .teacher-print-photo-placeholder {
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

@php
    $columns = request('columns', ['col-photo', 'col-name-bn', 'col-designation', 'col-mobile']);
    $isBn = request('lang', 'bn') === 'bn';

    if (!function_exists('bnNum')) {
        function bnNum($num, $isBn) {
            if (!$isBn) return $num;
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($en, $bn, $num);
        }
    }
@endphp

<table class="teacher-print-table">
    <thead>
        <tr>
            <th style="width:4%">ক্রমিক</th>
            @if(in_array('col-photo', $columns)) <th style="width:5%">ছবি</th> @endif
            @if(in_array('col-name-bn', $columns)) <th>নাম (বাংলা)</th> @endif
            @if(in_array('col-name-en', $columns)) <th>নাম (ইংরেজি)</th> @endif
            @if(in_array('col-father-bn', $columns)) <th>পিতার নাম (বাংলা)</th> @endif
            @if(in_array('col-father-en', $columns)) <th>পিতার নাম (English)</th> @endif
            @if(in_array('col-mother-bn', $columns)) <th>মাতার নাম (বাংলা)</th> @endif
            @if(in_array('col-mother-en', $columns)) <th>মাতার নাম (English)</th> @endif
            @if(in_array('col-designation', $columns)) <th>পদবী</th> @endif
            @if(in_array('col-mobile', $columns)) <th>মোবাইল</th> @endif
            @if(in_array('col-dob', $columns)) <th>জন্ম তারিখ</th> @endif
            @if(in_array('col-join-date', $columns)) <th>যোগদান তারিখ</th> @endif
            @if(in_array('col-present-addr', $columns)) <th>বর্তমান ঠিকানা</th> @endif
            @if(in_array('col-permanent-addr', $columns)) <th>স্থায়ী ঠিকানা</th> @endif
            @if(in_array('col-username', $columns)) <th>ইউজারনেম</th> @endif
            @if(in_array('col-password', $columns)) <th>পাসওয়ার্ড</th> @endif
        </tr>
    </thead>
    <tbody>
        @forelse($teachers as $teacher)
            @php
                $presentAddr = [];
                if($teacher->present_village) $presentAddr[] = $teacher->present_village;
                if($teacher->present_post_office) $presentAddr[] = $teacher->present_post_office;
                if($teacher->presentThana) $presentAddr[] = $teacher->presentThana->bn_name ?? $teacher->presentThana->name;
                if($teacher->presentDistrict) $presentAddr[] = $teacher->presentDistrict->bn_name ?? $teacher->presentDistrict->name;
                
                $permanentAddr = [];
                if($teacher->permanent_village) $permanentAddr[] = $teacher->permanent_village;
                if($teacher->permanent_post_office) $permanentAddr[] = $teacher->permanent_post_office;
                if($teacher->permanentThana) $permanentAddr[] = $teacher->permanentThana->bn_name ?? $teacher->permanentThana->name;
                if($teacher->permanentDistrict) $permanentAddr[] = $teacher->permanentDistrict->bn_name ?? $teacher->permanentDistrict->name;
            @endphp
            <tr>
                <td class="text-center">{{ bnNum($loop->iteration, $isBn) }}</td>
                @if(in_array('col-photo', $columns))
                    <td class="text-center">
                        @if($teacher->photo_url)
                            <img src="{{ $teacher->photo_url }}" alt="" class="teacher-print-photo">
                        @else
                            <span class="teacher-print-photo-placeholder">N/A</span>
                        @endif
                    </td>
                @endif
                @if(in_array('col-name-bn', $columns)) <td class="text-left">{{ $teacher->full_name_bn ?: '—' }}</td> @endif
                @if(in_array('col-name-en', $columns)) <td class="text-left">{{ $teacher->full_name ?: '—' }}</td> @endif
                
                @if(in_array('col-father-bn', $columns)) <td class="text-left">{{ $teacher->father_name_bn ?: '—' }}</td> @endif
                @if(in_array('col-father-en', $columns)) <td class="text-left">{{ $teacher->father_name_en ?: '—' }}</td> @endif
                @if(in_array('col-mother-bn', $columns)) <td class="text-left">{{ $teacher->mother_name_bn ?: '—' }}</td> @endif
                @if(in_array('col-mother-en', $columns)) <td class="text-left">{{ $teacher->mother_name_en ?: '—' }}</td> @endif
                
                @if(in_array('col-designation', $columns)) <td class="text-left">{{ $teacher->designation ?? '—' }}</td> @endif
                @if(in_array('col-mobile', $columns)) <td class="text-center">{{ bnNum($teacher->phone, $isBn) ?: '—' }}</td> @endif
                @if(in_array('col-dob', $columns)) <td class="text-center">{{ $teacher->date_of_birth ? bnNum($teacher->date_of_birth->format('d/m/Y'), $isBn) : '—' }}</td> @endif
                @if(in_array('col-join-date', $columns)) <td class="text-center">{{ $teacher->joining_date ? bnNum($teacher->joining_date->format('d/m/Y'), $isBn) : '—' }}</td> @endif
                
                @if(in_array('col-present-addr', $columns)) <td class="text-left">{{ !empty($presentAddr) ? bnNum(implode(', ', $presentAddr), $isBn) : '—' }}</td> @endif
                @if(in_array('col-permanent-addr', $columns)) <td class="text-left">{{ !empty($permanentAddr) ? bnNum(implode(', ', $permanentAddr), $isBn) : '—' }}</td> @endif
                    @if(in_array('col-username', $columns)) <td class="text-left">{{ $teacher->user->username ?? '—' }}</td> @endif
                    @if(in_array('col-password', $columns)) <td class="text-center">{{ $teacher->plain_password ? bnNum($teacher->plain_password, $isBn) : '—' }}</td> @endif
            </tr>
        @empty
            <tr>
                <td colspan="15" class="text-center">কোনো শিক্ষক নেই</td>
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
