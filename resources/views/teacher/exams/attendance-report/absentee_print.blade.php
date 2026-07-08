@extends('layouts.print')
@section('title', 'অনুপস্থিতি তালিকা')
@section('suppress_header')@endsection

@php
  if (!function_exists('bn_num')){
    function bn_num($v){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$v); }
  }

  $institute_name   = $schoolModel->name_bn   ?? $schoolModel->name   ?? '';
  $institute_addr   = $schoolModel->address_bn ?? $schoolModel->address ?? '';
  $institute_logo   = $schoolModel->logo ? asset('storage/'.$schoolModel->logo) : '';
  $plan_name        = $plan->name_bn ?? $plan->name ?? '';
  $shift            = match($plan->shift ?? '') {
      'Morning' => 'সকাল',
      'Day'     => 'দিবা',
      default   => 'বিকাল',
  };
@endphp

@push('print_head')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  *  { box-sizing: border-box; }
  body { font-family: 'Hind Siliguri', sans-serif; color: #000; background: #fff; margin: 0; }

  .actions { text-align: center; padding: 8px; border-bottom: 1px solid #ccc; }
  .btn-print { background: #2563eb; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-size: 15px; font-weight: 700; cursor: pointer; }

  .page  { width: 100%; max-width: 297mm; margin: 0 auto; padding: 8mm; }
  .sheet { background: #fff; border: 2px solid #000; border-radius: 4px; overflow: hidden; margin-bottom: 8mm; page-break-after: always; }
  .sheet:last-child { page-break-after: auto; }

  /* Header */
  .hdr { display: flex; align-items: center; gap: 14px; padding: 10px 16px; border-bottom: 2px solid #000; }
  .hdr-logo { width: 72px; height: 72px; flex-shrink: 0; }
  .hdr-logo img { width: 100%; height: 100%; object-fit: contain; }
  .hdr-brand { flex: 1; text-align: center; }
  .hdr-brand h1 { font-size: 22px; font-weight: 800; margin: 0 0 2px; }
  .hdr-brand p  { margin: 1px 0; font-size: 13px; }
  .hdr-brand .exam-name { font-size: 15px; font-weight: 700; margin-top: 3px; }
  .report-title { font-size: 17px; font-weight: 800; text-decoration: underline; margin-top: 4px; }

  /* Table */
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; font-size: 12px; }
  thead th { background: #f1f5f9; font-weight: 800; text-align: center; font-size: 13px; }
  td.serial, th.serial { width: 52px; text-align: center; }
  td.date-col, th.date-col { width: 110px; text-align: center; }

  /* Inside cell */
  .subject-label { font-weight: 800; font-size: 12px; padding-bottom: 3px; margin-bottom: 4px; border-bottom: 1px dashed #666; display: block; }
  .absent-list   { font-size: 12px; margin-top: 2px; }
  .absent-list div { padding: 1px 0; }
  .no-absent     { color: #555; font-style: italic; font-size: 11px; margin-top: 2px; }

  /* Footer */
  .footer-sigs { display: flex; justify-content: space-between; padding: 16px 20px 12px; }
  .sig-box { text-align: center; width: 220px; }
  .sig-line { border-top: 1px solid #000; margin-top: 50px; }
  .sig-lbl  { font-weight: 700; font-size: 12px; margin-top: 4px; }

  .brandbar { text-align: center; padding: 5px; font-size: 11px; font-weight: 600; border-top: 1px solid #000; }

  @media print {
    @page { size: A4 landscape; margin: 8mm; }
    .actions { display: none; }
    .page { padding: 0; }
    .sheet { border: none; box-shadow: none; border-radius: 0; }
  }
</style>
@endpush

@section('content')
<div class="actions">
  <button class="btn-print" onclick="window.print()">🖨️ প্রিন্ট করুন</button>
</div>

<div class="page">
  <div class="sheet">
    {{-- Header --}}
    <div class="hdr">
      <div class="hdr-logo">
        @if($institute_logo)
          <img src="{{ $institute_logo }}" alt="Logo">
        @endif
      </div>
      <div class="hdr-brand">
        <h1>{{ $institute_name }}</h1>
        <p>{{ $institute_addr }}</p>
        <p class="exam-name">{{ $plan_name }} ({{ $shift }} শিফট)</p>
        <p class="report-title">অনুপস্থিতি তালিকা</p>
      </div>
      <div style="width:72px;"></div>
    </div>

    {{-- Table --}}
    @if(empty($dates))
      <p style="text-align:center;padding:30px;">এই সীট প্ল্যানের কোনো তথ্য পাওয়া যায়নি।</p>
    @else
    <table>
      <thead>
        <tr>
          <th class="serial">ক্রমিক</th>
          <th class="date-col">তারিখ</th>
          @foreach($classes as $cName)
            <th>{{ $cName }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @php $sl = 1; @endphp
        @foreach($dates as $date)
        <tr>
          <td class="serial">{{ bn_num($sl++) }}</td>
          <td class="date-col">{{ bn_num(date('d/m/Y', strtotime($date))) }}</td>
          @foreach($classes as $cName)
            <td>
              @if(isset($matrix[$date][$cName]))
                <span class="subject-label">বিষয়ঃ {{ $matrix[$date][$cName]['subject'] ?? '' }}</span>
                @if(empty($matrix[$date][$cName]['absentees']))
                  <span class="no-absent">অনুপস্থিত নেই</span>
                @else
                  <div class="absent-list">
                    @foreach($matrix[$date][$cName]['absentees'] as $abs)
                      <div>{{ bn_num($abs->roll_no) }} - {{ $abs->student_name_bn ?: $abs->student_name_en }}</div>
                    @endforeach
                  </div>
                @endif
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    {{-- Signature footer --}}
    <div class="footer-sigs">
      <div class="sig-box">
        <div class="sig-line"></div>
        <div class="sig-lbl">পরীক্ষা নিয়ন্ত্রক</div>
      </div>
      <div class="sig-box">
        <div class="sig-line"></div>
        <div class="sig-lbl">অধ্যক্ষ / প্রধান শিক্ষক</div>
      </div>
    </div>

    <div class="brandbar">কারিগরি সহযোগীতায়ঃ বাতিঘর কম্পিউটার'স</div>
  </div>
</div>
@endsection
