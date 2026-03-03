@extends('layouts.admin')

@section('title', 'সুপার এডমিন ড্যাশবোর্ড')
@section('nav.superadmin.dashboard', 'active')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-6">
            <h1 class="m-0">সুপার এডমিন ড্যাশবোর্ড</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <a href="{{ route('superadmin.schools.index') }}" class="small-box bg-info text-decoration-none d-block">
                <div class="inner">
                    <h3>{{ $data['total_schools'] ?? 0 }}</h3>
                    <p>মোট স্কুল</p>
                </div>
                <div class="icon"><i class="fas fa-school"></i></div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('superadmin.users.index') }}" class="small-box bg-success text-decoration-none d-block">
                <div class="inner">
                    <h3>{{ $data['total_users'] ?? 0 }}</h3>
                    <p>মোট ব্যবহারকারী</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner text-dark">
                    <h3>{{ $data['total_students'] ?? 0 }}</h3>
                    <p>মোট ছাত্র-ছাত্রী</p>
                </div>
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $data['total_subjects'] ?? 0 }}</h3>
                    <p>মোট বিষয়</p>
                </div>
                <div class="icon"><i class="fas fa-book"></i></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">মাসওয়ারি নতুন স্কুল</h3>
        </div>
        <div class="card-body" style="height:340px;">
            <canvas id="schoolsChart" aria-label="মাসওয়ারি নতুন স্কুল চার্ট" role="img"></canvas>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">স্বাগতম, {{ auth()->user()->name ?? 'ব্যবহারকারী' }}!</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">আপনি সুপার এডমিন হিসেবে লগইন করেছেন। এখানে আপনি:</p>
                    <div class="row">
                        <div class="col-md-3"><a href="{{ route('superadmin.schools.index') }}" class="text-decoration-none text-dark"><div class="border rounded p-3 h-100"><i class="fas fa-school text-primary"></i> <span class="ml-2">সকল স্কুল ব্যবস্থাপনা</span></div></a></div>
                        <div class="col-md-3"><a href="{{ route('superadmin.schools.create') }}" class="text-decoration-none text-dark"><div class="border rounded p-3 h-100"><i class="fas fa-plus-circle text-success"></i> <span class="ml-2">নতুন স্কুল যোগ</span></div></a></div>
                        <div class="col-md-3"><a href="{{ route('superadmin.users.index') }}" class="text-decoration-none text-dark"><div class="border rounded p-3 h-100"><i class="fas fa-users-cog text-warning"></i> <span class="ml-2">ব্যবহারকারী ব্যবস্থাপনা</span></div></a></div>
                        <div class="col-md-3"><a href="#" class="text-decoration-none text-dark"><div class="border rounded p-3 h-100"><i class="fas fa-cogs text-danger"></i> <span class="ml-2">সিস্টেম সেটিংস</span></div></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
<script>
// Sample Chart.js line chart (replace dataset with dynamic AJAX later)
document.addEventListener('DOMContentLoaded', () => {
    if (window.Chart && document.getElementById('schoolsChart')) {
        const ctx = document.getElementById('schoolsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'নতুন স্কুল',
                    data: [0,2,4,3,5,7,6,8,9,11,10,13],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.2)',
                    tension: .35,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true }
                }
            }
        });
    }
});
</script>
@endpush