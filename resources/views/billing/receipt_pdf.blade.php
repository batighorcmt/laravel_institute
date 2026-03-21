<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt - {{ $payment->payment_number }}</title>
    <style>
        body {
            font-family: 'SolaimanLipi', 'Hind Siliguri', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-grid td {
            vertical-align: top;
            width: 50%;
        }
        .label {
            color: #777;
            font-size: 12px;
            text-transform: uppercase;
        }
        .value {
            font-weight: bold;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th, table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        table.items th {
            background-color: #f5f5f5;
            text-align: left;
        }
        table.items td.desc {
            text-align: left;
        }
        .total-box {
            background-color: #eee;
            padding: 10px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
        }
        .signature-box {
            width: 100%;
        }
        .signature-box td {
            width: 50%;
            padding-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 180px;
            text-align: center;
        }
        .bangla {
            font-family: 'SolaimanLipi', sans-serif;
        }
    </style>
</head>
<body>
    @php
        $monthsBN = [
            'Jan' => 'জানুয়ারি', 'Feb' => 'ফেব্রুয়ারি', 'Mar' => 'মার্চ', 'Apr' => 'এপ্রিল',
            'May' => 'মে', 'Jun' => 'জুন', 'Jul' => 'জুলাই', 'Aug' => 'আগস্ট',
            'Sep' => 'সেপ্টেম্বর', 'Oct' => 'অক্টোবর', 'Nov' => 'নভেম্বর', 'Dec' => 'ডিসেম্বর',
            'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ', 'April' => 'এপ্রিল',
            'May' => 'মে', 'June' => 'জুন', 'July' => 'জুলাই', 'August' => 'আগস্ট',
            'September' => 'সেপ্টেম্বর', 'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
        ];
        
        $methodsBN = [
            'cash' => 'নগদ',
            'sslcommerz' => 'অনলাইন (SSLCommerz)',
            'bkash' => 'বিকাশ',
            'nagad' => 'নগদ',
            'bank' => 'ব্যাংক'
        ];

        function toBN($number) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return str_replace($en, $bn, $number);
        }
    @endphp

    <div class="header">
        <div class="school-name">{{ $payment->school->name_bn ?? $payment->school->name }}</div>
        <div>{{ $payment->school->address_bn ?? $payment->school->address }}</div>
        <div>ফোন: {{ toBN($payment->school->phone ?? '...') }}</div>
        <div style="margin-top: 10px; font-weight: bold; text-decoration: underline;">ফিস কালেকশন রিসিট</div>
    </div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="label">শিক্ষার্থীর তথ্য</div>
                <div class="value">{{ $payment->student->student_name_bn ?? ($payment->student->student_name_en ?? 'শিক্ষার্থী') }}</div>
                <div>আইডি: {{ $payment->student->student_id }}</div>
                <div>শ্রেণি: {{ $payment->student->currentEnrollment?->class?->name ?? '...' }} | শাখা: {{ $payment->student->currentEnrollment?->section?->name ?? '...' }}</div>
                <div>রোল: {{ toBN($payment->student->currentEnrollment?->roll_no ?? '...') }}</div>
            </td>
            <td style="text-align: right;">
                <div class="label">রিসিট তথ্য</div>
                <div>রিসিট নং: <span class="value">{{ toBN($payment->payment_number) }}</span></div>
                <div>তারিখ: {{ toBN($payment->received_at->format('d/m/Y')) }}</div>
                <div>মাধ্যম: {{ $methodsBN[strtolower($payment->payment_method)] ?? $payment->payment_method }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="desc">বিবরণ</th>
                <th>মাস</th>
                <th>ফি</th>
                <th>জরিমানা</th>
                <th>মোট</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payment->paymentItems as $item)
            <tr>
                <td class="desc">{{ $item->studentFee->feeStructure->category->name ?? 'ফি' }}</td>
                <td>
                    @if($item->studentFee->month)
                        {{ $monthsBN[date('F', strtotime($item->studentFee->month . '-01'))] ?? date('M', strtotime($item->studentFee->month . '-01')) }}
                    @else
                        এককালীন
                    @endif
                </td>
                <td>{{ toBN(number_format($item->studentFee->amount, 0)) }}</td>
                <td>{{ toBN(number_format($item->studentFee->calculateFine(), 0)) }}</td>
                <td style="font-weight: bold;">{{ toBN(number_format($item->amount, 0)) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        সর্বমোট পরিশোধ: ৳ {{ toBN(number_format($payment->amount_paid, 0)) }}
    </div>

    <table class="signature-box">
        <tr>
            <td>
                <div class="signature-line">শিক্ষার্থীর স্বাক্ষর</div>
            </td>
            <td style="text-align: right;">
                <div class="signature-line" style="margin-left: auto;">আদায়কারীর স্বাক্ষর</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; text-align: center; color: #999; font-size: 10px;">
        Generated by Batighor EIMS &bull; batighorbd.com
    </div>
</body>
</html>
