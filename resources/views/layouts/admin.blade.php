<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'অ্যাডমিন')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/batighor-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/batighor-favicon.png') }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .navbar-search { min-width: 220px; }
        
        /* Sidebar Hierarchical Styling */
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link {
            font-weight: 500;
            padding: 0.7rem 1rem;
            border-left: 3px solid transparent;
        }
        
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: rgba(255,255,255,.1) !important;
            border-left-color: #007bff;
        }
        
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link:hover {
            background-color: rgba(255,255,255,.05);
        }
        
        /* First level submenu (nav-treeview) */
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link {
            padding-left: 2.5rem;
            font-size: 0.9rem;
            color: rgba(255,255,255,.7);
        }
        
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link:hover {
            background-color: rgba(255,255,255,.05);
            color: #fff;
        }
        
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link.active {
            background-color: rgba(255,255,255,.08);
            color: #fff;
        }
        
        /* Second level submenu (nested nav-treeview) */
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link {
            padding-left: 3.5rem;
            font-size: 0.85rem;
            color: rgba(255,255,255,.6);
        }
        
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link:hover {
            background-color: rgba(255,255,255,.04);
            color: #fff;
        }
        
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link.active {
            background-color: rgba(255,255,255,.06);
            color: #fff;
        }
        
        /* Third level submenu */
        .sidebar-dark-primary .nav-treeview .nav-treeview .nav-treeview > .nav-item > .nav-link {
            padding-left: 4.5rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,.5);
        }
        
        /* Icons for submenu items */
        .nav-treeview .nav-icon {
            font-size: 0.7rem;
            margin-left: 0.25rem;
            margin-right: 0.5rem;
        }
        
        /* Parent menu item with treeview */
        .sidebar-dark-primary .nav-item.has-treeview > .nav-link .right {
            transition: transform 0.3s ease;
        }
        
        .sidebar-dark-primary .nav-item.has-treeview.menu-open > .nav-link .right {
            transform: rotate(90deg);
        }
        
        /* Add spacing between major menu groups */
        .nav-sidebar > .nav-item {
            margin-bottom: 0.2rem;
        }
        
        /* Submenu background */
        .nav-treeview {
            background-color: rgba(0,0,0,.1);
            padding-top: 0.3rem;
            padding-bottom: 0.3rem;
        }
        
        /* Nested submenu darker background */
        .nav-treeview .nav-treeview {
            background-color: rgba(0,0,0,.15);
        }
        
        .nav-treeview .nav-treeview .nav-treeview {
            background-color: rgba(0,0,0,.2);
        }
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
                    <a href="{{ url('/') }}" class="nav-link">Home</a>
                </li>
            </ul>

            <form class="form-inline mr-3 d-none d-md-flex" onsubmit="event.preventDefault();">
                <div class="input-group navbar-search">
                    <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>

            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item mr-2">
                    <button id="themeToggle" class="btn btn-outline-secondary" title="Dark/Light" data-toggle="tooltip">
                        <i class="fas fa-adjust"></i>
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-danger navbar-badge">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg">
                        <span class="dropdown-header">No notifications</span>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i> {{ auth()->user()->name ?? 'User' }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="{{ route('profile.show') }}" class="dropdown-item">
                            <i class="fas fa-id-card mr-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" class="px-3 py-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="brand-image img-circle elevation-3" width="33" height="33" style="opacity:.9">
            <span class="brand-text font-weight-light">Batighor EIMS</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @php($u = auth()->user())
                    @if($u && $u->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('superadmin.dashboard') }}" class="nav-link @yield('nav.superadmin.dashboard')">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.schools.index') }}" class="nav-link {{ request()->routeIs('superadmin.schools.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-school"></i>
                                <p>Schools</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </a>
                        </li>
                    @elseif($u && $u->isPrincipal())
                        <li class="nav-item">
                            <a href="{{ route('principal.dashboard') }}" class="nav-link {{ request()->routeIs('principal.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        {{-- Academic Setup (remaining items) --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.teams.*') || request()->routeIs('principal.institute.academic-years.*') || request()->routeIs('principal.institute.extra-classes.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.teams.*') || request()->routeIs('principal.institute.academic-years.*') || request()->routeIs('principal.institute.extra-classes.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>Academic Setup <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.shifts.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Shift</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.classes.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Class</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sections.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sections.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Section</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.routine.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Class Routine</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.groups.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.groups.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Group</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.subjects.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.subjects.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Subject</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.academic-years.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.academic-years.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Academic Year</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Extra Class</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teams.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Special Team/Group</p></a></li>
                                </ul>
                                </li>

                                 {{-- Teacher Attendance --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.teacher-attendance.*') || request()->routeIs('teacher.attendance.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.*') || request()->routeIs('teacher.attendance.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-clock"></i>
                                    <p>Teacher Attendance <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('teacher.attendance.index') }}" class="nav-link {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>আমার হাজিরা</p></a></li>
                                    <li class="nav-item"><a href="{{ route('teacher.attendance.my-attendance') }}" class="nav-link {{ request()->routeIs('teacher.attendance.my-attendance') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>হাজিরার রেকর্ড</p></a></li>
                                    <li class="nav-item"><a href="{{ route('teacher.leave.index') }}" class="nav-link {{ request()->routeIs('teacher.leave.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>আমার ছুটি</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-attendance.reports.daily', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.reports.daily') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>দৈনিক রিপোর্ট</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-attendance.reports.monthly', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.reports.monthly') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>মাসিক রিপোর্ট</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-leaves.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-leaves.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Teacher Leaves</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-attendance.settings.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.settings.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                </ul>
                            </li>

                            @if(method_exists($u,'primarySchool') && $u->primarySchool())
                                {{-- Attendance (top-level) --}}
                                <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance*') || request()->routeIs('principal.institute.holidays*') ? 'menu-open' : '' }}">
                                    <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance*') || request()->routeIs('principal.institute.holidays*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-calendar-check"></i>
                                        <p>Student Attendance <i class="right fas fa-angle-left"></i></p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        {{-- Class Attendance --}}
                                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance.class*') ? 'menu-open' : '' }}">
                                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance.class*') ? 'active' : '' }}">
                                                <i class="far fa-dot-circle nav-icon"></i>
                                                <p>Class Attendance <i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.class.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.class.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Attendance</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.monthly_report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.monthly_report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Monthly Report</p></a></li>
                                            </ul>
                                        </li>

                                        {{-- Team Attendance --}}
                                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance.team*') ? 'menu-open' : '' }}">
                                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance.team*') ? 'active' : '' }}">
                                                <i class="far fa-dot-circle nav-icon"></i>
                                                <p>Team Attendance <i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.team.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.team.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Attendance</p></a></li>
                                                <li class="nav-item"><a href="#" class="nav-link"><i class="far fa-circle nav-icon"></i><p>View Reports</p></a></li>
                                            </ul>
                                        </li>

                                        {{-- Extra Class Attendance --}}
                                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.extra-classes.attendance*') ? 'menu-open' : '' }}">
                                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance*') ? 'active' : '' }}">
                                                <i class="far fa-dot-circle nav-icon"></i>
                                                <p>Extra Class Attendance <i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.take') || request()->routeIs('principal.institute.extra-classes.attendance.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Attendance</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.daily-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Daily Report</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.monthly-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Monthly Report</p></a></li>
                                            </ul>
                                        </li>

                                        {{-- Common Items --}}
                                        <li class="nav-item"><a href="{{ route('principal.institute.attendance.dashboard', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.dashboard') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Attendance Dashboard</p></a></li>
                                        <li class="nav-item"><a href="{{ route('principal.institute.holidays.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.holidays.*') || request()->routeIs('principal.institute.weekly-holidays.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Holiday Management</p></a></li>
                                    </ul>
                                </li>
                            {{-- Teachers (top-level) --}}
                            <li class="nav-item">
                                <a href="{{ route('principal.institute.teachers.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teachers.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                    <p>Teacher</p>
                                </a>
                            </li>

                            {{-- Manage Exam --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.exams.*') || request()->routeIs('principal.institute.seat-plans.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.exams.*') || request()->routeIs('principal.institute.seat-plans.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>Manage Exam <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>পরীক্ষা তালিকা</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.seat-plans.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.seat-plans.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>সিট প্ল্যান</p></a></li>
                                </ul>
                            </li>

                            {{-- Manage Results --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.results.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.results.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Manage Results <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.results.marksheet', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.results.marksheet') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>মার্কশিট</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.marks.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.marks.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>নম্বর Entry</p></a></li>
                                </ul>
                            </li>

                            {{-- Students (top-level) --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.students.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.students.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>Student <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>List</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.bulk', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.bulk') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Bulk student add</p></a></li>
                                </ul>
                            </li>

                        
                            {{-- SMS (top-level) --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.sms.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.sms.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-sms"></i>
                                    <p>SMS <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>SMS Settings</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.panel') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>SMS Panel</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.logs', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.logs*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>SMS Logs</p></a></li>
                                </ul>
                            </li>

                            {{-- Settings (other) --}}
                            <li class="nav-item">
                                <a href="{{ route('principal.institute.payments.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.payments.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-credit-card"></i>
                                    <p>Online Payments</p>
                                </a>
                            </li>

                           

                            {{-- Admissions --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.admissions.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.admissions.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>Admission <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.settings*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.applications', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.applications') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Applications</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.payments', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.payments') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Payment History</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.enrollment.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.enrollment.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Confirm Enrollment</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.exams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.exams.index') || request()->routeIs('principal.institute.admissions.exams.create') || request()->routeIs('principal.institute.admissions.exams.edit') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Manage Exam</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.seat-plans.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.seat-plans.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Manage Seat Plan</p></a></li>
                                    @php($currentExam = request()->route('exam'))
                                    @if($currentExam)
                                        <li class="nav-item"><a href="{{ route('principal.institute.admissions.exams.marks', [$u->primarySchool(), $currentExam]) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.exams.marks') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Marks Entry</p></a></li>
                                        <li class="nav-item"><a href="{{ route('principal.institute.admissions.exams.results', [$u->primarySchool(), $currentExam]) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.exams.results') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Results</p></a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @elseif($u && $u->isTeacher())
                        {{-- Teacher Dashboard --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        {{-- My Attendance History (records only) --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.attendance.index') }}" class="nav-link {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>My Attendance</p>
                            </a>
                        </li>

                        {{-- Teacher Leave --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.leave.index') }}" class="nav-link {{ request()->routeIs('teacher.leave.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-plane-departure"></i>
                                <p>My Leaves</p>
                            </a>
                        </li>

                        {{-- Student Attendance --}}
                        @if($u->primarySchool())
                        {{-- Student Attendance (Class, Extra Class, Team) --}}
                        <li class="nav-item has-treeview {{ request()->routeIs('teacher.institute.attendance.class.*') || request()->routeIs('teacher.institute.attendance.extra-classes.*') || request()->routeIs('teacher.institute.attendance.team.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('teacher.institute.attendance.class.*') || request()->routeIs('teacher.institute.attendance.extra-classes.*') || request()->routeIs('teacher.institute.attendance.team.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-check"></i>
                                <p>Student Attendance <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.class.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.class.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Class Attendance</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.extra-classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.extra-classes.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Extra Class Attendance</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.team.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.team.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Team Attendance</p></a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.homework.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.homework.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book-reader"></i>
                                <p>Homework</p>
                            </a>
                        </li>
                        {{-- Lesson Evaluation --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.lesson-evaluation.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.lesson-evaluation.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-clipboard-check"></i>
                                <p>Lesson Evaluation</p>
                            </a>
                        </li>

                        {{-- Directories --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.directory.students', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.directory.students*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Students</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.directory.teachers', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.directory.teachers') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                <p>Teachers</p>
                            </a>
                        </li>
                        @endif
                    @else
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
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
        <strong>&copy; {{ date('Y') }} {{ config('app.name', 'School Management System') }}.</strong> All rights reserved. 
        <div class="float-right d-none d-sm-inline">Version 1.0.0</div>
        {{-- <strong>&copy; {{ date('Y') }} স্কুল ম্যানেজমেন্ট সিস্টেম।</strong> সকল অধিকার সংরক্ষিত. --}}
    </footer>
</div>

<!-- Scripts bundled via Vite for AdminLTE v3.2 -->
@if(session('success'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { 
                toastr.success(@json(session('success')));
            }
        });
    </script>
    @endpush
@endif
@if(session('status'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { 
                toastr.success(@json(session('status')));
            }
        });
    </script>
    @endpush
@endif
@if(session('info'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { 
                toastr.info(@json(session('info')));
            }
        });
    </script>
    @endpush
@endif
@if(session('error'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            if (window.toastr) { 
                toastr.error(@json(session('error')));
            }
        });
    </script>
    @endpush
@endif
@yield('scripts')
@stack('scripts')
</body>
</html>