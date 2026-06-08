@extends('layouts.admin')

@section('title','উপস্থিতি ড্যাশবোর্ড')

@push('styles')
<style>
/* ===== প্রিন্ট স্টাইল: শুধুমাত্র ক্লাসভিত্তিক সারাংশ টেবিল প্রিন্ট হবে ===== */
@media print {
    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    /* সব কিছু লুকাও */
    body * {
        visibility: hidden !important;
    }

    /* শুধু প্রিন্ট সেকশন এবং তার ভেতরের সব কিছু দেখাও */
    #printClassSummary,
    #printClassSummary * {
        visibility: visible !important;
    }

    /* প্রিন্ট সেকশন: position absolute ব্যবহার করলে অতিরিক্ত ফাঁকা পৃষ্ঠা হয় না */
    #printClassSummary {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* card এর border, shadow সরাও */
    #printClassSummary.card {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    /* card-header সরাও (প্রিন্টে আলাদা হেডার আছে) */
    #printClassSummary .card-header {
        display: none !important;
    }

    /* card-body padding সরাও */
    #printClassSummary .card-body {
        padding: 0 !important;
    }

    /* table-responsive overflow সরাও যাতে কলাম কাটা না যায় */
    #printClassSummary .table-responsive {
        overflow: visible !important;
        width: 100% !important;
    }

    /* প্রিন্ট হেডার: লোগো বামে, নাম মাঝে */
    #printPageHeader {
        display: flex !important;
        align-items: center !important;
        padding-bottom: 8px !important;
        margin-bottom: 8px !important;
        border-bottom: 2px solid #222 !important;
        width: 100% !important;
    }

    #printPageHeader .print-logo {
        width: 60px !important;
        height: 60px !important;
        object-fit: contain !important;
        margin-right: 14px !important;
        flex-shrink: 0 !important;
    }

    #printPageHeader .print-title-block {
        flex: 1;
        text-align: center;
    }

    #printPageHeader .print-school-name {
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #000 !important;
        line-height: 1.3 !important;
        display: block !important;
    }

    #printPageHeader .print-report-title {
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #333 !important;
        display: block !important;
        margin-top: 3px !important;
    }

    #printPageHeader .print-date {
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #111 !important;
        display: block !important;
        margin-top: 4px !important;
    }


    /* প্রিন্ট বাটন লুকাও */
    .btn-print-summary {
        display: none !important;
    }

    /*
     * tfoot ডিফল্টে প্রতি পাতায় রিপিট হয়।
     * display: table-row-group করলে শুধু শেষ পাতায় একবার দেখাবে।
     */
    #printClassSummary table tfoot {
        display: table-row-group !important;
    }

    /* টেবিল */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10px !important;
        table-layout: auto !important;
    }

    table th, table td {
        border: 1px solid #888 !important;
        padding: 3px 5px !important;
        text-align: center !important;
        white-space: normal !important;
        word-break: keep-all !important;
        vertical-align: middle !important;
        color: #000 !important;
    }

    /*
     * সমস্যা: ব্রাউজার ডিফল্টে background-color প্রিন্ট করে না।
     * তাই dark bg + white text → সাদা টেক্সট সাদা কাগজে অদৃশ্য হয়।
     * সমাধান: হালকা ব্যাকগ্রাউন্ড + কালো টেক্সট → সবসময় দৃশ্যমান।
     */
    table thead th {
        background-color: #e8e8e8 !important;
        color: #000 !important;
        font-size: 9px !important;
        font-weight: 800 !important;
        white-space: normal !important;
        border: 1.5px solid #333 !important;
        border-bottom: 2px solid #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* text-success / text-danger in thead — গাঢ় রঙে দেখাবে */
    table thead th.text-success {
        color: #14532d !important;
    }
    table thead th.text-danger {
        color: #7f1d1d !important;
    }

    table tfoot th {
        background-color: #e8e8e8 !important;
        color: #000 !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        border: 1.5px solid #333 !important;
        border-top: 2px solid #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .table-active td, .table-active th {
        background-color: #d9d9d9 !important;
        font-weight: 700 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* tbody-তে success/danger রঙ ঠিক রাখো */
    table tbody td.text-success { color: #14532d !important; }
    table tbody td.text-danger  { color: #7f1d1d !important; }

    .badge {
        border: 1px solid #666 !important;
        padding: 1px 3px !important;
        font-size: 8px !important;
        background: #f0f0f0 !important;
        color: #333 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

/* প্রিন্ট হেডার সাধারণ অবস্থায় লুকানো */
#printPageHeader {
    display: none;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="mb-0">উপস্থিতি ড্যাশবোর্ড ({{ $date }})</h4>
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
                <div class="card-header py-2"><strong>ক্লাসভিত্তিক উপস্থিতির শতাংশ (Bar)</strong></div>
                <div class="card-body" style="height:280px;">
                    <canvas id="classBar" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3" id="printClassSummary">
        {{-- প্রিন্টের সময় দেখানো হেডার (সাধারণ অবস্থায় লুকানো) --}}
        <div id="printPageHeader">
            @php
                $__printSchool = $school;
                $__logoUrl = ($__printSchool && $__printSchool->logo)
                    ? asset('storage/' . $__printSchool->logo)
                    : asset('images/batighorsoft.png');
                $__schoolNameBn = $__printSchool->name_bn ?? $__printSchool->name ?? '';
            @endphp
            <img src="{{ $__logoUrl }}" alt="লোগো" class="print-logo">
            <div class="print-title-block">
                <span class="print-school-name">{{ $__schoolNameBn }}</span>
                <span class="print-report-title">ক্লাসভিত্তিক উপস্থিতি সারাংশ (শাখাভিত্তিক + মোট)</span>
                <span class="print-date">তারিখ: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') ?? $date }}</span>
            </div>
        </div>
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>ক্লাসভিত্তিক সারাংশ (শাখাভিত্তিক + মোট)</strong>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted mr-3">নিচে প্রতিটি শ্রেণির শাখা এবং শ্রেণির মোট, শেষে সর্বমোট দেখানো হয়েছে</small>
                <button class="btn btn-sm btn-outline-secondary btn-print-summary" onclick="window.print()" title="এই সারাংশ প্রিন্ট করুন">
                    <i class="fas fa-print mr-1"></i> প্রিন্ট
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="min-width:160px;">শ্রেণি - শাখা</th>
                            <th class="text-center">মোট শিক্ষার্থী</th>
                            <th class="text-center">মোট ছেলে</th>
                            <th class="text-center">মোট মেয়ে</th>
                            <th class="text-center text-success">উপস্থিত ছেলে</th>
                            <th class="text-center text-danger">অনুপস্থিত ছেলে</th>
                            <th class="text-center text-success">উপস্থিত মেয়ে</th>
                            <th class="text-center text-danger">অনুপস্থিত মেয়ে</th>
                            <th class="text-center">মোট উপস্থিত</th>
                            <th class="text-center">মোট অনুপস্থিত</th>
                            <th class="text-center">উপস্থিতির হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasRows = false; @endphp
                        @foreach($classWise as $c)
                            @if(!empty($c->sections))
                                @php $hasRows = true; @endphp
                                @foreach($c->sections as $s)
                                    @php
                                        $presentTotal = (int)($s['present_total'] ?? 0);
                                        $absentTotal = (int)($s['absent_total'] ?? 0);
                                        $hasAtt = !empty($s['att_taken']);
                                        $percent = ($s['total']>0 && $hasAtt)
                                            ? number_format(($presentTotal/$s['total'])*100,1)
                                            : null;
                                        // Format: ClassName - SectionName (TeacherInitials)
                                        $sectionLabel = $c->class_name . ' - ' . $s['section_name'];
                                        if (!empty($s['teacher_initials'])) {
                                            $sectionLabel .= ' (' . $s['teacher_initials'] . ')';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            {{ $sectionLabel }}
                                            @if(!$hasAtt)
                                                <span class="badge badge-secondary ml-1">হাজিরা হয়নি</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $s['total'] }}</td>
                                        <td class="text-center">{{ $s['total_male'] }}</td>
                                        <td class="text-center">{{ $s['total_female'] }}</td>
                                        <td class="text-center text-success">{{ $s['present_male'] }}</td>
                                        <td class="text-center text-danger">{{ $s['absent_male'] }}</td>
                                        <td class="text-center text-success">{{ $s['present_female'] }}</td>
                                        <td class="text-center text-danger">{{ $s['absent_female'] }}</td>
                                        <td class="text-center">{{ $presentTotal }}</td>
                                        <td class="text-center">{{ $absentTotal }}</td>
                                        <td class="text-center">{{ $percent !== null ? $percent.'%' : '—' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-active font-weight-bold">
                                    <td class="text-center">মোট ({{ $c->class_name }})</td>
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
                            @endif
                        @endforeach
                        @if(!$hasRows)
                            <tr><td colspan="11" class="text-center">কোনও তথ্য নেই</td></tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th class="text-center">সর্বমোট</th>
                            <th class="text-center">{{ $grandTotal }}</th>
                            <th class="text-center">{{ $classWise->sum('total_male') }}</th>
                            <th class="text-center">{{ $classWise->sum('total_female') }}</th>
                            <th class="text-center text-success">{{ $classWise->sum('present_male') }}</th>
                            <th class="text-center text-danger">{{ $classWise->sum('absent_male') }}</th>
                            <th class="text-center text-success">{{ $classWise->sum('present_female') }}</th>
                            <th class="text-center text-danger">{{ $classWise->sum('absent_female') }}</th>
                            <th class="text-center">{{ $grandPresent }}</th>
                            <th class="text-center">{{ $classWise->sum('absent_total') }}</th>
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
                            <th>রোল</th>
                            <th>নাম</th>
                            <th>ক্লাস</th>
                            <th>শাখা</th>
                            <th>লিঙ্গ</th>
                            <th>ক্রমাগত অনুপস্থিত (দিন)</th>
                            <th>সর্বশেষ মন্তব্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absentees as $a)
                        <tr>
                            <td>{{ $a->roll_no }}</td>
                            <td>{{ $a->student_name_bn ?? $a->student_name_en }}</td>
                            <td>{{ $a->class_name }}</td>
                            <td>{{ $a->section_name }}</td>
                            <td>{{ $a->gender }}</td>
                            <td>{{ $a->streak_days }}</td>
                            <td>{{ $a->latest_remarks }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">কোনও অনুপস্থিত শিক্ষার্থী নেই</td></tr>
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
