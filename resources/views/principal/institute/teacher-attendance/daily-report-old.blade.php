@extends('layouts.print')

@php
    $printTitle = 'শিক্ষক দৈনিক হাজিরা রিপোর্ট';
    $printSubtitle = 'তারিখ: ' . \Carbon\Carbon::parse($date)->format('d/m/Y (l)');
@endphp

@push('print_head')
<style>
    @page { size: A4 portrait; margin: 12mm; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .table th, .table td { border: 1px solid #333; padding: 8px 6px; text-align: left; }
    .table thead th { background-color: #e8e8e8; font-weight: 700; text-align: center; }
    .table tbody td { vertical-align: middle; }
    .text-center { text-align: center; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .badge-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .badge-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .badge-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .badge-secondary { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .btn-link { color: #007bff; text-decoration: none; font-size: 11px; }
    .summary-row { background-color: #f5f5f5; font-weight: 700; }
</style>
@endpush

@section('content')

@if($teachers->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">ক্রমিক</th>
                <th style="width: 25%;">শিক্ষকের নাম</th>
                <th style="width: 12%;">চেক-ইন</th>
                <th style="width: 12%;">চেক-আউট</th>
                <th style="width: 10%;">মোট সময়</th>
                <th style="width: 10%;">স্ট্যাটাস</th>
                <th style="width: 13%; white-space: nowrap;">লোকেশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $teacher)
                @php
                    $attendance = $teacher->teacherAttendances->first();
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $teacher->full_name }}</td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_in_time)
                            {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_out_time)
                            {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') }}
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance && $attendance->check_in_time && $attendance->check_out_time)
                            @php
                                $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                                $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);
                                $diff = $checkIn->diff($checkOut);
                            @endphp
                            {{ $diff->h }}ঘ {{ $diff->i }}ম
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($attendance)
                            @if($attendance->status === 'present')
                                <span class="badge badge-success">উপস্থিত</span>
                            @elseif($attendance->status === 'late')
                                <span class="badge badge-warning">বিলম্ব</span>
                            @elseif($attendance->status === 'absent')
                                <span class="badge badge-danger">অনুপস্থিত</span>
                            @else
                                <span class="badge badge-secondary">হাফ ডে</span>
                            @endif
                        @else
                            <span class="badge badge-secondary">নেই</span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space: nowrap; font-size: 11px;">
                        @if($attendance && $attendance->check_in_latitude && $attendance->check_in_longitude)
                            <a href="https://www.google.com/maps?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" 
                               target="_blank" 
                               class="btn-link"
                               title="চেক-ইন লোকেশন">ইন</a>
                        @endif
                        @if($attendance && $attendance->check_out_latitude && $attendance->check_out_longitude)
                            | <a href="https://www.google.com/maps?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" 
                               target="_blank" 
                               class="btn-link"
                               title="চেক-আউট লোকেশন">আউট</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <td colspan="6" style="text-align: right; padding-right: 15px;">সারসংক্ষেপ:</td>
                <td class="text-center">
                    <strong>উপস্থিত:</strong> {{ $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'present')->count() }} |
                    <strong>বিলম্ব:</strong> {{ $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'late')->count() }} |
                    <strong>অনুপস্থিত:</strong> {{ $teachers->filter(fn($t) => !$t->teacherAttendances->first())->count() }}
                </td>
            </tr>
        </tfoot>
    </table>
@else
    <p style="text-align: center; padding: 20px; color: #666;">এই প্রতিষ্ঠানে কোনো শিক্ষক নেই।</p>
@endif

@endsection
