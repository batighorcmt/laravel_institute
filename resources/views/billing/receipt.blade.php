@extends('layouts.admin')
@section('title', 'রিসিট - ' . ($payment->student->student_name_bn ?? $payment->student->student_name_en) . ' - ' . ($payment->paymentItems->first()->studentFee->feeStructure->category->name ?? 'পেমেন্ট'))

@push('styles')
<style>
    @media print {
        /* Hide all admin layout components */
        .main-header, .main-sidebar, .main-footer, .no-print, .content-header {
            display: none !important;
        }
        
        /* Reset content wrapper for print */
        .content-wrapper, .wrapper, body, html {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: auto !important;
            border: none !important;
        }

        #printable-receipt {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 20px !important;
            width: 100% !important;
            max-width: 100% !important;
            display: block !important;
            visibility: visible !important;
            position: static !important;
        }

        .printable, .printable * {
            visibility: visible !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        /* Ensure text is bright black in print */
        p, span, div, h1, h2, h3, h4, th, td {
            color: #000 !important;
        }
    }
    
    /* Bangla Font Support */
    .hind-siliguri {
        font-family: 'Hind Siliguri', sans-serif;
    }
</style>
@endpush

@section('content')
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
        'nagad' => 'নগদ (মোবাইল ব্যাংকিং)',
        'bank' => 'ব্যাংক'
    ];

    function toBN($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, $number);
    }

    function amountInWordsBN($number) {
        $number = (int)$number;
        if ($number == 0) return 'শূন্য';
        
        $bn_digits = ["", "এক", "দুই", "তিন", "চার", "পাঁচ", "ছয়", "সাত", "আট", "নয়"];
        $bn_tens = ["", "", "বিশ", "ত্রিশ", "চল্লিশ", "পঞ্চাশ", "ষাট", "সত্তুর", "আশি", "নব্বই"];
        $bn_teens = ["দশ", "এগারো", "বারো", "তেরো", "চৌদ্দ", "পনেরো", "ষোলো", "সতেরো", "আঠারো", "ঊনিশ"];
        
        $convert = function($n) use ($bn_digits, $bn_tens, $bn_teens, &$convert) {
            $n = (int)$n;
            $res = "";
            if ($n >= 100) {
                $res .= $bn_digits[(int)($n/100)] . " শত ";
                $n %= 100;
            }
            if ($n >= 20) {
                $res .= $bn_tens[(int)($n/10)] . " ";
                if ($n % 10 > 0) $res .= $bn_digits[$n % 10];
            } else if ($n >= 10) {
                $res .= $bn_teens[$n - 10];
            } else if ($n > 0) {
                $res .= $bn_digits[$n];
            }
            return trim($res);
        };

        $res = "";
        if ($number >= 10000000) {
            $res .= $convert((int)($number/10000000)) . " কোটি ";
            $number %= 10000000;
        }
        if ($number >= 100000) {
            $res .= $convert((int)($number/100000)) . " লক্ষ ";
            $number %= 100000;
        }
        if ($number >= 1000) {
            $res .= $convert((int)($number/1000)) . " হাজার ";
            $number %= 1000;
        }
        if ($number >= 100) {
            $res .= $convert((int)($number/100)) . " শত ";
            $number %= 100;
        }
        if ($number > 0) {
            $res .= $convert($number);
        }
        
        return trim($res) . " টাকা মাত্র";
    }
@endphp
<div class="py-4 no-print">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800 hind-siliguri">পেমেন্ট রিসিট</h1>
        <div class="space-x-4">
            <a href="{{ route('billing.collect') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">ফিরে যান</a>
            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm transition inline-flex items-center">
                <i class="fas fa-print mr-2"></i>
                প্রিন্ট করুন
            </button>
        </div>
    </div>
</div>

