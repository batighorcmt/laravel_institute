@extends('layouts.admin')
@section('title','স্কুল রুটিন (মাস্টার)')

@push('styles')
<style>
.table-routine th, .table-routine td { vertical-align: top; font-size:13px; }
.cell-box { margin:0 0 4px 0; padding:0; border:none; background:transparent; }
.cell-box .sub { font-weight:600; display:block; }
.cell-box .cls { color:#000; display:block; }
.teacher-col { font-weight: bold; width: 15%; text-align: right; }
</style>
@endpush

@section('content')

@php
  $bnMap = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-calendar-alt mr-1"></i> স্কুল রুটিন (মাস্টার রুটিন)</h1>
  <div>
    <a href="{{ route('principal.institute.routine.master-print', ['school' => $school->id, 'day_of_week' => $selectedDay]) }}" target="_blank" class="btn btn-primary"><i class="fas fa-print mr-1"></i> প্রিন্ট ভিউ</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <form action="{{ route('principal.institute.routine.master', $school) }}" method="GET" class="form-inline">
      <label for="day_of_week" class="mr-2 font-weight-bold">দিন নির্বাচন করুন:</label>
      <select name="day_of_week" id="day_of_week" class="form-control mr-3" onchange="this.form.submit()">
        @foreach($days as $dk => $dn)
          <option value="{{ $dk }}" {{ $selectedDay == $dk ? 'selected' : '' }}>{{ $dn }}</option>
        @endforeach
      </select>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">{{ $days[$selectedDay] }} - রুটিন ম্যাট্রিক্স</h3>
  </div>
  <div class="card-body">
    @if($maxPeriod <= 0 || $entries->isEmpty())
      <div class="alert alert-warning">এই দিনে কোনো ক্লাস নির্ধারিত নেই।</div>
    @else
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-routine text-center table-hover">
          <thead class="bg-light">
            <tr>
              <th style="width:180px">শিক্ষক</th>
              @for($p=1; $p<=$maxPeriod; $p++)
                <th>পিরিয়ড {{ strtr($p, $bnMap ?? []) }}</th>
              @endfor
            </tr>
          </thead>
          <tbody>
            @foreach($teachers as $teacher)
              @php
                // Check if teacher has any class this day (optional, if we want to show all teachers or only those with classes. Usually master routine shows all teachers)
              @endphp
              <tr>
                <th class="align-middle text-left">
                  {{ $teacher->user->name ?? 'Unknown' }} 
                  @if($teacher->initials) <small class="text-muted">({{ $teacher->initials }})</small> @endif
                </th>
                @for($p=1; $p<=$maxPeriod; $p++)
                  @php($list = collect($entries[$teacher->id.'#'.$p] ?? []))
                  <td class="align-middle">
                    @forelse($list as $e)
                      <div class="cell-box">
                        <div class="sub">{{ $e->subject?->name ?? '' }}</div>
                        <div class="cls small">{{ $e->class?->name ?? '' }} {{ $e->section ? '- '.$e->section->name : '' }}</div>
                        @if($e->room)
                          <div class="room small text-muted">রুম: {{ strtr($e->room, $bnMap ?? []) }}</div>
                        @endif
                      </div>
                    @empty
                      <span class="text-muted">—</span>
                    @endforelse
                  </td>
                @endfor
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
