@extends('layouts.admin')

@section('title', 'Payment Invoice')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Payment Invoice</h1>
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>

    <div class="bg-white shadow rounded p-4">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <h2 class="font-semibold mb-2">School</h2>
                <p>{{ $school->name }}</p>
                @if(!empty($school->address))
                <p>{{ $school->address }}</p>
                @endif
                <p>Code: {{ $school->code }}</p>
            </div>
            <div class="text-right">
                <h2 class="font-semibold mb-2">Invoice</h2>
                <p>Invoice #: {{ $payment->id }}</p>
                <p>Date: {{ $payment->created_at->format('Y-m-d H:i') }}</p>
                <p>Status: <span class="badge {{ $payment->status === 'Completed' ? 'bg-green-600' : ($payment->status === 'Failed' ? 'bg-red-600' : 'bg-gray-600') }}">{{ $payment->status }}</span></p>
            </div>
        </div>

        <hr class="my-4">

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <h2 class="font-semibold mb-2">Applicant</h2>
                @if($application)
                    <p>Name: {{ $application->name_bn ?? $application->name_en }}</p>
                    <p>App ID: {{ $application->app_id }}</p>
                    <p>Class: {{ $application->class_name }}</p>
                    <p>Payment Status: {{ $application->payment_status }}</p>
                @else
                    <p>Application not available</p>
                @endif
            </div>
            <div>
                <h2 class="font-semibold mb-2">Payment Details</h2>
                <p>Amount: {{ number_format($payment->amount, 2) }} BDT</p>
                <p>Transaction ID: {{ $payment->transaction_id }}</p>
                <p>Gateway: {{ $payment->gateway ?? 'SSLCommerz' }}</p>
                @if(!empty($payment->method))
                <p>Method: {{ $payment->method }}</p>
                @endif
            </div>
        </div>

        @if(is_array($payment->meta))
        <div class="mt-4">
            <h2 class="font-semibold mb-2">Gateway Meta</h2>
            <pre class="bg-gray-50 p-3 rounded text-xs">{{ json_encode($payment->meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif

        <div class="mt-6 text-sm text-gray-500">
            <p>This is a system generated invoice for record keeping.</p>
        </div>
    </div>
</div>
@endsection
