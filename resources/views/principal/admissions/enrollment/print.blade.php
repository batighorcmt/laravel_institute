@extends('layouts.print')

@php
    $lang = request('lang','bn');
    $titleBn = 'ভর্তি অনুমোদনকৃত/অননুমোদিত শিক্ষার্থীদের তালিকা';
    $titleEn = 'Approved/Unapproved Enrollment List';
    $printTitle = $lang==='bn' ? $titleBn : $titleEn;
    $subs = [];
    if(($filters['class'] ?? '') !== '') $subs[] = ($lang==='bn' ? 'ক্লাস: ' : 'Class: ') . $filters['class'];
    if(($filters['permission'] ?? '') === '1') $subs[] = $lang==='bn' ? 'শুধু অনুমোদিত' : 'Only Approved';
    elseif(($filters['permission'] ?? '') === '0') $subs[] = $lang==='bn' ? 'শুধু অননুমোদিত' : 'Only Unapproved';
    if(($filters['fee_status'] ?? '') === 'paid') $subs[] = $lang==='bn' ? 'ফিস: পরিশোধিত' : 'Fee: Paid';
    elseif(($filters['fee_status'] ?? '') === 'unpaid') $subs[] = $lang==='bn' ? 'ফিস: অপরিশোধিত' : 'Fee: Unpaid';
    if(($filters['q'] ?? '') !== '') $subs[] = ($lang==='bn' ? 'সার্চ: ' : 'Search: ') . $filters['q'];
    $printSubtitle = implode(' | ', $subs);

    // Digit formatter (Bangla digits if bn)
    $fmt = function($v) use ($lang){
        if($v===null) return '—';
        $s = (string)$v;
        if($lang!=='bn') return $s;
        $d = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr($s,$d);
    };
@endphp

@section('title', $printTitle)
@section('content')

<!-- Filters & Sorting (screen-only) -->
<div class="no-print" style="margin-bottom:12px; padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:6px; display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
    <form id="filterForm" method="GET" action="{{ url()->current() }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <div>
                    <label style="font-weight:600;">{{ $lang==='bn' ? 'রোল নং' : 'Roll No.' }}</label>
                    <input type="text" name="roll" class="form-control form-control-sm" style="min-width:100px;" value="{{ $filters['roll'] ?? '' }}" placeholder="{{ $lang==='bn' ? 'রোল নং' : 'Roll No.' }}">
                </div>
        <div>
            <label style="font-weight:600;">{{ $lang==='bn' ? 'ক্লাস' : 'Class' }}</label>
            <select name="class" class="form-control form-control-sm" style="min-width:120px;">
                <option value="">{{ $lang==='bn' ? 'সকল' : 'All' }}</option>
                @isset($classes)
                    @foreach($classes as $cls)
                        <option value="{{ $cls }}" {{ (isset($filters['class']) && $filters['class']===(string)$cls) ? 'selected' : '' }}>{{ $cls }}</option>
                    @endforeach
                @endisset
            </select>
        </div>
        <div>
            <label style="font-weight:600;">{{ $lang==='bn' ? 'অনুমতি' : 'Permission' }}</label>
            <select name="permission" class="form-control form-control-sm" style="min-width:140px;">
                <option value="" {{ ($filters['permission'] ?? '')==='' ? 'selected' : '' }}>{{ $lang==='bn' ? 'সকল' : 'All' }}</option>
                <option value="1" {{ ($filters['permission'] ?? '')==='1' ? 'selected' : '' }}>{{ $lang==='bn' ? 'অনুমোদিত' : 'Approved' }}</option>
                <option value="0" {{ ($filters['permission'] ?? '')==='0' ? 'selected' : '' }}>{{ $lang==='bn' ? 'অননুমোদিত' : 'Unapproved' }}</option>
            </select>
        </div>
        <div>
            <label style="font-weight:600;">{{ $lang==='bn' ? 'ফিস' : 'Fee' }}</label>
            <select name="fee_status" class="form-control form-control-sm" style="min-width:140px;">
                <option value="" {{ ($filters['fee_status'] ?? '')==='' ? 'selected' : '' }}>{{ $lang==='bn' ? 'সকল' : 'All' }}</option>
                <option value="paid" {{ ($filters['fee_status'] ?? '')==='paid' ? 'selected' : '' }}>{{ $lang==='bn' ? 'পরিশোধিত' : 'Paid' }}</option>
                <option value="unpaid" {{ ($filters['fee_status'] ?? '')==='unpaid' ? 'selected' : '' }}>{{ $lang==='bn' ? 'অপরিশোধিত' : 'Unpaid' }}</option>
            </select>
        </div>
        <div>
            <label style="font-weight:600;">{{ $lang==='bn' ? 'সাজান' : 'Sort by' }}</label>
            <select name="sort" class="form-control form-control-sm">
                @php($sortSel = request('sort','class'))
                <option value="class" {{ $sortSel==='class' ? 'selected' : '' }}>{{ $lang==='bn' ? 'ক্লাস' : 'Class' }}</option>
                <option value="roll" {{ $sortSel==='roll' ? 'selected' : '' }}>{{ $lang==='bn' ? 'রোল' : 'Roll' }}</option>
                <option value="merit" {{ $sortSel==='merit' ? 'selected' : '' }}>{{ $lang==='bn' ? 'মেধাক্রম' : 'Merit' }}</option>
            </select>
            <select name="order" class="form-control form-control-sm">
                @php($orderSel = request('order','asc'))
                <option value="asc" {{ $orderSel==='asc' ? 'selected' : '' }}>{{ $lang==='bn' ? 'ছোট→বড়' : 'Ascending' }}</option>
                <option value="desc" {{ $orderSel==='desc' ? 'selected' : '' }}>{{ $lang==='bn' ? 'বড়→ছোট' : 'Descending' }}</option>
            </select>
        </div>
        <div style="align-self:flex-end;">
            <button type="submit" class="btn btn-sm btn-primary">{{ $lang==='bn' ? 'প্রয়োগ' : 'Apply' }}</button>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">{{ $lang==='bn' ? 'রিসেট' : 'Reset' }}</a>
        </div>
    </form>
