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
                <table class="table table-bordered table-striped table-hover m-0">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 120px;">দিন</th>
                            <th style="width: 80px;">পিরিয়ড</th>
                            <th>বিষয়</th>
                            <th style="width: 180px;">সময়</th>
                            <th style="width: 200px;">শিক্ষক</th>
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
                            @if(isset($routine[$enDay]) && count($routine[$enDay]) > 0)
                                @foreach($routine[$enDay] as $index => $item)
                                <tr>
                                    @if($index === 0)
                                    <td style="font-weight: bold; vertical-align: middle;" rowspan="{{ count($routine[$enDay]) }}">{{ $bnDay }}</td>
                                    @endif
                                    <td class="text-center font-weight-bold">{{ $item->period_number }}</td>
                                    <td class="text-primary font-weight-bold">{{ $item->subject->name }}</td>
                                    <td class="text-muted">{{ \Carbon\Carbon::parse($item->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('h:i A') }}</td>
                                    <td>{{ $item->teacher ? ($item->teacher->full_name_bn ?: $item->teacher->full_name) : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td style="font-weight: bold;">{{ $bnDay }}</td>
                                <td colspan="4" class="text-muted text-center">কোনো ক্লাস নেই</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
