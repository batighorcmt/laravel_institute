<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>বকেয়া আদায় রিপোর্ট</title>
    <style>
        body {
            font-family: 'kalpurush', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 16px;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
        }
        .summary-table {
            width: 300px;
            float: right;
        }
        .summary-table td {
            text-align: right;
        }
        .summary-table td:first-child {
            text-align: left;
            font-weight: bold;
        }
        .bn-num {
            font-family: 'nikosh', sans-serif;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school->name_bn ?: $school->name }}</div>
        <div>{{ $school->address }}</div>
        <div class="report-title">বকেয়া আদায় রিপোর্ট</div>
        <div>
            @if(isset($filters['month']) && $filters['month'])
                মাস: {{ \Carbon\Carbon::parse($filters['month'])->format('F Y') }} 
            @endif
            @if(isset($filters['class_id']) && $filters['class_id'])
                | শ্রেণি: {{ \App\Models\SchoolClass::find($filters['class_id'])->bangla_name ?? '' }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ক্র.নং</th>
                <th class="text-left">শিক্ষার্থীর নাম</th>
                <th>আইডি</th>
                <th>রোল</th>
                <th>শ্রেণি (শাখা)</th>
                <th>ফি ক্যাটাগরি</th>
                <th>মাস</th>
                <th>নির্ধারিত</th>
                <th>জরিমানা</th>
                <th>পরিশোধিত</th>
                <th>বকেয়া</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalAmount = 0;
                $totalFine = 0;
                $totalPaid = 0;
                $totalDue = 0;
            @endphp
            @foreach($fees as $index => $fee)
                @php
                    $due = ($fee['amount'] - ($fee['paid_amount'] - $fee['fine_amount'])) + ($fee['fine_amount'] - $fee['fine_waiver']);
                    // Actually let's use the logic from Flutter since it was consistent
                    // (amount - (paid - fine)) + (fine - waiver)
                    // Wait, fee['paid_amount'] in controller is (paid_amount + fine_amount)
                    // So actual paid base is fee['paid_amount'] - fee['fine_amount']
                    $due = ($fee['amount'] - ($fee['paid_amount'] - $fee['fine_amount'])) + ($fee['fine_amount'] - $fee['fine_waiver']);
                    
                    $totalAmount += $fee['amount'];
                    $totalFine += $fee['fine_amount'];
                    $totalPaid += $fee['paid_amount'];
                    $totalDue += $due;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $fee['student_name'] }}</td>
                    <td>{{ $fee['student_code'] }}</td>
                    <td>{{ $fee['roll_no'] }}</td>
                    <td>{{ $fee['class_name'] }} ({{ $fee['section_name'] }})</td>
                    <td>{{ $fee['category_name'] }}</td>
                    <td>{{ $fee['month'] }}</td>
                    <td class="text-right">{{ number_format($fee['amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($fee['fine_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($fee['paid_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($due, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="7" class="text-right">মোট:</td>
                <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                <td class="text-right">{{ number_format($totalFine, 2) }}</td>
                <td class="text-right">{{ number_format($totalPaid, 2) }}</td>
                <td class="text-right">{{ number_format($totalDue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div style="float: left; width: 200px; text-align: center;">
            <br><br>
            -----------------------<br>
            প্রস্তুতকারীর স্বাক্ষর
        </div>
        <div style="float: right; width: 200px; text-align: center;">
            <br><br>
            -----------------------<br>
            প্রধান শিক্ষকের স্বাক্ষর
        </div>
        <div style="clear: both;"></div>
        <p style="text-align: center; font-size: 10px; margin-top: 50px;">
            রিপোর্ট তৈরির সময়: {{ date('d-m-Y h:i A') }}
        </p>
    </div>
</body>
</html>