</div>

<style>
    .print-table { width:100%; border-collapse:collapse; }
    .print-table th, .print-table td { border:1px solid #333; padding:6px; }
    .print-table thead th { background:#f8f8f8; }
    .fee-paid { color: #198754; font-weight:700; }
    .fee-unpaid { color: #dc3545; font-weight:700; }
</style>

<div class="table-responsive">
    @php($totalAssignedFee = $applications->sum('admission_fee'))
    <table class="table print-table mb-0">
        <thead>
            <tr>
                <th style="text-align:center;">{{ $lang==='bn' ? 'ক্রম' : 'No.' }}</th>
                <th style="text-align:center;">App ID</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'রোল নং' : 'Roll' }}</th>
                <th>{{ $lang==='bn' ? 'নাম' : 'Name' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'ক্লাস' : 'Class' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'মেধাক্রম' : 'Merit' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'অনুমতি' : 'Permission' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'নির্ধারিত ফিস' : 'Assigned Fee' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'মোট ফি' : 'Total Fee' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'ফিস' : 'Fee Status' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'মোবাইল' : 'Mobile' }}</th>
                <th style="text-align:center;">{{ $lang==='bn' ? 'গ্রাম' : 'Village' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $i => $app)
                <tr>
                    <td style="text-align:center;">{{ $fmt($i+1) }}</td>
                    <td style="text-align:center;">{{ $app->app_id ?? $app->id }}</td>
                    <td style="text-align:center;">{{ $fmt($app->admission_roll_no ? str_pad($app->admission_roll_no, 3, '0', STR_PAD_LEFT) : null) }}</td>
                    <td>{{ $lang==='bn' ? ($app->name_bn ?? $app->name_en) : ($app->name_en ?? $app->name_bn) }}</td>
                    <td style="text-align:center;">{{ $fmt($app->class_name) }}</td>
                    <td style="text-align:center;">{{ $fmt($app->merit_rank) }}</td>
                    <td style="text-align:center;">{{ ($app->admission_permission ?? false) ? ($lang==='bn' ? 'অনুমোদিত' : 'Approved') : ($lang==='bn' ? 'অননুমোদিত' : 'Unapproved') }}</td>
                    <td style="text-align:center;">{{ isset($app->admission_fee) ? ($lang==='bn' ? $fmt(number_format($app->admission_fee, 2)) : number_format($app->admission_fee, 2)) : '—' }}</td>
                    <td style="text-align:center;">{{ isset($app->admission_fee) ? ($lang==='bn' ? $fmt(number_format($app->admission_fee, 2)) : number_format($app->admission_fee, 2)) : '—' }}</td>
                    <td style="text-align:center;">
                        @if(isset($app->admission_fee_paid) && $app->admission_fee_paid)
                            <span class="fee-paid">{{ $lang==='bn' ? 'পরিশোধিত' : 'Paid' }}</span>
                        @else
                            <span class="fee-unpaid">{{ $lang==='bn' ? 'অপরিশোধিত' : 'Unpaid' }}</span>
                            @if(auth()->check() && auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('enrollment.fee.pay', [$school->id, $app->id]) }}" style="display:inline; margin-left:6px;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">{{ $lang==='bn' ? 'পে করুন' : 'Pay' }}</button>
                                </form>
                            @endif
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $lang==='bn' ? $fmt(preg_replace('/[^0-9]/','', $app->mobile)) : preg_replace('/[^0-9]/','', $app->mobile) }}</td>
                    <td style="text-align:center;">{{ $lang==='bn' ? ($app->present_village ?? $app->permanent_village ?? '—') : ($app->present_village ?? $app->permanent_village ?? '—') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align:center; padding:20px;">কোনো শিক্ষার্থী পাওয়া যায়নি</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" style="text-align:right; font-weight:700;">{{ $lang==='bn' ? 'মোট নির্ধারিত ফিস' : 'Total Assigned Fee' }}</td>
                <td style="text-align:center; font-weight:700;">{{ isset($totalAssignedFee) ? ($lang==='bn' ? $fmt(number_format($totalAssignedFee,2)) : number_format($totalAssignedFee,2)) : '0.00' }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
