<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'অ্যাডমিন')</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .navbar-search { min-width: 220px; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Header -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-widget="pushmenu" role="button"><i class="fa-solid fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ url('/') }}" class="nav-link">হোম</a>
                </li>
            </ul>

            <form class="form-inline mr-3 d-none d-md-flex" onsubmit="event.preventDefault();">
                <div class="input-group navbar-search">
                    <input class="form-control" type="search" placeholder="সার্চ..." aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>

            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item mr-2">
                    <button id="themeToggle" class="btn btn-outline-secondary" title="ডার্ক/লাইট" data-toggle="tooltip">
                        <i class="fas fa-adjust"></i>
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-danger navbar-badge">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg">
                        <span class="dropdown-header">কোন নোটিফিকেশন নেই</span>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i> {{ auth()->user()->name ?? 'User' }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <form method="POST" action="{{ route('logout') }}" class="px-3 py-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> লগআউট
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('superadmin.dashboard') }}" class="brand-link">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="brand-image img-circle elevation-3" width="33" height="33" style="opacity:.9">
            <span class="brand-text font-weight-light">স্কুল ম্যানেজমেন্ট</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @php($u = auth()->user())
                    @if($u && $u->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('superadmin.dashboard') }}" class="nav-link @yield('nav.superadmin.dashboard')">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>ড্যাশবোর্ড</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.schools.index') }}" class="nav-link {{ request()->routeIs('superadmin.schools.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-school"></i>
                                <p>স্কুল সমূহ</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>ব্যবহারকারী</p>
                            </a>
                        </li>
                    @elseif($u && $u->isPrincipal())
                        <li class="nav-item">
                            <a href="{{ route('principal.dashboard') }}" class="nav-link {{ request()->routeIs('principal.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>ড্যাশবোর্ড</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-university"></i>
                                <p>
                                    Institute
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute') }}" class="nav-link {{ request()->routeIs('principal.institute') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>প্রতিষ্ঠান তালিকা</p>
                                    </a>
                                </li>
                                @if(method_exists($u,'primarySchool') && $u->primarySchool())
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.manage', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.manage') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>ম্যানেজ করুন</p>
                                        </a>
                                    </li>
                                    <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.students.*') || request()->routeIs('principal.institute.teams.*') ? 'menu-open' : '' }}">
                                        <a href="#" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.students.*') || request()->routeIs('principal.institute.teams.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>একাডেমিক সেটআপ <i class="right fas fa-angle-left"></i></p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.shifts.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>শিফট</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.classes.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>ক্লাস</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.sections.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sections.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>সেকশন</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.routine.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>ক্লাস রুটিন</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.teachers.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teachers.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>শিক্ষক</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.groups.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.groups.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>গ্রুপ</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.subjects.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.subjects.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>বিষয়</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.academic-years.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.academic-years.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>শিক্ষাবর্ষ</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.students.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>শিক্ষার্থী</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('principal.institute.teams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teams.*') ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>বিশেষ দল/গ্রুপ</p>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>
                                    Attendance
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.attendance.class.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.class*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Class Attendance</p>
                                    </a>
                                </li>
                                @if($u->primarySchool())
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.attendance.dashboard', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.dashboard') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Attendance Dashboard</p>
                                    </a>
                                </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.holidays.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.holidays.*') || request()->routeIs('principal.institute.weekly-holidays.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Holiday Management</p>
                                        </a>
                                    </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.attendance.team.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.team.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Team Attendance</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.attendance.monthly_report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.monthly_report') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Monthly Report</p>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @if($u->primarySchool())
                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.holidays.*') || request()->routeIs('principal.institute.sms.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.holidays.*') || request()->routeIs('principal.institute.sms.*') || request()->routeIs('principal.institute.admissions.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>
                                    Settings
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.holidays.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.holidays.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Holiday Management</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.sms.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>SMS Settings</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.sms.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.panel') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>SMS Panel</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.sms.logs', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.logs*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>SMS Logs</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.payments.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.payments.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Online Payments</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.admissions.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.admissions.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-graduate"></i>
                                <p>
                                    Admission
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.admissions.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.settings*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Settings</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.admissions.applications', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.applications') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Applications</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principal.institute.admissions.payments', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.payments') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Payment History</p>
                                    </a>
                                </li>
                                @php($currentApp = request()->route('application'))
                                @if($currentApp)
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.admissions.applications.show', [$u->primarySchool()->id, $currentApp->id]) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.applications.show') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Application Details</p>
                                        </a>
                                    </li>
                                    @if($currentApp->accepted_at)
                                        <li class="nav-item">
                                            <a href="{{ route('principal.institute.admissions.applications.admit_card', [$u->primarySchool()->id, $currentApp->id]) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.applications.admit_card') ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Admit Card</p>
                                            </a>
                                        </li>
                                    @endif
                                @endif
                            </ul>
                        </li>
                        @endif
                    @else
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>ড্যাশবোর্ড</p>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Main -->
    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        {{-- Removed hard-coded version to keep print footer clean --}}
        <strong>&copy; {{ date('Y') }} {{ config('app.name', 'স্কুল ম্যানেজমেন্ট সিস্টেম') }}.</strong> সকল অধিকার সংরক্ষিত। 
        <div class="float-right d-none d-sm-inline">Version 1.0.0</div>
        {{-- <strong>&copy; {{ date('Y') }} স্কুল ম্যানেজমেন্ট সিস্টেম।</strong> সকল অধিকার সংরক্ষিত. --}}
    </footer>
</div>

<!-- Scripts bundled via Vite for AdminLTE v3.2 -->
@php($flash = session()->get('success') ?? session()->get('status') ?? null)
@if($flash)
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { toastr.success(@json($flash)); }
        });
    </script>
    @endpush
@endif
@if(session('error'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { toastr.error(@json(session('error'))); }
        });
    </script>
    @endpush
@endif
@stack('scripts')
</body>
</html>