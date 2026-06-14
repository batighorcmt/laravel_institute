@extends('layouts.admin')

@section('title', 'রুটিন ভিত্তিক রিপোর্ট')

@push('styles')
<style>
    /* ===== Date Picker Bar ===== */
    .rwr-datepicker-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
        padding: 14px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        margin-bottom: 20px;
    }
    .rwr-datepicker-bar label {
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .rwr-date-input {
        border: 2px solid rgba(255,255,255,0.4);
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 1rem;
        color: #1e3a5f;
        background: #fff;
        font-weight: 600;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        min-width: 170px;
    }
    .rwr-date-input:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255,215,0,0.3);
    }
    .rwr-day-badge {
        background: rgba(255,255,255,0.2);
        color: #fff;
        border-radius: 20px;
        padding: 5px 16px;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        border: 1px solid rgba(255,255,255,0.35);
    }

    /* ===== Table Styles ===== */
    .rwr-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    .rwr-table {
        width: 100%;
        min-width: 800px;
        border-collapse: collapse;
        background: #fff;
        font-size: 0.88rem;
    }
    .rwr-table thead tr:first-child {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
        color: #fff;
    }
    .rwr-table thead tr:first-child th {
        padding: 10px 12px;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.2);
        white-space: nowrap;
    }
    .rwr-table thead tr:first-child th.th-teacher {
        text-align: left;
        padding-left: 14px;
    }
    .period-header {
        background: rgba(255,255,255,0.12);
        min-width: 120px;
    }
    .rwr-table tbody tr {
        transition: background 0.15s;
        border-bottom: 1px solid #e8edf3;
    }
    .rwr-table tbody tr:hover {
        background: #f0f6ff;
    }
    .rwr-table tbody tr:nth-child(even) {
        background: #f8fafd;
    }
    .rwr-table tbody tr:nth-child(even):hover {
        background: #e8f0fb;
    }
    .td-teacher {
        padding: 10px 14px;
        font-weight: 600;
        color: #1e3a5f;
        border-right: 2px solid #d0dae8;
        white-space: nowrap;
        min-width: 180px;
        border-bottom: 1px solid #e0e8f0;
    }
    .td-teacher small {
        display: block;
        color: #5a7a9e;
        font-weight: 400;
        font-size: 0.78rem;
        margin-top: 1px;
    }
    .td-period {
        padding: 8px 10px;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #dde5ef;
        min-width: 120px;
        position: relative;
    }
    /* Routine cell content */
    .routine-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .routine-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 2px;
    }
    .routine-subject {
        font-weight: 700;
        color: #0a1c35;
        font-size: 0.90rem;
        line-height: 1.2;
    }
    .routine-meta {
        font-weight: 600;
        font-size: 0.82rem;
        color: #1a2b42;
        line-height: 1.2;
    }
    /* Status marks */
    .eval-status {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .tick-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #20c755, #16a34a);
        box-shadow: 0 2px 8px rgba(22,163,74,0.4);
        animation: popIn 0.3s ease;
    }
    .tick-mark svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: #fff;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .cross-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f5405a, #c0152d);
        box-shadow: 0 2px 8px rgba(192,21,45,0.35);
    }
    .cross-mark svg {
        width: 15px;
        height: 15px;
        fill: none;
        stroke: #fff;
        stroke-width: 3;
        stroke-linecap: round;
    }
    @keyframes popIn {
        0% { transform: scale(0.6); opacity: 0; }
        70% { transform: scale(1.15); }
        100% { transform: scale(1); opacity: 1; }
    }
    /* Empty period cell */
    .td-empty {
        background: repeating-linear-gradient(
            45deg,
            #f8fafd,
            #f8fafd 5px,
            #f2f5fa 5px,
            #f2f5fa 10px
        );
        color: #bcc8d8;
        font-size: 0.8rem;
        text-align: center;
        vertical-align: middle;
    }

    /* ===== Legend ===== */
    .rwr-legend {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 10px 16px;
        background: #f8fafd;
        border-radius: 8px;
        border: 1px solid #e0e8f0;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.88rem;
        color: #3a4a5e;
    }

    /* ===== Stats bar ===== */
    .rwr-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .stat-card {
        background: #fff;
        border-radius: 8px;
        padding: 10px 18px;
        box-shadow: 0 1px 8px rgba(0,0,0,0.07);
        border-left: 4px solid #2d6a9f;
        min-width: 130px;
    }
    .stat-card.green { border-left-color: #16a34a; }
    .stat-card.red   { border-left-color: #c0152d; }
    .stat-label { font-size: 0.75rem; color: #6b7c93; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: #1e3a5f; line-height: 1.2; }
    .stat-card.green .stat-value { color: #16a34a; }
    .stat-card.red   .stat-value { color: #c0152d; }

    /* ===== No data ===== */
    .rwr-empty {
        text-align: center;
        padding: 60px 20px;
        color: #7a8fa6;
    }
    .rwr-empty i { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: 0.35; }
    .rwr-empty h5 { font-weight: 700; color: #3a4a5e; }
</style>
@endpush

@section('content')
<div class="card" style="border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div class="card-header" style="background:linear-gradient(135deg,#1e3a5f,#2d6a9f); padding:14px 20px; border-bottom:none;">
        <h3 class="card-title" style="color:#fff; font-size:1.1rem; font-weight:700; margin:0;">
            <i class="fas fa-calendar-alt mr-2"></i>রুটিন ভিত্তিক ইভ্যালুয়েশন রিপোর্ট
        </h3>
    </div>
    <div class="card-body" style="padding:20px;">

        {{-- Date Picker Bar --}}
        <div class="rwr-datepicker-bar">
            <label for="rwr_date_input"><i class="fas fa-calendar-day mr-1"></i> তারিখ নির্বাচন করুন:</label>
            <input
                type="date"
                id="rwr_date_input"
                class="rwr-date-input"
                value="{{ $selectedDate }}"
                max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
            >
            @if($maxPeriod > 0 || $activeTeachers->isNotEmpty())
                <span class="rwr-day-badge">
                    <i class="fas fa-sun mr-1"></i>{{ $dayNameBn }} &nbsp;|&nbsp; {{ \Carbon\Carbon::parse($selectedDate)->format('d M, Y') }}
                </span>
            @else
                <span class="rwr-day-badge">
                    <i class="fas fa-sun mr-1"></i>{{ $dayNameBn }}
                </span>
            @endif
            @if($activeTeachers->isNotEmpty() && $maxPeriod > 0)
            <a href="{{ route('principal.institute.lesson-evaluations.routine-wise-report-print', [$school, 'date' => $selectedDate, 'lang' => 'bn']) }}"
               target="_blank"
               style="margin-left:auto; display:inline-flex; align-items:center; gap:6px; background:#fff; color:#1e3a5f; border:none; border-radius:7px; padding:7px 16px; font-weight:700; font-size:0.9rem; cursor:pointer; text-decoration:none; box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:background 0.15s, box-shadow 0.15s;"
               onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#fff'">
                <i class="fas fa-print"></i> প্রিন্ট করুন
            </a>
            @endif
        </div>

        @php
            $totalRoutine  = $routineEntries->count();
            $totalEvaled   = $evaluations->count();
            $totalMissing  = max(0, $totalRoutine - $totalEvaled);

            // Bangla number helper
            $toBn = function($n) {
                $d = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
                return strtr((string)$n, $d);
            };
        @endphp

        @if($activeTeachers->isNotEmpty() && $maxPeriod > 0)

        {{-- Stats Cards --}}
        <div class="rwr-stats">
            <div class="stat-card">
                <div class="stat-label">মোট পিরিওড</div>
                <div class="stat-value">{{ $toBn($totalRoutine) }}</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">ইভ্যালুয়েশন হয়েছে</div>
                <div class="stat-value">{{ $toBn($totalEvaled) }}</div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">ইভ্যালুয়েশন হয়নি</div>
                <div class="stat-value">{{ $toBn($totalMissing) }}</div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="rwr-legend">
            <span style="font-weight:700; color:#1e3a5f; margin-right:4px;">চিহ্নিত করণ:</span>
            <span class="legend-item">
                <span class="tick-mark" style="width:22px;height:22px;">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                ইভ্যালুয়েশন এন্ট্রি হয়েছে
            </span>
            <span class="legend-item">
                <span class="cross-mark" style="width:22px;height:22px;">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </span>
                ইভ্যালুয়েশন এন্ট্রি হয়নি
            </span>
            <span class="legend-item">
                <span style="display:inline-block;width:22px;height:14px;background:repeating-linear-gradient(45deg,#f2f5fa,#f2f5fa 3px,#e8eef5 3px,#e8eef5 6px);border-radius:3px;border:1px solid #dde5ef;"></span>
                এই পিরিওডে ক্লাস নেই
            </span>
        </div>

        {{-- Routine Table --}}
        <div class="rwr-wrapper">
            <table class="rwr-table" id="rwrTable">
                <thead>
                    <tr>
                        <th class="th-teacher" style="min-width:190px; text-align:left;">
                            <i class="fas fa-chalkboard-teacher mr-1"></i>শিক্ষক
                        </th>
                        @for($p = 1; $p <= $maxPeriod; $p++)
                            <th class="period-header">পিরিওড {{ $toBn($p) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeTeachers as $teacher)
                        @php
                            $teacherName   = $teacher->full_name_bn ?: ($teacher->full_name ?? optional($teacher->user)->name ?? 'N/A');
                            $initials      = $teacher->initials ? " [{$teacher->initials}]" : '';
                            $designation   = $teacher->designation ?? '';
                        @endphp
                        <tr>
                            <td class="td-teacher">
                                {{ $teacherName }}{{ $initials }}
                                @if($designation)
                                    <small>{{ $designation }}</small>
                                @endif
                            </td>
                            @for($p = 1; $p <= $maxPeriod; $p++)
                                @php
                                    $cellKey = $teacher->id . '#' . $p;
                                    $cellEntries = $routineGrid->get($cellKey, collect());
                                @endphp
                                @if($cellEntries->isNotEmpty())
                                    @php
                                        $entry        = $cellEntries->first();
                                        $subjectName  = $entry->subject?->bangla_name ?: $entry->subject?->name ?? '?';
                                        $className    = $entry->class?->bangla_name ?: $entry->class?->name ?? '?';
                                        $sectionName  = $entry->section?->bangla_name ?: $entry->section?->name ?? '';

                                        $evalKey      = 're#' . $entry->id; // by routine_entry_id
                                        $evalKey2     = $teacher->id . '#' . $entry->class_id . '#' . $entry->section_id . '#' . $entry->subject_id;

                                        $hasEval      = isset($evalLookup[$evalKey]) || isset($evalLookup[$evalKey2]);
                                    @endphp
                                    <td class="td-period">
                                        <div class="routine-cell">
                                            <div class="routine-info">
                                                <span class="routine-subject">{{ $subjectName }}</span>
                                                <span class="routine-meta">{{ $className }}{{ $sectionName ? ' - '.$sectionName : '' }}</span>
                                            </div>
                                            <div class="eval-status">
                                                @if($hasEval)
                                                    <span class="tick-mark" title="ইভ্যালুয়েশন এন্ট্রি হয়েছে">
                                                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                                    </span>
                                                @else
                                                    <span class="cross-mark" title="ইভ্যালুয়েশন এন্ট্রি হয়নি">
                                                        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td class="td-period td-empty">—</td>
                                @endif
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>{{-- end rwr-wrapper --}}

        @else
        {{-- No data state --}}
        <div class="rwr-empty">
            <i class="fas fa-calendar-times"></i>
            <h5>{{ $dayNameBn }}-এ কোনো রুটিন পাওয়া যায়নি</h5>
            <p style="font-size:0.93rem;">এই তারিখের জন্য রুটিনে কোনো ক্লাস নির্ধারণ করা নেই।<br>অন্য তারিখ নির্বাচন করুন।</p>
        </div>
        @endif

    </div>{{-- end card-body --}}
</div>{{-- end card --}}
@endsection

@push('scripts')
<script>
(function () {
    const dateInput = document.getElementById('rwr_date_input');
    if (!dateInput) return;

    dateInput.addEventListener('change', function () {
        const val = this.value;
        if (!val) return;
        // Redirect with new date
        const url = new URL(window.location.href);
        url.searchParams.set('date', val);
        window.location.href = url.toString();
    });
})();
</script>
@endpush
