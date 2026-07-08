@extends('layouts.admin')

@section('title', 'Absentee Report')

@php
  $institute_name_bn = $schoolModel->name_bn ?? $schoolModel->name ?? 'Institute Name';
  $institute_address_bn = $schoolModel->address_bn ?? $schoolModel->address ?? '';
  $institute_logo = $schoolModel->logo ? asset('storage/'.$schoolModel->logo) : '';

  if (!function_exists('bn_num')){
    function bn_num($v){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$v); }
  }
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  .print-area { font-family:'Hind Siliguri', sans-serif !important; color: #000; }
  .table-bordered th, .table-bordered td { border: 1px solid #000 !important; vertical-align: middle !important; }
  .text-center { text-align: center; }
  
  @media print {
      @page { size: A4 landscape; margin: 10mm; }
  }
</style>
@endpush

@section('content')
<div class="content-header no-print">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4>Absentee Report</h4>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Absentee Report</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card mb-3 no-print">
            <div class="card-body">
                <form id="absenteeForm" class="form-inline" method="GET" action="{{ auth()->user()->isPrincipal($schoolModel->id) ? route('principal.institute.exams.attendance-report.absentee', $schoolModel) : route('teacher.institute.exams.attendance-report.absentee', $schoolModel) }}">
                    <div class="form-row align-items-end w-100">
                        <div class="form-group mr-2">
                            <label class="mr-2">Seat Plan</label>
                            <select id="statPlan" name="plan_id" class="form-control" onchange="this.form.submit()" required>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $plan_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ml-auto">
                            @php
                                $printRoute = auth()->user()->isPrincipal($schoolModel->id) 
                                    ? route('principal.institute.exams.attendance-report.absentee', [$schoolModel, 'plan_id' => $plan_id, 'action' => 'print']) 
                                    : route('teacher.institute.exams.attendance-report.absentee', [$schoolModel, 'plan_id' => $plan_id, 'action' => 'print']);
                            @endphp
                            <a href="{{ $printRoute }}" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Print</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card print-area" id="printableArea">
            <div class="card-body">
                @if($plan)
                    <div class="text-center mb-4" style="position: relative;">
                        @if($institute_logo)
                            <div style="position: absolute; left: 0; top: 0;">
                                <img src="{{ $institute_logo }}" alt="Logo" style="height: 80px;">
                            </div>
                        @endif
                        <h2 style="margin: 0; font-weight: bold; font-size: 24px;">{{ $institute_name_bn }}</h2>
                        <p style="margin: 0; font-size: 16px;">{{ $institute_address_bn }}</p>
                        <p style="margin: 5px 0 0 0; font-size: 16px;">{{ $plan->name_bn ?? $plan->name }} ({{ $plan->shift === 'Morning' ? 'সকাল' : ($plan->shift === 'Day' ? 'দিবা' : 'বিকাল') }} শিফট)</p>
                        <h4 style="margin: 5px 0 0 0; font-weight: bold; text-decoration: underline; font-size: 18px;">অনুপস্থিতি তালিকা</h4>
                    </div>

                    @if(empty($dates))
                        <div class="alert alert-info text-center no-print">এই সীট প্ল্যানের কোনো তথ্য পাওয়া যায়নি।</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">ক্রমিক</th>
                                        <th style="width: 120px;">তারিখ</th>
                                        @foreach($classes as $cName)
                                            <th>{{ $cName }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $sl = 1; @endphp
                                    @foreach($dates as $date)
                                        <tr>
                                            <td>{{ bn_num($sl++) }}</td>
                                            <td>{{ bn_num(date('d/m/Y', strtotime($date))) }}</td>
                                            @foreach($classes as $cName)
                                                <td>
                                                    @if(isset($matrix[$date][$cName]))
                                                        <div style="font-weight: bold; margin-bottom: 5px; border-bottom: 1px dashed #000; display: inline-block;">বিষয়ঃ {{ $matrix[$date][$cName]['subject'] ?? 'অজানা' }}</div>
                                                        @if(empty($matrix[$date][$cName]['absentees']))
                                                            <div style="margin-top: 5px;">অনুপস্থিত নেই</div>
                                                        @else
                                                            @foreach($matrix[$date][$cName]['absentees'] as $abs)
                                                                <div style="margin-top: 3px;">{{ bn_num($abs->roll_no) }} - {{ $abs->student_name_bn ?: $abs->student_name_en }}</div>
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
