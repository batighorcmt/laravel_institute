<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>বকেয়া আদায় রিপোর্ট</title>
    <style>
        body {
            font-family: 'kalpurush', sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        @page {
            margin: 10mm;
        }
        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 5px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo-cell {
            width: 80px;
        }
        .logo {
            width: 70px;
            height: 70px;
        }
        .school-info {
            text-align: center;
        }
        .school-name {
            font-size: 28px;
            margin: 0;
            line-height: 1.2;
            font-weight: normal;
        }
        .school-address {
            font-size: 14px;
            margin-top: 5px;
            font-weight: normal;
        }
        .report-title {
            text-align: center;
            font-size: 22px;
            margin: 10px 0;
        }
        .filters-line {
            text-align: center;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .divider {
            border-top: 2px solid #000;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'kalpurush', sans-serif;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 10px;
        }
        th {
            background-color: #fff;
            font-weight: normal;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        .student-name {
            font-size: 11px;
            display: block;
        }
        .student-id {
            font-size: 9px;
            color: #333;
            display: block;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #000;
            border-radius: 12px;
            font-size: 9px;
            min-width: 60px;
        }
        
        .footer {
            margin-top: 50px;
        }
        .footer-sig {
            width: 100%;
        }
        .footer-sig td {
            border: none;
            width: 50%;
            padding-top: 40px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @php
        $ms = [1=>'জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
        $bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        
        $toBnNum = function($num) use ($bnDigits) {
            return str_replace(range(0,9), $bnDigits, $num);
        };

        $formatBnM = function($mStr) use ($ms, $bnDigits) {
            if(!$mStr) return '';
            try {
                $d = \Carbon\Carbon::parse($mStr);
                $bnM = $ms[$d->month] ?? '';
                $bnY = str_replace(range(0,9), $bnDigits, $d->year);
                return $bnM . ' ' . $bnY;
            } catch(\Exception $e) { return $mStr; }
        };

        $statusMap = [
            'paid' => 'পরিশোধিত',
            'partial' => 'আংশিক',
            'unpaid' => 'অপরিশোধিত'
        ];
    @endphp

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($school->logo)
                    <img src="{{ public_path('storage/'.$school->logo) }}" class="logo">
                @endif
            </td>
            <td class="school-info">
                <div class="school-name">{{ $school->name_bn ?: $school->name }}</div>
                <div class="school-address">{{ $school->address }}</div>
            </td>
            <td class="logo-cell"></td>
        </tr>
    </table>

    <div class="report-title">বকেয়া আদায় রিপোর্ট</div>

    <div class="filters-line">
        @if(isset($filters['academic_year_id']))
            @php $year = \App\Models\AcademicYear::find($filters['academic_year_id']); @endphp
            বছর: {{ $toBnNum($year->year ?? '') }} |
        @endif
        
        @if(isset($filters['class_id']))
            @php $cls = \App\Models\SchoolClass::find($filters['class_id']); @endphp
            শ্রেণি: {{ $cls->bangla_name ?: $cls->name ?: 'সকল' }} |
        @endif

        @if(isset($filters['section_id']))
            @php $sec = \App\Models\Section::find($filters['section_id']); @endphp
            শাখা: {{ $sec->bangla_name ?: $sec->name ?: 'সকল' }} |
        @endif

        @if(isset($filters['fee_category_id']))
            @php $cat = \App\Models\FeeCategory::find($filters['fee_category_id']); @endphp
            ক্যাটাগরি: {{ $cat->name ?? 'সকল' }} |
        @endif

        @php
            $stat = 'সবগুলো';
            if(isset($filters['status'])) {
                if($filters['status'] == 'due') $stat = 'বকেয়া (সব)';
                elseif($filters['status'] == 'unpaid') $stat = 'অপরিশোধিত';
                elseif($filters['status'] == 'partial') $stat = 'আংশিক';
                elseif($filters['status'] == 'paid') $stat = 'পরিশোধিত';
            }
        @endphp
        অবস্থা: {{ $stat }}
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">ক্র: নং</th>
                <th style="width: 150px;">শিক্ষার্থী ও আইডি</th>
                <th style="width: 40px;">রোল নং</th>
                <th style="width: 80px;">মাসের নাম</th>
                <th style="width: 60px;">নির্ধারিত</th>
                <th style="width: 50px;">জরিমানা</th>
                <th style="width: 50px;">মওকুফ</th>
                <th style="width: 60px;">পরিশোধিত</th>
                <th style="width: 60px;">বকেয়া</th>
                <th style="width: 80px;">অবস্থা</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalAmount = 0;
                $totalFine = 0;
                $totalWaiver = 0;
                $totalPaid = 0;
                $totalDue = 0;
            @endphp
            @foreach($fees as $index => $fee)
                @php
                    $due = max(0, ($fee['amount'] + ($fee['fine_amount'] ?? 0)) - ($fee['fine_waiver'] ?? 0) - ($fee['paid_amount'] ?? 0));
                    
                    $totalAmount += $fee['amount'];
                    $totalFine += ($fee['fine_amount'] ?? 0);
                    $totalWaiver += ($fee['fine_waiver'] ?? 0);
                    $totalPaid += ($fee['paid_amount'] ?? 0);
                    $totalDue += $due;
                @endphp
                <tr>
                    <td>{{ $toBnNum($index + 1) }}</td>
                    <td class="text-left">
                        <span class="student-name">{{ $fee['student_name'] }}</span>
                        <span class="student-id">{{ $fee['student_code'] }}</span>
                    </td>
                    <td>{{ $toBnNum($fee['roll_no']) }}</td>
                    <td>{{ $formatBnM($fee['month']) }}</td>
                    <td class="text-right">{{ $toBnNum(number_format($fee['amount'], 0)) }}</td>
                    <td class="text-right">{{ $toBnNum(number_format($fee['fine_amount'] ?? 0, 0)) }}</td>
                    <td class="text-right">{{ $toBnNum(number_format($fee['fine_waiver'] ?? 0, 0)) }}</td>
                    <td class="text-right">{{ $toBnNum(number_format($fee['paid_amount'] ?? 0, 0)) }}</td>
                    <td class="text-right">{{ $toBnNum(number_format($due, 0)) }}</td>
                    <td>
                        <div class="status-badge">
                            {{ $statusMap[$fee['status']] ?? 'অজানা' }}
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if(count($fees) > 0)
        <tfoot>
            <tr style="background-color: #f9f9f9;">
                <td colspan="4" class="text-right">মোট:</td>
                <td class="text-right">{{ $toBnNum(number_format($totalAmount, 0)) }}</td>
                <td class="text-right">{{ $toBnNum(number_format($totalFine, 0)) }}</td>
                <td class="text-right">{{ $toBnNum(number_format($totalWaiver, 0)) }}</td>
                <td class="text-right">{{ $toBnNum(number_format($totalPaid, 0)) }}</td>
                <td class="text-right">{{ $toBnNum(number_format($totalDue, 0)) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <table class="footer-sig">
            <tr>
                <td style="text-align: center;">
                    -----------------------<br>
                    প্রস্তুতকারীর স্বাক্ষর
                </td>
                <td style="text-align: center;">
                    -----------------------<br>
                    প্রধান শিক্ষকের স্বাক্ষর
                </td>
            </tr>
        </table>
        <div style="text-align: center; font-size: 10px; margin-top: 30px;">
            রিপোর্ট তৈরির সময়: {{ $toBnNum(date('d-m-Y h:i A')) }}
        </div>
    </div>
</body>
</html>
