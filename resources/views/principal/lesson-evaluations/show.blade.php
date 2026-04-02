@extends('layouts.admin')

@section('title', 'লেসন ইভালুয়েশন বিস্তারিত')

@push('styles')
<style>
    .detail-card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; }
    .stat-box { border-radius: 10px; padding: 15px; text-align: center; color: white; height: 100%; display: flex; flex-direction: column; justify-content: center; }
    .stat-box .count { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .stat-box .label { font-size: 0.85rem; font-weight: 500; opacity: 0.9; margin-top: 5px; }
    
    .bg-total { background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%); }
    .bg-completed { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .bg-partial { background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%); }
    .bg-not-done { background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); }
    .bg-absent { background: linear-gradient(135deg, #606c88 0%, #3f4c6b 100%); }

    .info-label { color: #6c757d; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
    .info-value { color: #2c3e50; font-size: 1rem; font-weight: 700; }
    
    .lesson-title-area { background: #f8f9fa; border-left: 5px solid #007bff; padding: 20px; border-radius: 0 10px 10px 0; margin-bottom: 25px; }
    .lesson-title-text { font-size: 1.4rem; font-weight: 800; color: #1a2a6c; margin-bottom: 0; }
    
    .student-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
    .table thead th { background: #f8f9fa; color: #495057; font-weight: 700; border-top: none; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    
    .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; border: 1px solid transparent; }
    .badge-status-completed { background-color: #e6fcf5; color: #0ca678; border-color: #c3fae8; }
    .badge-status-partial { background-color: #fff9db; color: #f08c00; border-color: #fff3bf; }
    .badge-status-not_done { background-color: #fff5f5; color: #f03e3e; border-color: #ffe3e3; }
    .badge-status-absent { background-color: #f8f9fa; color: #495057; border-color: #e9ecef; }
</style>
@endpush

@section('content')
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="font-weight-bold mb-0">ইভালুয়েশন বিস্তারিত</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('principal.institute.lesson-evaluations.index', $school) }}">রিপোর্ট</a></li>
                        <li class="breadcrumb-item active">বিস্তারিত</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('principal.institute.lesson-evaluations.index', $school) }}" class="btn btn-outline-secondary shadow-sm">
                    <i class="fas fa-arrow-left mr-1"></i> ফিরে যান
                </a>
                <button onclick="window.print()" class="btn btn-primary shadow-sm ml-2">
                    <i class="fas fa-print mr-1"></i> প্রিন্ট করুন
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-12">
            <div class="card detail-card mb-4">
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-3 border-right">
                            <div class="info-label">তারিখ ও সময়</div>
                            <div class="info-value">
                                <i class="far fa-calendar-alt mr-1 text-primary"></i> 
                                {{ $lessonEvaluation->evaluation_date ? $lessonEvaluation->evaluation_date->format('d-m-Y') : '-' }}
                                <div class="small text-muted">{{ $lessonEvaluation->evaluation_time ? $lessonEvaluation->evaluation_time->format('h:i A') : '' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 border-right">
                            <div class="info-label">শ্রেণি ও শাখা</div>
                            <div class="info-value">
                                <i class="fas fa-school mr-1 text-info"></i> 
                                {{ optional($lessonEvaluation->class)->name ?? '-' }}
                                <span class="badge badge-light border ml-1 px-2">{{ optional($lessonEvaluation->section)->name ?? 'শাখা নেই' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 border-right">
                            <div class="info-label">বিষয়</div>
                            <div class="info-value text-primary">
                                <i class="fas fa-book mr-1"></i> 
                                {{ optional($lessonEvaluation->subject)->name ?? '-' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">শিক্ষক</div>
                            <div class="info-value">
                                <i class="fas fa-user-tie mr-1 text-dark"></i> 
                                {{ optional($lessonEvaluation->teacher)->full_name ?? '-' }}
                            </div>
                        </div>
                    </div>

                    @if($lessonEvaluation->notes)
                        <div class="lesson-title-area">
                            <div class="info-label mb-2"><i class="fas fa-bookmark mr-1"></i> পাঠ / বিষয়ের শিরোনাম</div>
                            <div class="lesson-title-text">{{ $lessonEvaluation->notes }}</div>
                        </div>
                    @endif

                    <div class="row mt-4 mb-4">
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-total shadow-sm">
                                <div class="count">{{ $stats['total'] }}</div>
                                <div class="label">মোট শিক্ষার্থী</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-completed shadow-sm">
                                <div class="count">{{ $stats['completed'] }}</div>
                                <div class="label">পড়া হয়েছে</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-partial shadow-sm">
                                <div class="count">{{ $stats['partial'] }}</div>
                                <div class="label">আংশিক</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-not-done shadow-sm">
                                <div class="count">{{ $stats['not_done'] }}</div>
                                <div class="label">পড়া হয়নি</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-absent shadow-sm">
                                <div class="count">{{ $stats['absent'] }}</div>
                                <div class="label">অনুপস্থিত</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stat-box bg-info shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                                <div class="count">{{ $stats['completion_rate'] }}%</div>
                                <div class="label">সফলতার হার</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 80px;">ছবি</th>
                                    <th class="text-center" style="width: 100px;">রোল নো</th>
                                    <th>শিক্ষার্থীর নাম</th>
                                    <th class="text-center">স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lessonEvaluation->records as $record)
                                    <tr>
                                        <td class="text-center">
                                            <img src="{{ $record->student->photo_url ?? asset('images/default-avatar.svg') }}" 
                                                 class="student-img" 
                                                 onerror="this.src='{{ asset('images/default-avatar.svg') }}'"
                                                 alt="photo">
                                        </td>
                                        <td class="text-center font-weight-bold text-muted">{{ optional($record->student)->roll_no ?? '-' }}</td>
                                        <td class="font-weight-bold text-dark">{{ optional($record->student)->full_name ?? ('Student #' . $record->student_id) }}</td>
                                        <td class="text-center">
                                            <span class="badge-status badge-status-{{ $record->status }}">
                                                @if($record->status === 'completed') <i class="fas fa-check-circle mr-1"></i>
                                                @elseif($record->status === 'partial') <i class="fas fa-adjust mr-1"></i>
                                                @elseif($record->status === 'not_done') <i class="fas fa-times-circle mr-1"></i>
                                                @elseif($record->status === 'absent') <i class="fas fa-user-slash mr-1"></i>
                                                @endif
                                                {{ $record->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-5 text-muted">কোন রেকর্ড পাওয়া যায়নি।</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

