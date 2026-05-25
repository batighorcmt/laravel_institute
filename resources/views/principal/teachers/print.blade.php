@extends('layouts.print')

@section('title', $printTitle ?? 'শিক্ষক তালিকা')

@section('content')
<style>
    .teacher-print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin-top: 8px;
    }
    .teacher-print-table th,
    .teacher-print-table td {
        border: 1px solid #333;
        padding: 6px 8px;
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
        width: 36px;
        height: 36px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    .teacher-print-photo-placeholder {
        width: 36px;
        height: 36px;
        border: 1px dashed #999;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #666;
    }
</style>

<table class="teacher-print-table">
    <thead>
        <tr>
            <th style="width:4%">ক্রমিক</th>
            <th style="width:6%">ছবি</th>
            <th style="width:18%">নাম (বাংলা)</th>
            <th style="width:18%">নাম (ইংরেজি)</th>
            <th style="width:8%">Initials</th>
            <th style="width:14%">পদবী</th>
            <th style="width:12%">মোবাইল</th>
            <th style="width:14%">ইমেইল</th>
            <th style="width:6%">স্ট্যাটাস</th>
        </tr>
    </thead>
    <tbody>
        @forelse($teachers as $teacher)
            <tr>
                <td class="text-center">{{ $teacher->serial_number ?? $loop->iteration }}</td>
                <td class="text-center">
                    @if($teacher->photo_url)
                        <img src="{{ $teacher->photo_url }}" alt="" class="teacher-print-photo">
                    @else
                        <span class="teacher-print-photo-placeholder">N/A</span>
                    @endif
                </td>
                <td class="text-left">{{ $teacher->full_name_bn ?: '—' }}</td>
                <td class="text-left">{{ $teacher->full_name ?: '—' }}</td>
                <td class="text-center">{{ $teacher->initials ?? '—' }}</td>
                <td class="text-left">{{ $teacher->designation ?? '—' }}</td>
                <td class="text-center">{{ $teacher->phone ?? '—' }}</td>
                <td class="text-left">{{ $teacher->user?->email ?? '—' }}</td>
                <td class="text-center">{{ $teacher->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">কোনো শিক্ষক নেই</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
