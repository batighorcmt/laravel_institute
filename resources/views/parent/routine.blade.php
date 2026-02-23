@extends('layouts.admin')
@section('title', 'ক্লাস রুটিন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-calendar-alt mr-2"></i> ক্লাস রুটিন</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="card">
        <div class="card-header bg-info">
            <h3 class="card-title">সাপ্তাহিক ক্লাস রুটিন</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped m-0">
                    <thead>
                        <tr class="bg-light">
                            <th>দিন</th>
                            <th>পিরিয়ড ও সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $days = [
                                'Saturday' => 'শনিবার',
                                'Sunday' => 'রবিবার',
                                'Monday' => 'সোমবার',
                                'Tuesday' => 'মঙ্গলবার',
                                'Wednesday' => 'বুধবার',
                                'Thursday' => 'বৃহস্পতিবার',
                                'Friday' => 'শুক্রবার'
                            ];
                        @endphp
                        @foreach($days as $enDay => $bnDay)
                        <tr>
                            <td style="width: 120px; font-weight: bold;">{{ $bnDay }}</td>
                            <td>
                                @if(isset($routine[$enDay]))
                                    <div class="d-flex flex-wrap">
                                        @foreach($routine[$enDay] as $item)
                                        <div class="border rounded p-2 m-1 bg-white" style="min-width: 150px;">
                                            <div class="text-primary font-weight-bold">{{ $item->subject->name }}</div>
                                            <div class="text-sm">পিরিয়ড: {{ $item->period_number }}</div>
                                            <div class="text-sm text-muted">সময়: {{ \Carbon\Carbon::parse($item->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('h:i A') }}</div>
                                            <div class="text-sm">শিক্ষক: {{ $item->teacher->name }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">কোনো ক্লাস নেই</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
