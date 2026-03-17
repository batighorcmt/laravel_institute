@extends('layouts.app')

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-receipt, #printable-receipt * {
            visibility: visible;
        }
        #printable-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="h-full bg-slate-50 relative overflow-y-auto no-print">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 max-h-screen">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800" style="font-family: 'Hind Siliguri', sans-serif;">পেমেন্ট রিসিট</h1>
            <div class="space-x-4">
                <a href="{{ route('billing.collect') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">ফিরে যান</a>
                <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm transition inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    প্রিন্ট করুন
                </button>
            </div>
        </div>

        <!-- Printable Area -->
        <div id="printable-receipt" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 printable bg-opacity-95" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
            
            <!-- School Header -->
            <div class="text-center border-b border-slate-200 pb-6 mb-6">
                <h2 class="text-3xl font-bold text-slate-900 mb-1" style="font-family: 'Hind Siliguri', sans-serif;">{{ $payment->school->name ?? 'স্কুল/প্রতিষ্ঠান' }}</h2>
                @if($payment->school && $payment->school->address)
                    <p class="text-slate-500 text-sm">{{ $payment->school->address }}</p>
                @endif
                <div class="inline-block mt-4 px-4 py-1 bg-slate-100 rounded-full text-sm font-semibold text-slate-600 tracking-wider">
                    ফিস কালেকশন রিসিট
                </div>
            </div>

            <!-- Meta Information -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2 tracking-wider">শিক্ষার্থীর তথ্য</h4>
                    <p class="font-bold text-lg text-indigo-900 mb-1">{{ $payment->student->full_name }}</p>
                    <p class="text-slate-600 text-sm">আইডি: <span class="font-medium text-slate-800">{{ $payment->student->student_id }}</span></p>
                    <p class="text-slate-600 text-sm mt-1">
                        শ্রেণি: <span class="font-medium text-slate-800">{{ $payment->student->currentEnrollment->class->name ?? '...' }}</span>
                        @if($payment->student->currentEnrollment && $payment->student->currentEnrollment->section)
                            | শাখা: <span class="font-medium text-slate-800">{{ $payment->student->currentEnrollment->section->name }}</span>
                        @endif
                    </p>
                    <p class="text-slate-600 text-sm mt-1">
                        রোল: <span class="font-medium text-slate-800">{{ $payment->student->currentEnrollment->roll_no ?? '...' }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2 tracking-wider">রিসিট তথ্য</h4>
                    <p class="text-slate-600 text-sm">রিসিট নং: <span class="font-bold text-slate-800">{{ $payment->payment_number }}</span></p>
                    <p class="text-slate-600 text-sm mt-1">তারিখ: <span class="font-medium text-slate-800">{{ $payment->received_at->format('d M, Y') }}</span></p>
                    <p class="text-slate-600 text-sm mt-1">পেমেন্ট মাধ্যম: <span class="font-medium text-slate-800 capitalize">{{ $payment->payment_method }}</span></p>
                </div>
            </div>

            <!-- Payment Items -->
            <div class="mb-8 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">বিবরণ (ফি-এর খাত)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">মাস</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">পরিমাণ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($payment->paymentItems as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 font-medium">
                                {{ $item->studentFee->feeStructure->category->name ?? 'ফি' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 text-center">
                                @if($item->studentFee->month)
                                    {{ date('M, Y', strtotime($item->studentFee->month . '-01')) }}
                                @else
                                    <span class="text-slate-400 text-xs">One-time</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 text-right font-semibold">
                                ৳ {{ number_format($item->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                        <tr>
                            <th scope="row" colspan="2" class="px-6 py-4 text-right text-sm font-bold text-slate-900 uppercase">মোট পরিশোধিত:</th>
                            <td class="px-6 py-4 text-right text-lg font-bold text-indigo-700">
                                ৳ {{ number_format($payment->amount_paid, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
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
                <p class="text-xs text-slate-400" style="font-family: 'Hind Siliguri', sans-serif;">Generated by Batighor EIMS &bull; batighorbd.com</p>
            </div>

        </div>

    </div>
</div>
@endsection
