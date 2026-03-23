<!DOCTYPE html>
<html lang="bn">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt - {{ $payment->payment_number }}</title>
    <style>
        /* Force normal font weight everywhere to prevent mPDF from falling back to non-Bengali bold fonts */
        body, table, td, th, div, p, span, h1, h2, h3, h4, b, strong {
            font-family: 'kalpurush', sans-serif !important;
            font-weight: normal !important;
        }
        body {
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .wrapper {
            padding: 5px 15px;
        }

        /* ===== HEADER ===== */
        .header-container {
            width: 100%;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-td {
            width: 90px;
            vertical-align: middle;
            text-align: left;
        }
        .school-info-td {
            text-align: center;
            vertical-align: middle;
        }
        .school-name {
            font-size: 26px;
            color: #000;
            margin-bottom: 3px;
        }
        .school-address {
            font-size: 13px;
            color: #000;
        }
        .school-contact {
            font-size: 13px;
            color: #000;
        }
        .receipt-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 18px;
            background-color: #e2e8f0;
            border-radius: 20px;
            font-size: 13px;
            color: #000;
            border: 1px solid #cbd5e1;
        }

        /* ===== INFO SECTION ===== */
        table.info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-left {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .info-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
            padding-left: 15px;
        }
        .info-title {
            font-size: 12px;
            color: #475569;
            margin-bottom: 8px;
            text-decoration: underline;
        }
        .student-name {
            font-size: 18px;
            color: #000; 
            margin-bottom: 5px;
        }
        .info-row {
            font-size: 13px;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .info-row span {
            color: #000;
        }

        /* ===== ITEMS TABLE ===== */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #94a3b8;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        table.items-table th {
            background-color: #f1f5f9;
            color: #000;
            font-size: 12px;
            padding: 8px;
            border: 1px solid #94a3b8; 
        }
        table.items-table td {
            font-size: 13px;
            color: #000;
            padding: 8px;
            border-left: 1px solid #94a3b8;
            border-right: 1px solid #94a3b8;
            border-bottom: 1px dotted #cbd5e1;
        }
        table.items-table tfoot th, table.items-table tfoot td {
            background-color: #f1f5f9;
            border-top: 2px solid #000;
            border-bottom: 1px solid #94a3b8;
            color: #000;
            font-size: 15px;
            padding: 10px;
        }

        /* ===== AMOUNT IN WORDS ===== */
        .amount-in-words {
            font-size: 14px;
            margin-bottom: 40px;
            color: #000;
        }

        /* ===== SIGNATURES ===== */
        table.signature-table {
            width: 100%;
            margin-top: 70px;
        }
        .signature-cell {
            width: 50%;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #475569;
            width: 190px;
            padding-top: 6px;
            font-size: 13px;
            color: #1e293b;
        }
        .sig-right {
            text-align: right;
        }
        .sig-right .signature-line {
            margin-left: auto;
        }

        /* ===== FOOTER ===== */
        .footer-note {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-red { color: #dc2626; }
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

    // Resolve Logo path properly for mPDF
    $logoSrc = null;
    if ($payment->school && $payment->school->logo) {
        if (file_exists(public_path('storage/' . $payment->school->logo))) {
             $logoPath = public_path('storage/' . $payment->school->logo);
        } elseif (file_exists(storage_path('app/public/' . $payment->school->logo))) {
             $logoPath = storage_path('app/public/' . $payment->school->logo);
        } else {
             $logoPath = null;
        }

        if ($logoPath) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['png']) ? 'image/png' : 'image/jpeg';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
@endphp

<div class="wrapper">

    {{-- ===== HEADER ===== --}}
    <div class="header-container">
        <table class="header-table">
            <tr>
                @if($logoSrc)
                <td class="logo-td">
                    <img src="{{ $logoSrc }}" width="80" height="80">
                </td>
                @endif
                <td class="school-info-td">
                    <div class="school-name">{{ $payment->school->name_bn ?? $payment->school->name ?? 'প্রতিষ্ঠান' }}</div>
                    @if($payment->school && ($payment->school->address_bn || $payment->school->address))
                        <div class="school-address">{{ $payment->school->address_bn ?? $payment->school->address }}</div>
                        <div class="school-contact">
                            {{ toBN($payment->school->phone ?? '') }}
                            @if($payment->school->email) | {{ $payment->school->email }} @endif
                            @if($payment->school->website) | {{ $payment->school->website }} @endif
                        </div>
                    @endif
                    <div class="receipt-badge">ফিস আদায় রশিদ</div>
                </td>
                @if($logoSrc)
                <td style="width: 90px;"></td> <!-- Spacer to keep center alignment -->
                @endif
            </tr>
        </table>
    </div>

    {{-- ===== INFO SECTION ===== --}}
    <table class="info-table">
        <tr>
            <td class="info-left">
                <div class="info-title">শিক্ষার্থীর তথ্য</div>
                <div class="student-name">{{ $payment->student->student_name_bn ?? $payment->student->student_name_en }}</div>
                <div class="info-row">আইডি: <span>{{ $payment->student->student_id }}</span></div>
                <div class="info-row">
                    শ্রেণি: <span>{{ $payment->student->currentEnrollment->class->bangla_name ?? $payment->student->currentEnrollment->class->name ?? '...' }}</span>
                    @if($payment->student->currentEnrollment && $payment->student->currentEnrollment->section)
                        | শাখা: <span>{{ $payment->student->currentEnrollment->section->bangla_name ?? $payment->student->currentEnrollment->section->name }}</span>
                    @endif
                </div>
                <div class="info-row">রোল: <span>{{ toBN($payment->student->currentEnrollment->roll_no ?? '') }}</span></div>
            </td>
            <td class="info-right">
                <div class="info-title">রিসিট তথ্য</div>
                <div class="info-row">রিসিট নং: <span>{{ toBN($payment->payment_number) }}</span></div>
                <div class="info-row">তারিখ: <span>{{ toBN($payment->received_at->format('d/m/Y')) }}</span></div>
                <div class="info-row">পেমেন্ট মাধ্যম: <span>{{ $methodsBN[strtolower($payment->payment_method)] ?? $payment->payment_method }}</span></div>
                @if($payment->tran_id || $payment->external_txn_id)
                    <div class="info-row">ট্রানজেকশন আইডি: <span>{{ $payment->tran_id ?? $payment->external_txn_id }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 38%;">বিবরণ (ফি-এর খাত)</th>
                <th class="text-center" style="width: 16%;">মাস</th>
                <th class="text-right" style="width: 12%;">ফি</th>
                <th class="text-right" style="width: 10%;">জরিমানা</th>
                <th class="text-right" style="width: 10%;">মওকুফ</th>
                <th class="text-right" style="width: 14%;">মোট</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payment->paymentItems as $item)
            <tr>
                <td>{{ $item->studentFee->feeStructure->category->name ?? 'ফি' }}</td>
                <td class="text-center">
                    @if($item->studentFee->month)
                        {{ $monthsBN[date('F', strtotime($item->studentFee->month.'-01'))] ?? '' }}, 
                        {{ toBN(date('Y', strtotime($item->studentFee->month.'-01'))) }}
                    @else
                        এককালীন
                    @endif
                </td>
                <td class="text-right">{{ toBN(number_format(($item->studentFee->original_amount ?: $item->studentFee->amount), 0)) }}</td>
                <td class="text-right">{{ toBN(number_format($item->studentFee->calculateOriginalFine(), 0)) }}</td>
                <td class="text-right text-red">-{{ toBN(number_format(((($item->studentFee->original_amount ?: $item->studentFee->amount) - $item->studentFee->amount) + ($item->studentFee->fine_waiver ?? 0)), 0)) }}</td>
                <td class="text-right">৳ {{ toBN(number_format($item->amount, 0)) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">সর্বমোট পরিশোধিত:</td>
                <td class="text-right">৳ {{ toBN(number_format($payment->amount_paid, 0)) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== AMOUNT IN WORDS ===== --}}
    <div class="amount-in-words">
        কথায়: {{ amountInWordsBN($payment->amount_paid) }}
    </div>

    {{-- ===== SIGNATURES ===== --}}
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-line">শিক্ষার্থীর/অভিভাবকের স্বাক্ষর</div>
            </td>
            <td class="signature-cell sig-right">
                <div class="signature-line">আদায়কারীর স্বাক্ষর</div>
                @if($payment->collectedBy)
                    <div style="font-size: 11px; margin-top: 3px; font-weight: bold;">({{ $payment->collectedBy->name ?? '' }})</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Generated by Batighor EIMS &bull; batighorbd.com
    </div>

</div>
</body>
</html>
