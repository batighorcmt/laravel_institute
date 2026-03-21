<!DOCTYPE html>
<html lang="bn">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt - {{ $payment->payment_number }}</title>
    <style>
        body, table, td, th, div, p, span, h1, h2, h3, h4 {
            font-family: 'kalpurush', sans-serif;
            color: #000;
        }
        body {
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .page {
            padding: 18px 22px;
        }

        /* ===== SCHOOL HEADER ===== */
        .header-border {
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header-table {
            width: 100%;
        }
        .logo-cell {
            width: 80px;
            vertical-align: middle;
        }
        .logo-cell img {
            width: 72px;
            height: 72px;
        }
        .school-info-cell {
            text-align: center;
            vertical-align: middle;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 3px 0;
        }
        .school-sub {
            font-size: 12px;
            margin: 2px 0;
            color: #000;
        }
        .receipt-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 16px;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* ===== META INFO ===== */
        .meta-table {
            width: 100%;
            margin-bottom: 18px;
        }
        .meta-left {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .meta-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .section-label {
            font-size: 9px;
            font-weight: bold;
            color: #94a3b8;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .student-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 3px;
        }
        .meta-row {
            font-size: 12px;
            color: #334155;
            margin-bottom: 2px;
        }
        .meta-row span {
            font-weight: bold;
            color: #000;
        }

        /* ===== ITEMS TABLE ===== */
        .items-wrapper {
            border: 1px solid #e2e8f0;
            margin-bottom: 14px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead tr {
            background-color: #f8fafc;
        }
        table.items th {
            padding: 7px 5px;
            font-size: 10px;
            font-weight: bold;
            color: #000;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }
        table.items tbody td {
            padding: 7px 5px;
            font-size: 12px;
            color: #000;
        }
        table.items tfoot {
            background-color: #f8fafc;
            border-top: 2px solid #000;
        }
        table.items tfoot td {
            padding: 8px 5px;
            font-size: 13px;
            font-weight: bold;
        }

        /* ===== AMOUNT IN WORDS ===== */
        .amount-words {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 18px;
            color: #000;
            padding-bottom: 8px;
            border-bottom: 1px dotted #94a3b8;
        }

        /* ===== SIGNATURE ===== */
        .sig-table {
            width: 100%;
            margin-top: 55px;
        }
        .sig-cell {
            width: 50%;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            width: 180px;
            padding-top: 5px;
            font-size: 11px;
            color: #475569;
            font-weight: bold;
        }
        .sig-right {
            text-align: right;
        }
        .sig-right .sig-line {
            margin-left: auto;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-left   { text-align: left; }
        .bold        { font-weight: bold; }
        .red         { color: #dc2626; }
    </style>
</head>
<body>
@php
    $monthsBN = [
        'Jan'=>'জানুয়ারি','Feb'=>'ফেব্রুয়ারি','Mar'=>'মার্চ','Apr'=>'এপ্রিল',
        'May'=>'মে','Jun'=>'জুন','Jul'=>'জুলাই','Aug'=>'আগস্ট',
        'Sep'=>'সেপ্টেম্বর','Oct'=>'অক্টোবর','Nov'=>'নভেম্বর','Dec'=>'ডিসেম্বর',
        'January'=>'জানুয়ারি','February'=>'ফেব্রুয়ারি','March'=>'মার্চ','April'=>'এপ্রিল',
        'June'=>'জুন','July'=>'জুলাই','August'=>'আগস্ট',
        'September'=>'সেপ্টেম্বর','October'=>'অক্টোবর','November'=>'নভেম্বর','December'=>'ডিসেম্বর',
    ];
    $methodsBN = [
        'cash'=>'নগদ','sslcommerz'=>'অনলাইন (SSLCommerz)',
        'bkash'=>'বিকাশ','nagad'=>'নগদ (মোবাইল)','bank'=>'ব্যাংক',
    ];

    function toBN($number) {
        return str_replace(['0','1','2','3','4','5','6','7','8','9'],
                           ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], $number);
    }

    function amountInWordsBN($number) {
        $number = (int)$number;
        if ($number == 0) return 'শূন্য টাকা মাত্র।';
        $d = ["","এক","দুই","তিন","চার","পাঁচ","ছয়","সাত","আট","নয়"];
        $t = ["","","বিশ","ত্রিশ","চল্লিশ","পঞ্চাশ","ষাট","সত্তুর","আশি","নব্বই"];
        $teen = ["দশ","এগারো","বারো","তেরো","চৌদ্দ","পনেরো","ষোলো","সতেরো","আঠারো","ঊনিশ"];
        $cv = function($n) use ($d,$t,$teen,&$cv) {
            $n=(int)$n; $r="";
            if($n>=100){$r.=$d[(int)($n/100)]." শত ";$n%=100;}
            if($n>=20){$r.=$t[(int)($n/10)]." ";if($n%10>0)$r.=$d[$n%10];}
            elseif($n>=10){$r.=$teen[$n-10];}
            elseif($n>0){$r.=$d[$n];}
            return trim($r);
        };
        $res="";
        if($number>=10000000){$res.=$cv((int)($number/10000000))." কোটি ";$number%=10000000;}
        if($number>=100000){$res.=$cv((int)($number/100000))." লক্ষ ";$number%=100000;}
        if($number>=1000){$res.=$cv((int)($number/1000))." হাজার ";$number%=1000;}
        if($number>=100){$res.=$cv((int)($number/100))." শত ";$number%=100;}
        if($number>0){$res.=$cv($number);}
        return trim($res)." টাকা মাত্র।";
    }

    // Resolve logo (works local and on live server)
    $logoBase64 = null;
    if ($payment->school && $payment->school->logo) {
        $logoPath = public_path('storage/' . $payment->school->logo);
        if (!file_exists($logoPath)) {
            // Try alternative: storage_path
            $logoPath = storage_path('app/public/' . $payment->school->logo);
        }
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['png']) ? 'image/png' : 'image/jpeg';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
@endphp

<div class="page">

    {{-- ===== SCHOOL HEADER ===== --}}
    <div class="header-border">
        <table class="header-table">
            <tr>
                @if($logoBase64)
                <td class="logo-cell">
                    <img src="{{ $logoBase64 }}">
                </td>
                @endif
                <td class="school-info-cell">
                    <div class="school-name">{{ $payment->school->name_bn ?? $payment->school->name ?? 'প্রতিষ্ঠান' }}</div>
                    @if($payment->school && ($payment->school->address_bn || $payment->school->address))
                        <div class="school-sub">{{ $payment->school->address_bn ?? $payment->school->address }}</div>
                        <div class="school-sub">
                            ফোন: {{ toBN($payment->school->phone ?? '') }}
                            @if($payment->school->email) | ইমেইল: {{ $payment->school->email }} @endif
                        </div>
                    @endif
                    <div class="receipt-pill">ফিস কালেকশন রিসিট</div>
                </td>
                @if($logoBase64)
                <td style="width: 80px;"></td>
                @endif
            </tr>
        </table>
    </div>

    {{-- ===== META INFO ===== --}}
    <table class="meta-table">
        <tr>
            <td class="meta-left">
                <div class="section-label">শিক্ষার্থীর তথ্য</div>
                <div class="student-name">{{ $payment->student->student_name_bn ?? $payment->student->student_name_en }}</div>
                <div class="meta-row">আইডি: <span>{{ $payment->student->student_id }}</span></div>
                <div class="meta-row">
                    শ্রেণি: <span>{{ $payment->student->currentEnrollment->class->bangla_name ?? $payment->student->currentEnrollment->class->name ?? '...' }}</span>
                    @if($payment->student->currentEnrollment && $payment->student->currentEnrollment->section)
                        | শাখা: <span>{{ $payment->student->currentEnrollment->section->bangla_name ?? $payment->student->currentEnrollment->section->name }}</span>
                    @endif
                </div>
                <div class="meta-row">রোল: <span>{{ toBN($payment->student->currentEnrollment->roll_no ?? '') }}</span></div>
            </td>
            <td class="meta-right">
                <div class="section-label">রিসিট তথ্য</div>
                <div class="meta-row">রিসিট নং: <span>{{ toBN($payment->payment_number) }}</span></div>
                <div class="meta-row">তারিখ: <span>{{ toBN($payment->received_at->format('d/m/Y')) }}</span></div>
                <div class="meta-row">পেমেন্ট মাধ্যম: <span>{{ $methodsBN[strtolower($payment->payment_method)] ?? $payment->payment_method }}</span></div>
                @if($payment->tran_id || $payment->external_txn_id)
                    <div class="meta-row" style="font-size:11px;">ট্রানজেকশন আইডি: <span>{{ $payment->tran_id ?? $payment->external_txn_id }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== PAYMENT ITEMS TABLE ===== --}}
    <div class="items-wrapper">
        <table class="items">
            <thead>
                <tr>
                    <th style="text-align:left;">বিবরণ (ফি-এর খাত)</th>
                    <th style="text-align:center; width:100px;">মাস</th>
                    <th style="text-align:right; width:65px;">ফি</th>
                    <th style="text-align:right; width:65px;">জরিমানা</th>
                    <th style="text-align:right; width:65px;">মওকুফ</th>
                    <th style="text-align:right; width:80px;">মোট</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->paymentItems as $item)
                <tr>
                    <td style="font-weight:500;">{{ $item->studentFee->feeStructure->category->name ?? 'ফি' }}</td>
                    <td style="text-align:center;">
                        @if($item->studentFee->month)
                            {{ $monthsBN[date('F', strtotime($item->studentFee->month.'-01'))] ?? '' }},
                            {{ toBN(date('Y', strtotime($item->studentFee->month.'-01'))) }}
                        @else
                            এককালীন
                        @endif
                    </td>
                    <td style="text-align:right;">{{ toBN(number_format(($item->studentFee->original_amount ?: $item->studentFee->amount), 0)) }}</td>
                    <td style="text-align:right;">{{ toBN(number_format($item->studentFee->calculateOriginalFine(), 0)) }}</td>
                    <td style="text-align:right;" class="red">-{{ toBN(number_format(((($item->studentFee->original_amount ?: $item->studentFee->amount) - $item->studentFee->amount) + ($item->studentFee->fine_waiver ?? 0)), 0)) }}</td>
                    <td style="text-align:right;" class="bold">৳ {{ toBN(number_format($item->amount, 0)) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">সর্বমোট পরিশোধিত:</td>
                    <td style="text-align:right; font-size:15px;">৳ {{ toBN(number_format($payment->amount_paid, 0)) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ===== AMOUNT IN WORDS ===== --}}
    <div class="amount-words">
        কথায়: {{ amountInWordsBN($payment->amount_paid) }}
    </div>

    {{-- ===== SIGNATURES ===== --}}
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-line">শিক্ষার্থীর/অভিভাবকের স্বাক্ষর</div>
            </td>
            <td class="sig-cell sig-right">
                <div class="sig-line">আদায়কারীর স্বাক্ষর</div>
            </td>
        </tr>
    </table>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        Generated by Batighor EIMS &bull; batighorbd.com
    </div>

</div>
</body>
</html>
