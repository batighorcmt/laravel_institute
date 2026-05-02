@extends('layouts.print')

@section('title', $printTitle)

@php
    $bn = [
        'sl' => 'ক্র.নং',
        'subject' => 'বিষয়ের নাম',
        'teacher' => 'শিক্ষকের নাম',
        'total_classes' => 'মোট ক্লাস',
        'entered' => 'এন্ট্রি হয়েছে',
        'missing' => 'এন্ট্রি হয়নি',
        'completed' => 'পড়া হয়েছে',
        'partial' => 'আংশিক',
        'not_done' => 'পড়া হয়নি',
        'absent' => 'অনুপস্থিত',
        'total' => 'সর্বমোট',
    ];
    $en = [
        'sl' => 'SL',
        'subject' => 'Subject Name',
        'teacher' => 'Teacher Name',
        'total_classes' => 'Total Classes',
        'entered' => 'Entries Done',
        'missing' => 'Entries Missing',
        'completed' => 'Completed',
        'partial' => 'Partial',
        'not_done' => 'Not Done',
        'absent' => 'Absent',
        'total' => 'Total',
    ];
    $t = ($lang == 'en') ? $en : $bn;
    
    function num($n, $lang) {
        if ($lang != 'bn') return $n;
        $bn_digits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace(range(0, 9), $bn_digits, $n);
    }
@endphp

@push('print_head')
<style>
    .page-subtitle { font-size: 14px !important; margin-top: 2px !important; font-weight: 500 !important; }
    
    .badge-print { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; min-width: 25px; }
    .badge-total { background-color: #17a2b8 !important; }
    .badge-entered { background-color: #28a745 !important; }
    .badge-missing { background-color: #dc3545 !important; }
    
    /* Header shading */
    thead.bg-light th { background-color: #f8f9fa !important; color: #333 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    /* Status colors for header and cells */
    .bg-success-s { background-color: #e8f5e9 !important; color: #2e7d32 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-warning-s { background-color: #fff8e1 !important; color: #f57f17 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-danger-s { background-color: #ffebee !important; color: #c62828 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-secondary-s { background-color: #f5f5f5 !important; color: #616161 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    .bg-success-h { background-color: #28a745 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-warning-h { background-color: #ffc107 !important; color: black !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-danger-h { background-color: #dc3545 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-secondary-h { background-color: #6c757d !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    .bg-dark { background-color: #343a40 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #dee2e6; padding: 6px 8px; font-size: 14px; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .align-middle { vertical-align: middle; }
    
    tbody tr td { background-color: transparent !important; }
</style>
@endpush

@section('content')
    <table>
        <thead class="bg-light">
            <tr class="text-center">
                <th width="40" rowspan="2" class="align-middle">{{ $t['sl'] }}</th>
                <th class="text-left align-middle" rowspan="2">{{ $t['subject'] }}</th>
                <th class="text-left align-middle" rowspan="2">{{ $t['teacher'] }}</th>
                <th colspan="3">{{ $lang == 'bn' ? 'ক্লাস তথ্য' : 'Class Info' }}</th>
                <th colspan="4">{{ $lang == 'bn' ? 'শিক্ষার্থী পরিসংখ্যান (এন্ট্রিকৃত ক্লাস সমূহের মোট)' : 'Student Stats (Total)' }}</th>
            </tr>
            <tr class="text-center">
                <th>{{ $t['total_classes'] }}</th>
                <th>{{ $t['entered'] }}</th>
                <th>{{ $t['missing'] }}</th>
                <th class="bg-success-h">{{ $t['completed'] }}</th>
                <th class="bg-warning-h">{{ $t['partial'] }}</th>
                <th class="bg-danger-h">{{ $t['not_done'] }}</th>
                <th class="bg-secondary-h">{{ $t['absent'] }}</th>
            </tr>
        </thead>
        <tbody>
            @php($totals = ['routine'=>0, 'entered'=>0, 'missing'=>0, 'comp'=>0, 'part'=>0, 'not'=>0, 'abs'=>0])
            @foreach($reportData as $index => $row)
                @php($totals['routine'] += $row['total_classes'])
                @php($totals['entered'] += $row['entered'])
                @php($totals['missing'] += $row['missing'])
                @php($totals['comp'] += $row['completed_students'])
                @php($totals['part'] += $row['partial_students'])
                @php($totals['not'] += $row['not_done_students'])
                @php($totals['abs'] += $row['absent_students'])
                <tr class="text-center">
                    <td>{{ num($index + 1, $lang) }}</td>
                    <td class="text-left" style="font-weight: bold;">{{ $row['subject'] }}</td>
                    <td class="text-left">{{ $row['teacher'] }}</td>
                    <td><span class="badge-print badge-total">{{ num($row['total_classes'], $lang) }}</span></td>
                    <td><span class="badge-print badge-entered">{{ num($row['entered'], $lang) }}</span></td>
                    <td><span class="badge-print badge-missing">{{ num($row['missing'], $lang) }}</span></td>
                    <td class="bg-success-s" style="font-weight: bold;">{{ num($row['completed_students'], $lang) }}</td>
                    <td class="bg-warning-s" style="font-weight: bold;">{{ num($row['partial_students'], $lang) }}</td>
                    <td class="bg-danger-s" style="font-weight: bold;">{{ num($row['not_done_students'], $lang) }}</td>
                    <td class="bg-secondary-s" style="font-weight: bold;">{{ num($row['absent_students'], $lang) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-dark">
            <tr class="text-center" style="font-weight: bold; color: white;">
                <td colspan="3" class="text-right">{{ $t['total'] }} = </td>
                <td>{{ num($totals['routine'], $lang) }}</td>
                <td>{{ num($totals['entered'], $lang) }}</td>
                <td>{{ num($totals['missing'], $lang) }}</td>
                <td>{{ num($totals['comp'], $lang) }}</td>
                <td>{{ num($totals['part'], $lang) }}</td>
                <td>{{ num($totals['not'], $lang) }}</td>
                <td>{{ num($totals['abs'], $lang) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
