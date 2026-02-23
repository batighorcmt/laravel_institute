@extends('layouts.admin')
@section('title', 'লেসন ইভ্যালুয়েশন রিপোর্ট')

@push('styles')
<style>
    .summary-table th { background-color: #f4f6f9; color: #495057; font-weight: 600; border-top: none; }
    .summary-table td { vertical-align: middle; }
    .summary-badge { min-width: 35px; border-radius: 5px; font-weight: bold; }
    #summaryModal .modal-header { border-bottom: none; }
    #summaryModal .modal-title { color: #007bff; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-chart-line mr-2"></i> লেসন ইভ্যালুয়েশন রিপোর্ট</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="card mb-4">
        <div class="card-header bg-secondary">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> ফিল্টার</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('parent.evaluations') }}" method="GET">
                <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>তারিখ</label>
                            <input type="date" name="date" class="form-control" value="{{ $filterDate }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>বিষয়</label>
                            <select name="subject_id" class="form-control select2">
                                <option value="">সকল বিষয়</option>
                                @foreach($subjects as $cs)
                                <option value="{{ $cs->subject_id }}" {{ request('subject_id') == $cs->subject_id ? 'selected' : '' }}>
                                    {{ $cs->subject->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>অবস্থা</label>
                            <select name="status" class="form-control">
                                <option value="">সকল অবস্থা</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>পড়া হয়েছে</option>
                                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>আংশিক হয়েছে</option>
                                <option value="not_done" {{ request('status') == 'not_done' ? 'selected' : '' }}>পড়া হয়নি</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>অনুপস্থিত</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2 flex-grow-1">সার্চ করুন</button>
                                <a href="{{ route('parent.evaluations', ['student_id' => $selectedStudent->id]) }}" class="btn btn-outline-secondary">রিসেট</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-danger">
            <h3 class="card-title">লেসন ইভ্যালুয়েশন রিপোর্ট</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>তারিখ</th>
                            <th>বিষয়</th>
                            <th>লেসন/অধ্যায়</th>
                            <th>অবস্থা</th>
                            <th>মন্তব্য</th>
                            <th>শিক্ষক</th>
                            <th class="text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $eval)
                        <tr>
                            <td>{{ $eval->lessonEvaluation->evaluation_date ? $eval->lessonEvaluation->evaluation_date->format('d M, Y') : 'N/A' }}</td>
                            <td>{{ $eval->lessonEvaluation->subject->name ?? 'N/A' }}</td>
                            <td>{{ $eval->lessonEvaluation->lesson_name }}</td>
                            <td>
                                <span class="badge badge-{{ $eval->status_color }}">
                                    {{ $eval->status_label }}
                                </span>
                            </td>
                            <td>{{ $eval->remarks }}</td>
                            <td>{{ $eval->lessonEvaluation->teacher->name ?? 'N/A' }}</td>
                            <td class="text-right">
                                <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#summaryModal">
                                    সারাংশ
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">কোনো ইভ্যালুয়েশন রেকর্ড পাওয়া যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $evaluations->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" role="dialog" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="summaryModalLabel">
                    <i class="fas fa-clipboard-check mr-2"></i> লেসন ইভলুশন রিপোর্ট ({{ $currentYear }})
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table summary-table mb-0">
                        <thead>
                            <tr>
                                <th>বিষয়ের নাম</th>
                                <th>শিক্ষকের নাম</th>
                                <th class="text-center">পড়া হয়েছে</th>
                                <th class="text-center">আংশিক হয়েছে</th>
                                <th class="text-center">হয় নাই</th>
                                <th class="text-center">অনুপস্থিত</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaryData as $subjectId => $data)
                            <tr>
                                <td class="font-weight-bold">{{ $data['subject_name'] }}</td>
                                <td class="text-muted small">{{ $data['teacher_name'] }}</td>
                                <td class="text-center">
                                    <span class="badge badge-success summary-badge">{{ $data['completed'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning summary-badge" style="background-color: #ffc107; color: #000;">{{ $data['partial'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-danger summary-badge">{{ $data['not_done'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary summary-badge">{{ $data['absent'] }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">কোনো তথ্য পাওয়া যায়নি।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ করুন</button>
            </div>
        </div>
    </div>
</div>
@endsection
