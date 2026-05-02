@extends('layouts.admin')

@section('title', 'শিক্ষক ভিত্তিক লেসন ইভ্যালুয়েশন রিপোর্ট')

@push('styles')
<style>
    .badge-print { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: white; min-width: 25px; }
    .badge-total { background-color: #17a2b8; }
    .badge-entered { background-color: #28a745; }
    .badge-missing { background-color: #dc3545; }
</style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">শিক্ষক ভিত্তিক লেসন ইভ্যালুয়েশন রিপোর্ট</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">তারিখ হতে</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">তারিখ পর্যন্ত</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="small font-weight-bold">শিক্ষক নির্বাচন করুন</label>
                        <select name="teacher_id" class="form-control form-control-sm select2" required>
                            <option value="">নির্বাচন করুন</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $teacherId == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->full_name }} {{ $teacher->initials ? '['.$teacher->initials.']' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <button type="submit" class="btn btn-sm btn-primary px-3">রিপোর্ট দেখুন</button>
                        @if($teacherId && $reportData->isNotEmpty())
                            <a href="{{ route('principal.institute.lesson-evaluations.teacher-report-print', [$school] + request()->query() + ['lang' => 'bn']) }}" target="_blank" class="btn btn-sm btn-info ml-1">
                                <i class="fa fa-print"></i> প্রিন্ট করুন
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if($teacherId)
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th width="50" rowspan="2" class="align-middle">ক্র.নং</th>
                                <th class="text-left align-middle" rowspan="2">শ্রেণি ও শাখা</th>
                                <th class="text-left align-middle" rowspan="2">বিষয়ের নাম</th>
                                <th colspan="3">ক্লাস তথ্য</th>
                                <th colspan="4">শিক্ষার্থী পরিসংখ্যান (এন্ট্রিকৃত ক্লাস সমূহের মোট)</th>
                            </tr>
                            <tr class="text-center">
                                <th title="Total Scheduled Classes">মোট ক্লাস</th>
                                <th title="Entries Done">এন্ট্রি হয়েছে</th>
                                <th title="Missing Entries">এন্ট্রি হয়নি</th>
                                <th class="bg-success text-white">পড়া হয়েছে</th>
                                <th class="bg-warning">আংশিক</th>
                                <th class="bg-danger text-white">পড়া হয়নি</th>
                                <th class="bg-secondary text-white">অনুপস্থিত</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totals = ['routine'=>0, 'entered'=>0, 'missing'=>0, 'comp'=>0, 'part'=>0, 'not'=>0, 'abs'=>0])
                            
                            @forelse($reportData as $index => $row)
                                @php($totals['routine'] += $row['total_classes'])
                                @php($totals['entered'] += $row['entered'])
                                @php($totals['missing'] += $row['missing'])
                                @php($totals['comp'] += $row['completed_students'])
                                @php($totals['part'] += $row['partial_students'])
                                @php($totals['not'] += $row['not_done_students'])
                                @php($totals['abs'] += $row['absent_students'])
                                
                                <tr class="text-center">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-left font-weight-bold">{{ $row['class_name'] }} [{{ $row['section_name'] }}]</td>
                                    <td class="text-left">{{ $row['subject'] }}</td>
                                    <td><span class="badge-print badge-total">{{ $row['total_classes'] }}</span></td>
                                    <td><span class="badge-print badge-entered">{{ $row['entered'] }}</span></td>
                                    <td><span class="badge-print badge-missing">{{ $row['missing'] }}</span></td>
                                    <td class="text-success font-weight-bold">{{ $row['completed_students'] }}</td>
                                    <td class="text-warning font-weight-bold">{{ $row['partial_students'] }}</td>
                                    <td class="text-danger font-weight-bold">{{ $row['not_done_students'] }}</td>
                                    <td class="text-secondary font-weight-bold">{{ $row['absent_students'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-3">এই শিক্ষকের রুটিনে কোন ক্লাস খুঁজে পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($reportData->isNotEmpty())
                        <tfoot class="bg-dark text-white">
                            <tr class="text-center font-weight-bold">
                                <td colspan="3" class="text-right">সর্বমোট = </td>
                                <td>{{ $totals['routine'] }}</td>
                                <td>{{ $totals['entered'] }}</td>
                                <td>{{ $totals['missing'] }}</td>
                                <td>{{ $totals['comp'] }}</td>
                                <td>{{ $totals['part'] }}</td>
                                <td>{{ $totals['not'] }}</td>
                                <td>{{ $totals['abs'] }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    রিপোর্ট দেখতে অনুগ্রহ করে শিক্ষক নির্বাচন করুন।
                </div>
            @endif
        </div>
    </div>
@endsection
