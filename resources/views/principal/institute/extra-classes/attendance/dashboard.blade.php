@extends('layouts.admin')

@section('title','এক্সট্রা ক্লাস উপস্থিতি ড্যাশবোর্ড')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="mb-0">এক্সট্রা ক্লাস উপস্থিতি ড্যাশবোর্ড ({{ $date }})</h4>
            <small class="text-muted">স্কুল: {{ $school->name }}</small>
        </div>
        <div class="col-md-6 text-md-right mt-2 mt-md-0">
            <form method="get" action="" class="form-inline justify-content-md-end">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm mr-2" />
                <button class="btn btn-sm btn-primary">দেখুন</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card" style="background:linear-gradient(135deg,#4f46e5,#6366f1); color:#fff;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 small">মোট শিক্ষার্থী</div>
                        <div class="display-4 font-weight-bold">{{ $totalStudents }}</div>
                    </div>
                    <div class="opacity-75"><i class="fas fa-users fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card" style="background:linear-gradient(135deg,#059669,#10b981); color:#fff;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 small">উপস্থিত (Late সহ)</div>
                        <div class="display-4 font-weight-bold">{{ $presentToday }}</div>
                    </div>
                    <div class="opacity-75"><i class="fas fa-user-check fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card" style="background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 small">অনুপস্থিত</div>
                        <div class="display-4 font-weight-bold">{{ $absentToday }}</div>
                    </div>
                    <div class="opacity-75"><i class="fas fa-user-times fa-2x"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card" style="background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 small">উপস্থিতির শতাংশ</div>
                        <div class="display-4 font-weight-bold">{{ $attendancePercent !== null ? $attendancePercent.'%' : '—' }}</div>
                    </div>
                    <div class="opacity-75"><i class="fas fa-percentage fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header py-2"><strong>লিঙ্গভিত্তিক উপস্থিতি (Pie)</strong></div>
                <div class="card-body" style="height:280px;">
                    <canvas id="genderPie" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header py-2"><strong>এক্সট্রা ক্লাসভিত্তিক উপস্থিতির শতাংশ (Bar)</strong></div>
                <div class="card-body" style="height:280px;">
                    <canvas id="classBar" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>এক্সট্রা ক্লাসভিত্তিক সারাংশ</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="min-width:150px;">এক্সট্রা ক্লাস</th>
                            <th class="text-center">মোট শিক্ষার্থী</th>
                            <th class="text-center">মোট ছেলে</th>
                            <th class="text-center">মোট মেয়ে</th>
                            <th class="text-center text-success">উপস্থিত ছেলে</th>
                            <th class="text-center text-danger">অনুপস্থিত ছেলে</th>
                            <th class="text-center text-success">উপস্থিত মেয়ে</th>
                            <th class="text-center text-danger">অনুপস্থিত মেয়ে</th>
                            <th class="text-center">মোট উপস্থিত</th>
                            <th class="text-center">মোট অনুপস্থিত</th>
                            <th class="text-center">উপস্থিতির হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($extraClassWise as $c)
                        <tr>
                            <td class="text-center">
                                {{ $c->extra_class_name }}
                                @if(!$c->att_taken)
                                    <span class="badge badge-secondary ml-1">হাজিরা হয়নি</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $c->total }}</td>
                            <td class="text-center">{{ $c->total_male }}</td>
                            <td class="text-center">{{ $c->total_female }}</td>
                            <td class="text-center text-success">{{ $c->present_male }}</td>
                            <td class="text-center text-danger">{{ $c->absent_male }}</td>
                            <td class="text-center text-success">{{ $c->present_female }}</td>
                            <td class="text-center text-danger">{{ $c->absent_female }}</td>
                            <td class="text-center">{{ $c->present_total }}</td>
                            <td class="text-center">{{ $c->absent_total }}</td>
                            <td class="text-center">{{ $c->percentage !== null ? $c->percentage.'%' : '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center">কোনও তথ্য নেই</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th class="text-center">সর্বমোট</th>
                            <th class="text-center">{{ $grandTotal }}</th>
                            <th class="text-center">{{ $extraClassWise->sum('total_male') }}</th>
                            <th class="text-center">{{ $extraClassWise->sum('total_female') }}</th>
                            <th class="text-center text-success">{{ $extraClassWise->sum('present_male') }}</th>
                            <th class="text-center text-danger">{{ $extraClassWise->sum('absent_male') }}</th>
                            <th class="text-center text-success">{{ $extraClassWise->sum('present_female') }}</th>
                            <th class="text-center text-danger">{{ $extraClassWise->sum('absent_female') }}</th>
                            <th class="text-center">{{ $grandPresent }}</th>
                            <th class="text-center">{{ $extraClassWise->sum('absent_total') }}</th>
                            <th class="text-center">{{ $grandPercent !== null ? $grandPercent.'%' : '—' }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>আজ অনুপস্থিত শিক্ষার্থীর তালিকা</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>নাম</th>
                            <th>এক্সট্রা ক্লাস</th>
                            <th>লিঙ্গ</th>
                            <th>ক্রমাগত অনুপস্থিত (দিন)</th>
                            <th>সর্বশেষ মন্তব্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absentees as $a)
                        <tr>
                            <td>{{ $a->student_name_bn ?? $a->student_name_en }}</td>
                            <td>{{ $a->class_name }}</td>
                            <td>{{ $a->gender }}</td>
                            <td>{{ $a->streak_days }}</td>
                            <td>{{ $a->latest_remarks }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">কোনও অনুপস্থিত শিক্ষার্থী নেই</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const genderCtx = document.getElementById('genderPie');
    const classCtx = document.getElementById('classBar');
    if (!genderCtx || !classCtx) return;
    const genderLabels = @json($genderLabels);
    const genderDataRaw = @json($genderData);
    const barLabels = @json($barLabels);
    const barDataRaw = @json($barData);
    // Fallbacks when arrays empty
    const pieLabels = (Array.isArray(genderLabels) && genderLabels.length) ? genderLabels : ['ডেটা নেই'];
    const pieData = (Array.isArray(genderDataRaw) && genderDataRaw.length) ? genderDataRaw.map(v=>v==null?0:v) : [1];
    const barLabelsFinal = (Array.isArray(barLabels) && barLabels.length) ? barLabels : ['ডেটা নেই'];
    const barDataFinal = (Array.isArray(barDataRaw) && barDataRaw.length) ? barDataRaw.map(v=>v==null?0:v) : [0];
    if (typeof Chart === 'undefined') return;
    new Chart(genderCtx, {
        type: 'pie',
        data: { labels: pieLabels, datasets: [{ data: pieData, backgroundColor: ['#3498db','#e74c3c','#9b59b6','#2ecc71'] }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
    new Chart(classCtx, {
        type: 'bar',
        data: { labels: barLabelsFinal, datasets: [{ label: 'উপস্থিতির %', data: barDataFinal, backgroundColor: '#2ecc71' }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
    });
})();
</script>
@endpush
