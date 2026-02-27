@extends('layouts.print')

@php
    $lang = request('lang', 'bn');
    $printTitle = $lang === 'bn' ? 'ফলাফল পত্র' : 'Result Sheet';
    $printSubtitle = ($lang === 'bn' ? 'পরীক্ষা: ' : 'Exam: ') .
                     ($lang === 'bn' ? $exam->name : ($exam->name_en ?? $exam->name)) .
                     ' | ' . ($lang === 'bn' ? 'শ্রেণি: ' : 'Class: ') .
                     ($lang === 'bn' ? $class->name : ($class->name_en ?? $class->name));

    // Separate passed and failed results
    $passedResults = $results->filter(function($r) {
        return $r->computed_status !== 'অকৃতকার্য' && $r->computed_letter !== 'F';
    })->values();

    $failedResults = $results->filter(function($r) {
        return $r->computed_status === 'অকৃতকার্য' || $r->computed_letter === 'F';
    })->values();

    // Helper to convert English digits to Bangla digits when needed
    if (! function_exists('toBanglaNumber')) {
        function toBanglaNumber($value) {
            $eng = ['0','1','2','3','4','5','6','7','8','9'];
            $bn  = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($eng, $bn, (string) $value);
        }
    }

    $decimal = \App\Models\Setting::getDecimalPosition($school->id);
@endphp

@section('title', $printTitle)

