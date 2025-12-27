@extends('layouts.print')

@section('title', 'ভর্তি অনুমতি তালিকা')

@section('content')
@php
    $title = 'ভর্তি অনুমোদনকৃত/অননুমোদিত শিক্ষার্থীদের তালিকা';
    $sub = [];
    if(($filters['class'] ?? '') !== '') $sub[] = 'ক্লাস: '.$filters['class'];
    if(($filters['permission'] ?? '') === '1') $sub[] = 'শুধু অনুমোদিত';
    elseif(($filters['permission'] ?? '') === '0') $sub[] = 'শুধু অননুমোদিত';
    if(($filters['fee_status'] ?? '') === 'paid') $sub[] = 'ফিস: পরিশোধিত';
    elseif(($filters['fee_status'] ?? '') === 'unpaid') $sub[] = 'ফিস: অপরিশোধিত';
    if(($filters['q'] ?? '') !== '') $sub[] = 'সার্চ: '.$filters['q'];
    $subtitle = implode(' | ', $sub);
@endphp

<div style="text-align:center; margin-bottom:10px;">
    <div style="font-size:14px; font-weight:700;">{{ $title }}</div>
    @if($subtitle)
        <div style="font-size:12px; color:#555;">{{ $subtitle }}</div>
    @endif
</div>

<style>
    .print-table { width:100%; border-collapse:collapse; }
    .print-table th, .print-table td { border:1px solid #333; padding:6px; }
    .print-table thead th { background:#f8f8f8; }
</style>

<div class="table-responsive">
    <table class="table print-table mb-0">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">App ID</th>
                <th style="text-align:center;">রোল নং</th>
                <th>নাম (বাংলা)</th>
                <th style="text-align:center;">ক্লাস</th>
                <th style="text-align:center;">মেধাক্রম</th>
                <th style="text-align:center;">অনুমতি</th>
                <th style="text-align:center;">নির্ধারিত ফিস</th>
                <th style="text-align:center;">ফিস</th>
                <th style="text-align:center;">মোবাইল</th>
                <th style="text-align:center;">গ্রাম</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $i => $app)
                <tr>
                    <td style="text-align:center;">{{ $i+1 }}</td>
                    <td style="text-align:center;">{{ $app->app_id ?? $app->id }}</td>
                    <td style="text-align:center;">{{ $app->admission_roll_no ? str_pad($app->admission_roll_no, 3, '0', STR_PAD_LEFT) : '—' }}</td>
                    <td>{{ $app->name_bn }}</td>
                    <td style="text-align:center;">{{ $app->class_name }}</td>
                    <td style="text-align:center;">{{ $app->merit_rank ?? '—' }}</td>
                    <td style="text-align:center;">{{ ($app->admission_permission ?? false) ? 'অনুমোদিত' : 'অননুমোদিত' }}</td>
                    <td style="text-align:center;">{{ isset($app->admission_fee) ? number_format($app->admission_fee, 2) : '—' }}</td>
                    <td style="text-align:center;">{{ ($app->admission_fee_paid ?? false) ? 'পরিশোধিত' : 'অপরিশোধিত' }}</td>
                    <td style="text-align:center;">{{ $app->mobile }}</td>
                    <td style="text-align:center;">{{ $app->present_village ?? $app->permanent_village ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; padding:20px;">কোনো শিক্ষার্থী পাওয়া যায়নি</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
