<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মাসিক রিপোর্ট কার্ড — {{ $student->student_name_bn }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ======= RESET & BASE ======= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #1a237e;
            --secondary: #283593;
            --accent:    #e53935;
            --gold:      #f9a825;
            --teal:      #00695c;
            --light-bg:  #e8eaf6;
            --card-bg:   #fff;
            --border:    #c5cae9;
            --text:      #1a1a2e;
            --muted:     #5c6bc0;
        }

        body {
            font-family: 'Hind Siliguri', 'Noto Serif Bengali', sans-serif;
            background: #f0f2f8;
            color: var(--text);
            font-size: 10.5pt;
            line-height: 1.5;
        }

        /* ======= PRINT PAGE SETUP ======= */
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .page { box-shadow: none !important; margin: 0 !important; border-radius: 0 !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }

        /* ======= SCREEN PREVIEW ======= */
        .no-print {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        .no-print button {
            background: #fff;
            color: var(--primary);
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Hind Siliguri', sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .no-print button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }

        /* ======= MAIN PAGE ======= */
        .page {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            margin: 20px auto;
            padding: 12mm 14mm;
            box-shadow: 0 10px 60px rgba(0,0,0,0.25);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative corner accents */
        .page::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 120px; height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, transparent 70%);
            opacity: 0.08;
            border-radius: 0 0 100% 0;
        }
        .page::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0;
            width: 120px; height: 120px;
            background: linear-gradient(315deg, var(--accent) 0%, transparent 70%);
            opacity: 0.08;
            border-radius: 100% 0 0 0;
        }

        /* ======= HEADER ======= */
        .rc-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 4px solid transparent;
            border-image: linear-gradient(to right, var(--primary), var(--gold), var(--accent)) 1;
            position: relative;
        }
        .rc-header .logo-box {
            flex: 0 0 80px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 3px solid var(--gold);
            overflow: hidden;
            background: var(--light-bg);
            box-shadow: 0 4px 12px rgba(26,35,126,0.2);
        }
        .rc-header .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .rc-header .logo-placeholder {
            font-size: 30px;
            color: var(--primary);
        }
        .rc-header .school-info {
            flex: 1;
            text-align: center;
        }
        .rc-header .school-name {
            font-size: 18pt;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }
        .rc-header .school-address {
            font-size: 9.5pt;
            color: #555;
            margin-top: 3px;
        }
        .rc-header .rc-title-badge {
            display: inline-block;
            margin-top: 6px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            padding: 3px 18px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 8px rgba(229,57,53,0.3);
        }

        /* ======= STUDENT INFO CARD ======= */
        .student-card {
            display: flex;
            gap: 14px;
            background: linear-gradient(135deg, #e8eaf6 0%, #e3f2fd 100%);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
            position: relative;
        }
        .student-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, var(--primary), var(--accent));
            border-radius: 10px 0 0 10px;
        }
        .student-photo {
            flex: 0 0 90px;
            width: 90px;
            height: 110px;
            border-radius: 8px;
            overflow: hidden;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-details {
            flex: 1;
        }
        .student-name {
            font-size: 15pt;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px dashed var(--border);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 12px;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            font-size: 9.5pt;
        }
        .info-label {
            font-weight: 600;
            color: var(--secondary);
            min-width: 85px;
            flex-shrink: 0;
        }
        .info-label::after { content: ':'; }
        .info-value {
            color: var(--text);
            flex: 1;
        }
        .info-item.full-width {
            grid-column: 1 / -1;
        }

        /* ======= MONTH SECTION ======= */
        .month-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .month-label {
            background: linear-gradient(135deg, var(--teal), #004d40);
            color: #fff;
            padding: 5px 18px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 3px 8px rgba(0,105,92,0.3);
        }
        .month-underline {
            flex: 1;
            border-bottom: 2px dashed var(--teal);
            height: 1px;
        }
        .month-value-box {
            border: 1.5px solid var(--teal);
            border-radius: 6px;
            padding: 3px 20px;
            min-width: 120px;
            color: var(--teal);
            font-weight: 700;
            font-size: 10pt;
            text-align: center;
        }

        /* ======= ATTENDANCE TABLE ======= */
        .table-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .table-title-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
        }
        .table-title-text {
            font-size: 11pt;
            font-weight: 700;
            color: var(--primary);
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(26,35,126,0.1);
        }
        table.report-table thead tr {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        table.report-table thead th {
            color: #fff;
            font-size: 9.5pt;
            font-weight: 700;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            letter-spacing: 0.2px;
        }
        table.report-table thead th:first-child { text-align: center; }
        table.report-table tbody tr {
            height: 24px;
        }
        table.report-table tbody tr:nth-child(odd) {
            background: #f3f4fb;
        }
        table.report-table tbody tr:nth-child(even) {
            background: #fff;
        }
        table.report-table tbody td {
            border: 1px solid #c5cae9;
            padding: 3px 6px;
            font-size: 9pt;
            height: 24px;
        }
        table.report-table tbody td:first-child {
            text-align: center;
            font-weight: 700;
            color: var(--muted);
            background: linear-gradient(135deg, #e8eaf6, #ede7f6);
        }

        /* Column widths */
        .col-serial   { width: 8%; }
        .col-date     { width: 14%; }
        .col-arrival  { width: 16%; }
        .col-departure{ width: 16%; }
        .col-remark   { width: 28%; }
        .col-sign     { width: 18%; }

        /* ======= FOOTER / SIGNATURES ======= */
        .rc-footer {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sign-block {
            text-align: center;
            width: 130px;
        }
        .sign-line {
            border-top: 1.5px solid var(--primary);
            padding-top: 5px;
            font-size: 9pt;
            font-weight: 600;
            color: var(--secondary);
        }
        .sign-space {
            height: 36px;
        }

        /* ======= DECORATIVE STRIPE ======= */
        .color-stripe {
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--gold), var(--accent), var(--teal));
            border-radius: 3px;
            margin-bottom: 10px;
        }
        .color-stripe-footer {
            height: 4px;
            background: linear-gradient(to right, var(--teal), var(--gold), var(--primary));
            border-radius: 3px;
            margin-top: 14px;
        }
    </style>
</head>
<body>

{{-- Screen-only print button --}}
<div class="no-print">
    <button onclick="window.print()">
        <i class="fas fa-print" style="margin-right:6px;"></i> প্রিন্ট করুন
    </button>
</div>

<div class="page">

    {{-- Top color stripe --}}
    <div class="color-stripe"></div>

    {{-- ===== HEADER ===== --}}
    <div class="rc-header">
        <div class="logo-box">
            @if($school->logo)
                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name_bn }} logo">
            @else
                <span class="logo-placeholder"><i class="fas fa-school"></i></span>
            @endif
        </div>
        <div class="school-info">
            <div class="school-name">{{ $school->name_bn ?? $school->name }}</div>
            <div class="school-address">{{ $school->address_bn ?? $school->address }}</div>
            <span class="rc-title-badge">মাসিক রিপোর্ট কার্ড</span>
        </div>
    </div>

    {{-- ===== STUDENT INFO CARD ===== --}}
    @php
        $className = $student->currentEnrollment?->class?->bangla_name ?? $student->currentEnrollment?->class?->name ?? 'N/A';
        $sectionName = $student->currentEnrollment?->section?->bangla_name ?? $student->currentEnrollment?->section?->name;
        $classSection = $className;
        if ($sectionName) {
            $classSection .= ' (' . $sectionName . ')';
        }
    @endphp
    <div class="student-card">
        <div class="student-photo">
            <img src="{{ $student->photo_url }}" alt="{{ $student->student_name_bn }}">
        </div>
        <div class="student-details">
            <div class="student-name" style="margin-top: 5px; margin-bottom: 8px; border-bottom: 1px dashed var(--border); padding-bottom: 6px;">{{ $student->student_name_bn ?: $student->student_name_en }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">রোল নম্বর</span>
                    <span class="info-value">{{ $student->currentEnrollment->roll_no ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">শ্রেণি</span>
                    <span class="info-value">{{ $classSection }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">প্রশিক্ষক</span>
                    <span class="info-value">{{ $team->instructor_name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">দলের নাম</span>
                    <span class="info-value">{{ $team->name }}</span>
                </div>
                @php
                    $presentAddress = collect([
                        $student->present_village,
                        $student->present_post_office,
                        $student->present_upazilla,
                        $student->present_district,
                    ])->filter()->implode(', ');
                @endphp
                <div class="info-item full-width">
                    <span class="info-label">বর্তমান ঠিকানা</span>
                    <span class="info-value">{{ $presentAddress ?: 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MONTH SECTION ===== --}}
    <div class="month-section">
        <span class="month-label"><i class="fas fa-calendar-alt" style="margin-right:5px;"></i>মাসের নাম</span>
        <div class="month-underline"></div>
        <div class="month-value-box">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
    </div>

    {{-- ===== ATTENDANCE TABLE ===== --}}
    <div class="table-title">
        <div class="table-title-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="table-title-text">উপস্থিতির বিবরণ</div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th class="col-serial">ক্রমিক</th>
                <th class="col-date">তারিখ</th>
                <th class="col-arrival">আগমন সময়</th>
                <th class="col-departure">প্রস্থান সময়</th>
                <th class="col-remark">মন্তব্য</th>
                <th class="col-sign">প্রশিক্ষকের স্বাক্ষর</th>
            </tr>
        </thead>
        <tbody>
            @for($row = 1; $row <= 20; $row++)
            <tr>
                <td>{{ $row }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- ===== FOOTER SIGNATURES ===== --}}
    <div class="rc-footer">
        <div class="sign-block">
            <div class="sign-space"></div>
            <div class="sign-line">শিক্ষার্থীর স্বাক্ষর</div>
        </div>
        <div class="sign-block">
            <div class="sign-space"></div>
            <div class="sign-line">প্রশিক্ষকের স্বাক্ষর</div>
        </div>
        <div class="sign-block">
            <div class="sign-space"></div>
            <div class="sign-line">প্রতিষ্ঠান প্রধানের স্বাক্ষর</div>
        </div>
    </div>

    {{-- Bottom color stripe --}}
    <div class="color-stripe-footer"></div>
</div>

<script>
    // Auto print when opened in a new tab
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 500);
    });
</script>
</body>
</html>
