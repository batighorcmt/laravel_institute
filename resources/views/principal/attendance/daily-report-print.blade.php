@php
    $printTitle = 'শিক্ষার্থী দৈনিক হাজিরা রিপোর্ট';
    $statusLabel = $status === 'present' ? 'উপস্থিত' : ($status === 'late' ? 'দেরী' : ($status === 'absent' ? 'অনুপস্থিত' : 'সকল'));
    $presentCount = $records->where('status', 'present')->count();
    $lateCount    = $records->where('status', 'late')->count();
    $absentCount  = $records->where('status', 'absent')->count();
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>{{ $printTitle }}</title>
    <style>
        body { font-family: 'SolaimanLipi', 'Kalpurush', Arial, sans-serif; font-size: 11pt; margin: 0; padding: 20px; }
        h2, h4 { text-align: center; margin: 2px 0; }
        .meta { text-align: center; margin-bottom: 10px; font-size: 10pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #2c3e50; color: white; padding: 6px 8px; font-size: 10pt; }
        td { padding: 5px 8px; border: 1px solid #ccc; font-size: 10pt; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .badge-present { color: #27ae60; font-weight: bold; }
        .badge-late    { color: #e67e22; font-weight: bold; }
        .badge-absent  { color: #e74c3c; font-weight: bold; }
        .summary { display: flex; gap: 30px; justify-content: center; margin: 8px 0 14px; }
        .summary span { font-weight: bold; }
        .medium-biometric { color: #2980b9; }
        .medium-app       { color: #8e44ad; }
        .medium-web       { color: #7f8c8d; }
        .medium-system    { color: #95a5a6; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <h2>{{ $school->name ?? 'বিদ্যালয়' }}</h2>
    <h4>শিক্ষার্থী দৈনিক হাজিরা রিপোর্ট</h4>
    <div class="meta">
        তারিখ: <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong> &nbsp;|&nbsp;
        শ্রেণি: <strong>{{ $className }}</strong> &nbsp;|&nbsp;
        শাখা: <strong>{{ $sectionName }}</strong> &nbsp;|&nbsp;
        অবস্থা: <strong>{{ $statusLabel }}</strong>
    </div>

    <div class="summary">
        <span class="badge-present">উপস্থিত: {{ $presentCount }}</span>
        <span class="badge-late">দেরী: {{ $lateCount }}</span>
        <span class="badge-absent">অনুপস্থিত: {{ $absentCount }}</span>
        <span>মোট: {{ $records->count() }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>শিক্ষার্থীর নাম</th>
                <th>শিক্ষার্থী আইডি</th>
                <th>শ্রেণি / শাখা</th>
                <th>প্রবেশ সময়</th>
                <th>প্রস্থান সময়</th>
                <th>অবস্থা</th>
                <th>মাধ্যম</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $i => $rec)
            @php
                $student = $rec->student;
                $enrollment = $student?->currentEnrollment;
                $cn = $enrollment?->class?->name ?? '—';
                $sn = $enrollment?->section?->name ?? '—';
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $student?->student_name_bn ?? $student?->student_name_en ?? '—' }}</td>
                <td>{{ $student?->student_id ?? '—' }}</td>
                <td>{{ $cn }} / {{ $sn }}</td>
                <td>{{ $rec->entry_time ? \Carbon\Carbon::parse($rec->entry_time)->format('h:i A') : '—' }}</td>
                <td>{{ $rec->exit_time  ? \Carbon\Carbon::parse($rec->exit_time)->format('h:i A')  : '—' }}</td>
                <td class="badge-{{ $rec->status }}">
                    @if($rec->status === 'present') উপস্থিত
                    @elseif($rec->status === 'late') দেরী
                    @elseif($rec->status === 'absent') অনুপস্থিত
                    @else {{ $rec->status }}
                    @endif
                </td>
                <td class="medium-{{ $rec->medium }}">
                    @if($rec->medium === 'biometric') মেশিন
                    @elseif($rec->medium === 'mobile_app') অ্যাপ
                    @elseif($rec->medium === 'system') সিস্টেম
                    @else ওয়েব
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center">কোনো রেকর্ড পাওয়া যায়নি।</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