<div class="flex justify-center mb-8">
    <!-- Printable Area -->
    <div id="printable-receipt" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 printable bg-opacity-95 max-w-4xl w-full" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <!-- School Header -->
        <div class="border-b border-slate-200 pb-4 mb-6 relative">
            <div class="flex items-center">
                @if($payment->school && $payment->school->logo)
                    <div class="flex-shrink-0 mr-6">
                        <img src="{{ asset('storage/' . $payment->school->logo) }}" class="h-20 w-20 object-contain">
                    </div>
                @endif
                <div class="flex-1 text-center pr-10">
                    <h2 class="text-3xl font-bold text-black mb-1 hind-siliguri">{{ $payment->school->name_bn ?? $payment->school->name ?? 'প্রতিষ্ঠান' }}</h2>
                    @if($payment->school && ($payment->school->address_bn || $payment->school->address))
                        <p class="text-black text-sm">{{ $payment->school->address_bn ?? $payment->school->address }}</p>
                        <p class="text-black text-sm font-medium">
                            {{ toBN($payment->school->phone ?? '01700000000') }} 
                            @if($payment->school->email) | {{ $payment->school->email }} @endif
                            @if($payment->school->website) | {{ $payment->school->website }} @endif
                        </p>
                    @endif
                    <div class="inline-block mt-3 px-4 py-1 bg-slate-100 rounded-full text-sm font-semibold text-black tracking-wider">
                        ফিস কালেকশন রিসিট
                    </div>
                </div>
            </div>
        </div>

            <!-- Meta Information -->
            <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2 tracking-wider">শিক্ষার্থীর তথ্য</h4>
                <p class="font-bold text-lg text-indigo-900 mb-1">{{ $payment->student->student_name_bn ?? $payment->student->student_name_en }}</p>
                <p class="text-slate-600 text-sm">আইডি: <span class="font-medium text-slate-800">{{ $payment->student->student_id }}</span></p>
                <p class="text-slate-600 text-sm mt-1">
                    শ্রেণি: <span class="font-medium text-slate-800">{{ $payment->student->currentEnrollment->class->bangla_name ?? $payment->student->currentEnrollment->class->name ?? '...' }}</span>
                    @if($payment->student->currentEnrollment && $payment->student->currentEnrollment->section)
                        | শাখা: <span class="font-medium text-slate-800">{{ $payment->student->currentEnrollment->section->bangla_name ?? $payment->student->currentEnrollment->section->name }}</span>
                    @endif
                </p>
                <p class="text-black text-sm mt-1">
                    রোল: <span class="font-bold text-black">{{ toBN($payment->student->currentEnrollment->roll_no ?? '...') }}</span>
                </p>
            </div>
                <div class="text-right">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2 tracking-wider font-bold">রিসিট তথ্য</h4>
                    <p class="text-black text-sm">রিসিট নং: <span class="font-bold text-black">{{ toBN($payment->payment_number) }}</span></p>
                    <p class="text-black text-sm mt-1">তারিখ: <span class="font-medium text-black">{{ toBN($payment->received_at->format('d/m/Y')) }}</span></p>
                    <p class="text-black text-sm mt-1">পেমেন্ট মাধ্যম: <span class="font-bold text-black capitalize">{{ $methodsBN[strtolower($payment->payment_method)] ?? $payment->payment_method }}</span></p>
                    @if($payment->tran_id || $payment->external_txn_id)
                        <p class="text-black text-xs mt-1">ট্রানজেকশন আইডি: <span class="font-medium text-black">{{ $payment->tran_id ?? $payment->external_txn_id }}</span></p>
                    @endif
                </div>
            </div>

            <!-- Payment Items -->
            <div class="mb-8 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-bold text-black uppercase tracking-wider">বিবরণ (ফি-এর খাত)</th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-bold text-black uppercase tracking-wider">মাস</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-bold text-black uppercase tracking-wider">ফি</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-bold text-black uppercase tracking-wider">জরিমানা</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-bold text-black uppercase tracking-wider">মওকুফ</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-bold text-black uppercase tracking-wider">মোট</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($payment->paymentItems as $item)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black font-medium">
                                {{ $item->studentFee->feeStructure->category->name ?? 'ফি' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black text-center">
                                @if($item->studentFee->month)
                                    {{ $monthsBN[date('F', strtotime($item->studentFee->month . '-01'))] ?? date('M', strtotime($item->studentFee->month . '-01')) }}, {{ toBN(date('Y', strtotime($item->studentFee->month . '-01'))) }}
                                @else
                                    <span class="text-black text-xs text-nowrap">এককালীন</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black text-right">
                                {{ toBN(number_format($item->studentFee->amount ?? $item->amount, 0)) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black text-right">
                                {{ toBN(number_format($item->studentFee->fine_amount ?? 0, 0)) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black text-right text-red-600">
                                -{{ toBN(number_format($item->studentFee->fine_waiver ?? 0, 0)) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-black text-right font-bold">
                                ৳ {{ toBN(number_format($item->amount, 0)) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-black">
                        <tr>
                            <th scope="row" colspan="5" class="px-4 py-2 text-right text-sm font-bold text-black uppercase">সর্বমোট পরিশোধিত:</th>
                            <td class="px-4 py-2 text-right text-lg font-bold text-black">
                                ৳ {{ toBN(number_format($payment->amount_paid, 0)) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mb-4 text-black font-bold hind-siliguri">
                কথায়: {{ amountInWordsBN($payment->amount_paid) }}
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-2 mt-16 pt-8 break-inside-avoid">
                <div>
                    <div class="border-t border-slate-300 w-48 text-center pt-2">
                        <p class="text-sm text-slate-600 font-medium">শিক্ষার্থীর/অভিভাবকের স্বাক্ষর</p>
                    </div>
                </div>
                <div class="text-right flex flex-col items-end">
                    <div class="border-t border-slate-300 w-48 text-center pt-2">
                        <p class="text-sm text-slate-600 font-medium">আদায়কারীর স্বাক্ষর</p>
                    </div>
                </div>
            </div>

        <div class="mt-8 text-center border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-400 hind-siliguri">Generated by Batighor EIMS &bull; batighorbd.com</p>
        </div>

    </div>
</div>
@endsection
