<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পরিসংখ্যান রিপোর্ট — {{ $exam->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #3949ab;
            --primary-dark: #283593;
            --success: #2e7d32;
            --danger: #c62828;
            --info: #0277bd;
            --warning: #f57c00;
        }

        @media print {
            @page { size: auto; margin: 10mm; }
            body { font-size: 9pt; padding: 0; margin: 0; background: #fff !important; color: #000; }
            .no-print, .btn { display: none !important; }
            
            /* Stat cards specific styling for print */
            .stat-cards-row { display: flex !important; flex-wrap: nowrap !important; }
            .stat-cards-row .col-6 { width: 20% !important; flex: 0 0 20% !important; max-width: 20% !important; padding: 0 3px !important; }
            
            /* General row behavior in print - let them be block to avoid overlap on page break */
            .row:not(.stat-cards-row) { display: block !important; width: 100% !important; margin: 0 !important; }
            .row:not(.stat-cards-row) > div[class*="col-"] { width: 100% !important; max-width: 100% !important; flex: none !important; margin-bottom: 10px !important; padding: 0 !important; }

            .page-break { page-break-after: auto; break-after: auto; }
            .stat-card, .chart-box, .panel-box, tr, td, th { page-break-inside: avoid; break-inside: avoid; }
            
            .stat-card { padding: 0.5rem; margin-bottom: 0.5rem; }
            .stat-card h6 { font-size: 0.75rem; margin-bottom: 0.2rem; }
            .stat-card .stat-value { font-size: 1.25rem; }
            .chart-box { height: auto !important; min-height: 250px !important; padding: 0.5rem; margin-bottom: 0.5rem; }
            .section-title { margin: 0.5rem 0 0.25rem; font-size: 1rem; border-bottom: 1px solid var(--primary); padding-bottom: 2px; }
            .table { margin-bottom: 0.5rem; font-size: 8.5pt; }
            .table th, .table td { padding: 0.2rem 0.3rem !important; }
            .screen-header { display: none !important; }
            
            .print-header { display: flex !important; align-items: center; justify-content: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--primary); text-align: left; }
            .print-header-logo { max-height: 70px; margin-right: 15px; }
            .print-header-text h4 { font-size: 1.8rem; font-weight: bold; margin-bottom: 2px; }
            .print-header-text p { font-size: 0.95rem; margin-bottom: 2px; }
            
            /* Reduce space between sections */
            .mb-3, .mb-4 { margin-bottom: 0.5rem !important; }
        }

        body {
            font-family: 'SolaimanLipi', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e8eaf6 0%, #f5f7fa 50%, #e3f2fd 100%);
            min-height: 100vh;
        }

        .screen-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 1.25rem 0;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(57, 73, 171, 0.35);
        }

        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }

        .report-title {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.35rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            font-size: 1.05rem;
        }

        .stat-card {
            border-radius: 10px;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            border: none;
            color: #fff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-card h6 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.95;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-card .stat-label { font-size: 0.8rem; opacity: 0.9; }

        .stat-total { background: linear-gradient(135deg, #5c6bc0, #3949ab); }
        .stat-pass { background: linear-gradient(135deg, #43a047, #2e7d32); }
        .stat-fail { background: linear-gradient(135deg, #e53935, #c62828); }
        .stat-rate { background: linear-gradient(135deg, #039be5, #0277bd); }
        .stat-gpa { background: linear-gradient(135deg, #fb8c00, #ef6c00); }

        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin: 1.25rem 0 0.85rem;
            padding-bottom: 0.4rem;
            border-bottom: 3px solid var(--primary);
            font-size: 1.1rem;
        }

        .chart-box {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            height: 300px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
        }

        .table thead th {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-weight: 600;
            font-size: 0.88rem;
            vertical-align: middle;
        }

        .table tbody tr:nth-child(even) { background: #f8f9ff; }

        .pass-rate-high { color: var(--success); font-weight: 700; }
        .pass-rate-low { color: var(--danger); font-weight: 700; }

        .badge-pass {
            background: var(--success);
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.78rem;
        }

        .badge-fail {
            background: var(--danger);
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.78rem;
        }

        .panel-box {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
            height: 100%;
        }

        .table-light-blue thead th {
            background: #e3f2fd;
            color: #1565c0;
            font-weight: 700;
        }

        .progress-bar-section { background: #1976d2; }
        .progress-bar-group { background: #f57c00; }

        .rolls-cell {
            font-size: 0.85rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .progress-bar-gpa-5 { background: #2e7d32; }
        .progress-bar-gpa-4 { background: #0288d1; }
        .progress-bar-gpa-35 { background: #0097a7; }
        .progress-bar-gpa-3 { background: #fbc02d; color: #333; }
        .progress-bar-gpa-2 { background: #ffa000; }
        .progress-bar-gpa-1 { background: #f57c00; }
        .progress-bar-gpa-0 { background: #c62828; }

        .empty-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            color: #856404;
        }

        .merit-gold { color: #f9a825; }
        .merit-silver { color: #78909c; }
        .merit-bronze { color: #8d6e63; }
    </style>
</head>
<body>
    <div class="screen-header">
        <div class="container text-center">
            <h4 class="mb-1">{{ $instituteName }}</h4>
            @if($instituteAddress)
                <p class="mb-0 opacity-75">{{ $instituteAddress }}</p>
            @endif
            <div class="report-title">
                {{ $exam->name }} — {{ $class->name }} ({{ $yearLabel }}) পরিসংখ্যান রিপোর্ট
            </div>
        </div>
    </div>

    @php
        $logoUrl = null;
        if(isset($school) && $school && $school->logo){
            $candidates = [
                'uploads/schools/'.$school->logo,
                'storage/schools/'.$school->logo,
                'storage/'.$school->logo,
            ];
            foreach($candidates as $c){ 
                if(file_exists(public_path($c))){ 
                    $logoUrl = asset($c); 
                    break;
                } 
            }
        }
    @endphp

    <div class="print-header">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" class="print-header-logo">
        @endif
        <div class="print-header-text">
            <h4>{{ $instituteName }}</h4>
            @if($instituteAddress)<p>{{ $instituteAddress }}</p>@endif
            <p><strong>{{ $exam->name }} — {{ $class->name }} ({{ $yearLabel }}) পরিসংখ্যান রিপোর্ট</strong></p>
        </div>
    </div>

    <div class="container-fluid px-3 px-md-4" style="max-width: 1200px;">
        <div class="row mb-3 no-print">
            <div class="col-12 text-center">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer"></i> প্রিন্ট করুন
                </button>
                <button type="button" onclick="window.close()" class="btn btn-outline-secondary btn-sm ms-2">
                    <i class="bi bi-x-circle"></i> বন্ধ করুন
                </button>
                <a href="{{ route('principal.institute.results.exams', $school) }}" class="btn btn-outline-primary btn-sm ms-2">
                    <i class="bi bi-arrow-left"></i> ফলাফল তালিকা
                </a>
            </div>
        </div>

        @if($totalStudents === 0)
            <div class="empty-notice mb-4">
                <i class="bi bi-exclamation-triangle"></i>
                এই পরীক্ষা ও শ্রেণির জন্য এখনও কোনো মার্ক এন্ট্রি করা হয়নি। মার্ক এন্ট্রি করলেই পরিসংখ্যান স্বয়ংক্রিয়ভাবে তৈরি হবে।
            </div>
        @endif

        {{-- সার্বিক পরিসংখ্যান --}}
        <div class="row g-2 mb-3 stat-cards-row">
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-card stat-total">
                    <h6><i class="bi bi-people-fill"></i> মোট পরীক্ষার্থী</h6>
                    <div class="stat-value">{{ $totalStudents }}</div>
                    <div class="stat-label">জন</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-card stat-pass">
                    <h6><i class="bi bi-check-circle-fill"></i> উত্তীর্ণ</h6>
                    <div class="stat-value">{{ $passedStudents }}</div>
                    <div class="stat-label">জন ({{ $passRate }}%)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-card stat-fail">
                    <h6><i class="bi bi-x-circle-fill"></i> অনুত্তীর্ণ</h6>
                    <div class="stat-value">{{ $failedStudents }}</div>
                    <div class="stat-label">জন ({{ $totalStudents > 0 ? round(100 - $passRate, 2) : 0 }}%)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-card stat-rate">
                    <h6><i class="bi bi-graph-up-arrow"></i> পাসের হার</h6>
                    <div class="stat-value">{{ $passRate }}%</div>
                    <div class="stat-label">সর্বমোট</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="stat-card stat-gpa">
                    <h6><i class="bi bi-star-fill"></i> গড় GPA</h6>
                    <div class="stat-value">{{ number_format($averageGPA, 2) }}</div>
                    <div class="stat-label">উত্তীর্ণদের</div>
                </div>
            </div>
        </div>

        @if($totalStudents > 0)
            {{-- চার্ট --}}
            <div class="row mb-3 page-break">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="chart-box">
                        <canvas id="passFailChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-box">
                        <canvas id="gpaDistributionChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- বিষয়ভিত্তিক (শুধু যে বিষয়ে মার্ক এন্ট্রি হয়েছে) --}}
            @if(count($subjectStats) > 0)
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="section-title"><i class="bi bi-book-half"></i> বিষয়ভিত্তিক পরিসংখ্যান</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>বিষয়ের নাম</th>
                                        <th class="text-center">পূর্ণমান</th>
                                        <th class="text-center">উত্তীর্ণ</th>
                                        <th class="text-center">অনুত্তীর্ণ</th>
                                        <th class="text-center">পাসের হার</th>
                                        <th class="text-center">সর্বোচ্চ</th>
                                        <th class="text-center">সর্বনিম্ন</th>
                                        <th class="text-center">গড় নম্বর</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjectStats as $subject)
                                        <tr>
                                            <td><strong>{{ $subject['name'] }}</strong></td>
                                            <td class="text-center">{{ $subject['full_marks'] }}</td>
                                            <td class="text-center text-success">{{ $subject['passed'] }}</td>
                                            <td class="text-center text-danger">{{ $subject['failed'] }}</td>
                                            <td class="text-center {{ $subject['pass_rate'] < 50 ? 'pass-rate-low' : 'pass-rate-high' }}">
                                                {{ $subject['pass_rate'] }}%
                                            </td>
                                            <td class="text-center">{{ $subject['max_marks'] }}</td>
                                            <td class="text-center">{{ $subject['min_marks'] }}</td>
                                            <td class="text-center">{{ $subject['average'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="empty-notice">
                            <i class="bi bi-info-circle"></i>
                            এখনও কোনো বিষয়ে মার্ক এন্ট্রি করা হয়নি। মার্ক এন্ট্রি করলে বিষয়ভিত্তিক পরিসংখ্যান এখানে দেখা যাবে।
                        </div>
                    </div>
                </div>
            @endif

            {{-- ফেলের সংখ্যা ভিত্তিক সারাংশ --}}
            @if(count($failSummaryBySubjectCount) > 0)
                <div class="row mb-3 page-break">
                    <div class="col-md-5 mb-3 mb-md-0">
                        <h5 class="section-title"><i class="bi bi-exclamation-octagon"></i> ফেলের সংখ্যা ভিত্তিক সারাংশ</h5>
                        <div class="panel-box">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 table-light-blue">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="25%">ফেলকৃত বিষয়</th>
                                            <th class="text-center" width="25%">শিক্ষার্থী</th>
                                            <th>রোল নং</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failSummaryBySubjectCount as $row)
                                            <tr>
                                                <td class="text-center"><strong>{{ $row['fail_count'] }}</strong> টি</td>
                                                <td class="text-center">{{ $row['student_count'] }} জন</td>
                                                <td class="rolls-cell">{{ $row['rolls'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <h5 class="section-title"><i class="bi bi-person-x"></i> শিক্ষার্থীর বিষয়ভিত্তিক ফেলের তালিকা</h5>
                        <div class="panel-box">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 table-light-blue">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="15%">রোল</th>
                                            <th width="30%">নাম</th>
                                            <th class="text-center" width="10%">সংখ্যা</th>
                                            <th>যেসব বিষয়ে ফেল করেছে</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($studentFailures) && count($studentFailures) > 0)
                                            @foreach($studentFailures as $fail)
                                                <tr>
                                                    <td class="text-center"><strong>{{ $fail['roll'] }}</strong></td>
                                                    <td>{{ $fail['name'] }}</td>
                                                    <td class="text-center text-danger"><strong>{{ $fail['fail_count'] }}</strong></td>
                                                    <td class="rolls-cell text-danger">{{ $fail['subjects'] }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="4" class="text-center">কোনো তথ্য নেই</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- শাখা ও গ্রুপভিত্তিক পাস হার --}}
            @if(count($sectionPassRates) > 0 || count($groupPassRates) > 0)
                <div class="row mb-3 page-break">
                    @if(count($sectionPassRates) > 0)
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h5 class="section-title"><i class="bi bi-diagram-3"></i> শাখাভিত্তিক পাস হার</h5>
                            <div class="panel-box">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0 table-light-blue">
                                        <thead>
                                            <tr>
                                                <th>শাখা</th>
                                                <th class="text-center">শিক্ষার্থী</th>
                                                <th class="text-center">পাস</th>
                                                <th class="text-center">পাস হার</th>
                                                <th width="28%">চার্ট</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sectionPassRates as $row)
                                                <tr>
                                                    <td><strong>{{ $row['label'] }}</strong></td>
                                                    <td class="text-center">{{ $row['total'] }}</td>
                                                    <td class="text-center text-success">{{ $row['passed'] }}</td>
                                                    <td class="text-center {{ $row['pass_rate'] < 50 ? 'pass-rate-low' : 'pass-rate-high' }}">{{ $row['pass_rate'] }}%</td>
                                                    <td>
                                                        <div class="progress" style="height: 18px;">
                                                            <div class="progress-bar progress-bar-section" style="width: {{ max($row['pass_rate'], 5) }}%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if(count($groupPassRates) > 0)
                        <div class="col-md-6">
                            <h5 class="section-title"><i class="bi bi-collection"></i> গ্রুপভিত্তিক পাস হার</h5>
                            <div class="panel-box">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0 table-light-blue">
                                        <thead>
                                            <tr>
                                                <th>গ্রুপ</th>
                                                <th class="text-center">শিক্ষার্থী</th>
                                                <th class="text-center">পাস</th>
                                                <th class="text-center">পাস হার</th>
                                                <th width="28%">চার্ট</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groupPassRates as $row)
                                                <tr>
                                                    <td><strong>{{ $row['label'] }}</strong></td>
                                                    <td class="text-center">{{ $row['total'] }}</td>
                                                    <td class="text-center text-success">{{ $row['passed'] }}</td>
                                                    <td class="text-center {{ $row['pass_rate'] < 50 ? 'pass-rate-low' : 'pass-rate-high' }}">{{ $row['pass_rate'] }}%</td>
                                                    <td>
                                                        <div class="progress" style="height: 18px;">
                                                            <div class="progress-bar progress-bar-group" style="width: {{ max($row['pass_rate'], 5) }}%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- GPA বন্টন --}}
            <div class="row mb-3 page-break">
                <div class="col-12">
                    <h5 class="section-title"><i class="bi bi-bar-chart-line"></i> GPA বন্টন</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="22%">GPA রেঞ্জ</th>
                                    <th width="48%">ছাত্র/ছাত্রী সংখ্যা</th>
                                    <th width="30%">শতকরা হার</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $gpaBarClasses = [
                                        '5.00' => 'progress-bar-gpa-5',
                                        '4.00-4.99' => 'progress-bar-gpa-4',
                                        '3.50-3.99' => 'progress-bar-gpa-35',
                                        '3.00-3.49' => 'progress-bar-gpa-3',
                                        '2.00-2.99' => 'progress-bar-gpa-2',
                                        '1.00-1.99' => 'progress-bar-gpa-1',
                                        '0.00' => 'progress-bar-gpa-0',
                                    ];
                                @endphp
                                @foreach($gpaDistribution as $range => $count)
                                    @php $percentage = $totalStudents > 0 ? round(($count / $totalStudents) * 100, 2) : 0; @endphp
                                    <tr>
                                        <td><strong>{{ $range }}</strong></td>
                                        <td>
                                            <div class="progress" style="height: 22px;">
                                                <div class="progress-bar {{ $gpaBarClasses[$range] ?? '' }}"
                                                     role="progressbar"
                                                     style="width: {{ max($percentage, $count > 0 ? 8 : 0) }}%">
                                                    {{ $count }} জন
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $percentage }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- শীর্ষ ১০ --}}
            @if(count($topStudents) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="section-title"><i class="bi bi-trophy"></i> শীর্ষ ১০ শিক্ষার্থী</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="8%">মর্যাদাক্রম</th>
                                        <th class="text-center" width="12%">রোল / আইডি</th>
                                        <th>নাম</th>
                                        <th class="text-center" width="12%">মোট নম্বর</th>
                                        <th class="text-center" width="10%">GPA</th>
                                        <th class="text-center" width="10%">গ্রেড</th>
                                        <th class="text-center" width="12%">ফলাফল</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topStudents as $student)
                                        <tr>
                                            <td class="text-center">
                                                @if($student['position'] === 1)
                                                    <strong class="merit-gold"><i class="bi bi-trophy-fill"></i> {{ $student['position'] }}</strong>
                                                @elseif($student['position'] === 2)
                                                    <strong class="merit-silver">{{ $student['position'] }}</strong>
                                                @elseif($student['position'] === 3)
                                                    <strong class="merit-bronze">{{ $student['position'] }}</strong>
                                                @else
                                                    {{ $student['position'] }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $student['roll'] }}</td>
                                            <td>{{ $student['name'] }}</td>
                                            <td class="text-center">{{ $student['total_marks'] }}</td>
                                            <td class="text-center"><strong>{{ $student['gpa'] }}</strong></td>
                                            <td class="text-center">{{ $student['letter_grade'] ?? '—' }}</td>
                                            <td class="text-center">
                                                <span class="{{ $student['status'] === 'Passed' ? 'badge-pass' : 'badge-fail' }}">
                                                    {{ $student['status'] === 'Passed' ? 'উত্তীর্ণ' : 'অনুত্তীর্ণ' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if($totalStudents > 0)
    <script>
        const totalStudents = {{ $totalStudents }};
        const passedStudents = {{ $passedStudents }};
        const failedStudents = {{ $failedStudents }};
        const gpaLabels = @json(array_keys($gpaDistribution));
        const gpaCounts = @json(array_values($gpaDistribution));

        const passFailCtx = document.getElementById('passFailChart');
        let passFailChart;
        if (passFailCtx) {
            passFailChart = new Chart(passFailCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [`উত্তীর্ণ (${passedStudents})`, `অনুত্তীর্ণ (${failedStudents})`],
                    datasets: [{
                        data: [passedStudents, failedStudents],
                        backgroundColor: ['#2e7d32', '#c62828'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `পাস-ফেল পরিসংখ্যান (মোট: ${totalStudents})`,
                            font: { size: 14 },
                        },
                        legend: { position: 'bottom' },
                    },
                    cutout: '62%',
                },
            });
        }

        const gpaCtx = document.getElementById('gpaDistributionChart');
        let gpaDistChart;
        if (gpaCtx) {
            gpaDistChart = new Chart(gpaCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: gpaLabels,
                    datasets: [{
                        label: 'ছাত্র/ছাত্রী সংখ্যা',
                        data: gpaCounts,
                        backgroundColor: ['#2e7d32', '#0288d1', '#0097a7', '#fbc02d', '#ffa000', '#f57c00', '#c62828'],
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `GPA বন্টন (মোট: ${totalStudents})`,
                            font: { size: 14 },
                        },
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            title: { display: true, text: 'সংখ্যা' },
                        },
                    },
                },
            });
        }

        window.addEventListener('beforeprint', () => {
            passFailChart?.resize();
            gpaDistChart?.resize();
        });
    </script>
    @endif
</body>
</html>