@push('print_head')
<style>
    .settings-panel {
        position: fixed;
        left: 0;
        top: 120px;
        width: 240px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 12px;
        z-index: 1000;
        max-height: 60vh;
        overflow-y: auto;
        backdrop-filter: blur(4px);
    }

    .settings-icon {
        position: fixed;
        left: 10px;
        top: 130px;
        width: 40px;
        height: 40px;
        background: #007bff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        color: white;
        font-size: 18px;
        transition: all 0.3s ease;
    }

    .settings-icon:hover {
        background: #0056b3;
        transform: scale(1.1);
    }

    .settings-icon.active {
        background: #dc3545;
    }

    .settings-panel h3 {
        margin: 0 0 12px 0;
        font-size: 13px;
        font-weight: 700;
        border-bottom: 2px solid #007bff;
        padding-bottom: 6px;
    }

    .checkbox-group {
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #007bff;
    }

    .checkbox-group label {
        margin: 0;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.2;
        flex: 1;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    thead {
        background-color: #f0f0f0;
        font-weight: 700;
    }

    th, td {
        border: 1px solid #333;
        padding: 8px;
        text-align: center;
        font-size: 14px; /* increased font size */
    }

    th {
        font-weight: 700;
        font-style: bold;
    }

    td.text-left {
        text-align: left;
        padding-left: 10px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 12px;
    }

    /* Footer/signature area: bring signature lines closer to labels */
    .footer-section {
        margin-top: 48px; /* reduce space above signatures */
        display: flex;
        justify-content: space-between;
        padding-top: 6px; /* small padding so border is close */
    }

    .signature-box {
        text-align: center;
        width: 220px;
        border-top: 1px solid #000;
        padding-top: 4px; /* bring label as close as possible to the line */
        margin-top: 0;
        font-size: 12px;
    }

    /* Hide global site footer elements on this page */
    footer, .site-footer, .app-footer, .developer-credit, .powered-by {
        display: none !important;
    }

    .result-pass {
        color: #28a745;
        font-weight: 600;
    }

    .result-fail {
        color: #dc3545;
        font-weight: 600;
    }

    .hidden-column {
        display: none;
    }

    /* Keep the TD visible but hide the number inside when class-pos for failed students
       should remain blank. Use visibility on the inner .class-pos so cell borders
       remain and an empty cell is shown. */
    .hidden-class-pos-failed .class-pos {
        visibility: hidden;
    }

    .row-hidden {
        display: none;
    }

    @media print {
        .settings-panel, .settings-icon, .no-print {
            display: none !important;
        }

        body {
            padding: 0;
        }
        /* Hide global site footer elements that may come from layout */
        footer, .site-footer, .app-footer, .developer-credit, .powered-by {
            display: none !important;
        }
    }
</style>

<script class="no-print">
document.addEventListener('DOMContentLoaded', function() {
    const settingsToggle = document.getElementById('settingsToggle');
    const settingsPanel = document.getElementById('settingsPanel');
    const showFailedCheckbox = document.getElementById('showFailed');
    const showFailedClassPosCheckbox = document.getElementById('showFailedClassPos');
    const showSectionCheckbox = document.getElementById('showSectionPos');
    const resultTable = document.getElementById('resultTable');
    const lang = '{{ $lang }}';

    function engToBn(str) {
        return String(str).replace(/\d/g, function(d) {
            return ['০','১','২','৩','৪','৫','৬','৭','৮','৯'][d];
        });
    }

    function formatNumberForLang(n) {
        if (lang === 'bn') return engToBn(n);
        return String(n);
    }

    if (!settingsToggle || !settingsPanel) return;

    // Toggle settings panel
    settingsToggle.addEventListener('click', function(e) {
        e.preventDefault();
        const isHidden = settingsPanel.style.display === 'none' || settingsPanel.style.display === '';
        settingsPanel.style.display = isHidden ? 'block' : 'none';
        settingsToggle.classList.toggle('active', isHidden);
    });

    // Close settings panel when clicking outside
    document.addEventListener('click', function(e) {
        const isSettingsPanel = settingsPanel.contains(e.target);
        const isSettingsIcon = settingsToggle.contains(e.target);

        if (!isSettingsPanel && !isSettingsIcon && settingsPanel.style.display === 'block') {
            settingsPanel.style.display = 'none';
            settingsToggle.classList.remove('active');
        }
    });

    // Handle failed position checkbox
    showFailedCheckbox.addEventListener('change', function() {
        const failedRows = document.querySelectorAll('.row-failed');
        failedRows.forEach(row => {
            if (this.checked) {
                row.classList.remove('row-hidden');
            } else {
                row.classList.add('row-hidden');
            }
        });

        // When showing failed rows, also show their class position if the checkbox is checked
        const failedClassPosCells = document.querySelectorAll('.row-failed .class-pos-cell');
        if (this.checked && showFailedClassPosCheckbox.checked) {
            failedClassPosCells.forEach(cell => {
                cell.classList.remove('hidden-class-pos-failed');
            });
        } else if (!this.checked) {
            // When hiding failed rows, hide their class position cells
            failedClassPosCells.forEach(cell => {
                cell.classList.add('hidden-class-pos-failed');
            });
        }

        // Update all positions
        updatePositions();
    });

    // Handle failed class position checkbox
    showFailedClassPosCheckbox.addEventListener('change', function() {
        const failedClassPosCells = document.querySelectorAll('.row-failed .class-pos-cell');
        if (this.checked && showFailedCheckbox.checked) {
            // Show failed students' class positions
            failedClassPosCells.forEach(cell => {
                cell.classList.remove('hidden-class-pos-failed');
            });
        } else {
            // Hide them
            failedClassPosCells.forEach(cell => {
                cell.classList.add('hidden-class-pos-failed');
            });
        }

        // Update positions to recalculate class positions
        updatePositions();
    });

    // Handle section position checkbox
    showSectionCheckbox.addEventListener('change', function() {
        const sectionColumns = document.querySelectorAll('.section-position-column');
        sectionColumns.forEach(col => {
            col.classList.toggle('hidden-column');
        });

        if (this.checked) {
            updateSectionPositions();
        }
    });

    function isRowVisible(row) {
        return row.style.display !== 'none' && !row.classList.contains('row-hidden');
    }

    function updateSectionPositions() {
        const allRows = Array.from(document.querySelectorAll('#resultTable tbody tr'));
        const visibleRows = allRows.filter(r => isRowVisible(r));
        const sections = {};

        visibleRows.forEach(row => {
            const sectionPos = row.querySelector('.section-pos');
            const section = sectionPos ? sectionPos.dataset.section : '';

            if (!sections[section]) {
                sections[section] = [];
            }
            sections[section].push(row);
        });

        // Update positions per section
        for (let section in sections) {
            sections[section].forEach((row, index) => {
                const sectionPos = row.querySelector('.section-pos');
                if (sectionPos) {
                    sectionPos.textContent = formatNumberForLang(index + 1);
                }
            });
        }
    }

    function updateClassPositions() {
        // Get ALL rows
        const allRows = Array.from(document.querySelectorAll('#resultTable tbody tr'));
        let position = 1;

        allRows.forEach((row) => {
            const classPos = row.querySelector('.class-pos');
            if (classPos && isRowVisible(row)) {
                classPos.textContent = formatNumberForLang(position);
                position++;
            }
        });
    }

    function updatePositions() {
        const allRows = Array.from(document.querySelectorAll('#resultTable tbody tr'));
        const visibleRows = allRows.filter(r => isRowVisible(r));
        const passedRows = visibleRows.filter(r => r.classList.contains('row-passed'));
        const failedRows = visibleRows.filter(r => r.classList.contains('row-failed'));

        // Update SL (first column)
        passedRows.forEach((row, index) => {
            row.querySelector('td').textContent = formatNumberForLang(index + 1);
        });

        failedRows.forEach((row, index) => {
            row.querySelector('td').textContent = formatNumberForLang(passedRows.length + index + 1);
        });

        // Update class positions
        updateClassPositions();

        // Update section positions if enabled
        if (showSectionCheckbox.checked) {
            updateSectionPositions();
        }
    }

    // Initialize positions on page load
    updateClassPositions();
});

</script>
@endpush

@section('content')
<div class="no-print">
    <button class="settings-icon" id="settingsToggle" title="{{ $lang === 'bn' ? 'সেটিংস' : 'Settings' }}">
        ⚙️
    </button>

    <div class="settings-panel no-print" id="settingsPanel" style="display: none;">
        <h3>{{ $lang === 'bn' ? 'সেটিংস' : 'Settings' }}</h3>

        <div class="checkbox-group">
            <input type="checkbox" id="showFailed" />
            <label for="showFailed">
                {{ $lang === 'bn' ? 'অকৃতকার্য শিক্ষার্থী দেখান' : 'Show Failed Students' }}
            </label>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="showFailedClassPos" />
            <label for="showFailedClassPos">
                {{ $lang === 'bn' ? 'অকৃতকার্যদের শ্রেণিতে অবস্থান' : 'Show Class Pos for Failed' }}
            </label>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="showSectionPos" />
            <label for="showSectionPos">
                {{ $lang === 'bn' ? 'শাখায় অবস্থান' : 'Section Wise Position' }}
            </label>
        </div>
    </div>
</div>

<div class="info-row">
    <div>
        <strong>{{ $lang === 'bn' ? 'পরীক্ষার নাম:' : 'Exam:' }}</strong>
        {{ $lang === 'bn' ? $exam->name : ($exam->name_en ?? $exam->name) }}<br>
        <strong>{{ $lang === 'bn' ? 'শ্রেণি:' : 'Class:' }}</strong>
        {{ $lang === 'bn' ? $class->name : ($class->name_en ?? $class->name) }}
    </div>
    <div style="text-align: right;">
        <strong>{{ $lang === 'bn' ? 'শিক্ষাবর্ষ:' : 'Academic Year:' }}</strong>
        {{ $exam->academicYear->name ?? 'N/A' }}<br>
        <strong>{{ $lang === 'bn' ? 'প্রিন্টের তারিখ:' : 'Print Date:' }}</strong>
        @php
            // Use 12-hour format with AM/PM
            $printDt = now()->format('d/m/Y h:i A');
            if($lang === 'bn') {
                // convert digits to Bangla and AM/PM to Bangla markers
                $printDt = toBanglaNumber($printDt);
                $printDt = str_replace(['AM','PM'], ['এএম','পিএম'], $printDt);
            }
        @endphp
        <span id="printDate">{{ $printDt }}</span>
    </div>
</div>

<table id="resultTable">
    <thead>
        <tr>
            <th style="width: 4%;">{{ $lang === 'bn' ? 'ক্রমিক' : 'SL' }}</th>
            <th style="width: 8%;">{{ $lang === 'bn' ? 'আইডি' : 'ID' }}</th>
            <th class="text-left" style="width: 20%;">{{ $lang === 'bn' ? 'শিক্ষার্থীর নাম' : 'Student Name' }}</th>
            <th style="width: 6%;">{{ $lang === 'bn' ? 'শাখা' : 'Section' }}</th>
            <th style="width: 6%;">{{ $lang === 'bn' ? 'রোল নং' : 'Roll' }}</th>
            <th style="width: 8%;">{{ $lang === 'bn' ? 'মোট নম্বর' : 'Total' }}</th>
            <th style="width: 6%;">{{ $lang === 'bn' ? 'জিপিএ' : 'GPA' }}</th>
            <th style="width: 10%;">{{ $lang === 'bn' ? 'ফলাফল' : 'Status' }}</th>
            <th style="width: 8%;">{{ $lang === 'bn' ? 'শ্রেণিতে অবস্থান' : 'Class Pos' }}</th>
            <th class="section-position-column hidden-column" style="width: 8%;">{{ $lang === 'bn' ? 'শাখায় অবস্থান' : 'Sec Pos' }}</th>
        </tr>
    </thead>
    <tbody>
        {{-- Display passed students first --}}
        @forelse($passedResults as $passed)
            <tr class="row-passed">
                <td>{{ $lang === 'bn' ? toBanglaNumber($loop->iteration) : $loop->iteration }}</td>
                <td>{{ $passed->student->student_id }}</td>
                <td class="text-left">
                    @if($lang === 'bn')
                        {{ $passed->student->student_name_bn ?: $passed->student->student_name_en }}
                    @else
                        {{ $passed->student->student_name_en ?: $passed->student->student_name_bn }}
                    @endif
                </td>
                <td>{{ optional($passed->student->currentEnrollment)->section->name ?? 'N/A' }}</td>
                <td>{{ $lang === 'bn' && optional($passed->student->currentEnrollment)->roll_no ? toBanglaNumber(optional($passed->student->currentEnrollment)->roll_no) : (optional($passed->student->currentEnrollment)->roll_no ?? 'N/A') }}</td>
                <td>{{ $lang === 'bn' ? toBanglaNumber(number_format($passed->computed_total_marks, $decimal, '.', '')) : number_format($passed->computed_total_marks, $decimal, '.', '') }}</td>
                <td>{{ $lang === 'bn' ? toBanglaNumber(number_format($passed->computed_gpa, 2)) : number_format($passed->computed_gpa, 2) }}</td>
                <td>
                    <span class="result-pass">{{ $lang === 'bn' ? 'উত্তীর্ণ' : 'Passed' }}</span>
                </td>
                <td class="class-pos-cell">
                    <span class="class-pos">{{ $lang === 'bn' ? toBanglaNumber($loop->iteration) : $loop->iteration }}</span>
                </td>
                <td class="section-position-column hidden-column section-pos" data-section="{{ optional($passed->student->currentEnrollment)->section_id ?? '' }}">
                    -
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="padding: 20px; text-align: center;">
                    {{ $lang === 'bn' ? 'কোনো ফলাফল পাওয়া যায়নি' : 'No results found' }}
                </td>
            </tr>
        @endforelse

        {{-- Display failed students (initially hidden) --}}
        @foreach($failedResults as $failed)
            <tr class="row-failed row-hidden">
                <td>{{ $lang === 'bn' ? toBanglaNumber($passedResults->count() + $loop->iteration) : $passedResults->count() + $loop->iteration }}</td>
                <td>{{ $failed->student->student_id }}</td>
                <td class="text-left">
                    @if($lang === 'bn')
                        {{ $failed->student->student_name_bn ?: $failed->student->student_name_en }}
                    @else
                        {{ $failed->student->student_name_en ?: $failed->student->student_name_bn }}
                    @endif
                </td>
                <td>{{ optional($failed->student->currentEnrollment)->section->name ?? 'N/A' }}</td>
                <td>{{ $lang === 'bn' && optional($failed->student->currentEnrollment)->roll_no ? toBanglaNumber(optional($failed->student->currentEnrollment)->roll_no) : (optional($failed->student->currentEnrollment)->roll_no ?? 'N/A') }}</td>
                <td>{{ $lang === 'bn' ? toBanglaNumber(number_format($failed->computed_total_marks, $decimal, '.', '')) : number_format($failed->computed_total_marks, $decimal, '.', '') }}</td>
                <td>{{ $lang === 'bn' ? toBanglaNumber(number_format($failed->computed_gpa, 2)) : number_format($failed->computed_gpa, 2) }}</td>
                <td>
                    <span class="result-fail">{{ $lang === 'bn' ? 'অকৃতকার্য' : 'Failed' }}</span>
                    @if($failed->fail_count)
                        ({{ $lang === 'bn' ? toBanglaNumber($failed->fail_count) : $failed->fail_count }})
                    @endif
                </td>
                <td class="class-pos-cell hidden-class-pos-failed">
                    <span class="class-pos">{{ $lang === 'bn' ? toBanglaNumber($passedResults->count() + $loop->iteration) : $passedResults->count() + $loop->iteration }}</span>
                </td>
                <td class="section-position-column hidden-column section-pos" data-section="{{ optional($failed->student->currentEnrollment)->section_id ?? '' }}">
                    -
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer-section">
    <div class="signature-box">
        {{ $lang === 'bn' ? 'শ্রেণি শিক্ষকের স্বাক্ষর' : "Class Teacher's Signature" }}
    </div>
    @if(isset($principalTeacher))
        <div class="signature-box">
            {{ $principalTeacher->name }}<br>
            {{ $lang === 'bn' ? 'অধ্যক্ষ/প্রধান শিক্ষক' : 'Principal/Head Teacher' }}
        </div>
    @else
        <div class="signature-box">
            {{ $lang === 'bn' ? 'অধ্যক্ষ/প্রধান শিক্ষকের স্বাক্ষর' : "Principal's Signature" }}
        </div>
    @endif
</div>

@endsection
