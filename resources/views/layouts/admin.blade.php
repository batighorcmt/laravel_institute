<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'অ্যাডমিন')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
    <script>
        (function(){
            function ensureSelect2(){
                if (window.$ && $.fn && $.fn.select2) return;
                if (window.__select2_cdn_loading) return;
                window.__select2_cdn_loading = true;
                var css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css'; css.crossOrigin = 'anonymous'; document.head.appendChild(css);
                var js = document.createElement('script'); js.src = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js'; js.async = true; js.crossOrigin = 'anonymous';
                document.head.appendChild(js);
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensureSelect2); else ensureSelect2();
        })();
    </script>
    <style>
        .navbar-search { min-width: 220px; }

        /* ============================================================
           MODERN SIDEBAR UI
           Applies to every role block (superadmin / principal / teacher)
           since they all share the same nav-sidebar / nav-item / nav-link /
           nav-icon / has-treeview markup — one shared stylesheet, one look.
           ============================================================ */

        .main-sidebar.sidebar-dark-primary {
            background: linear-gradient(180deg, #1e1b4b 0%, #171433 55%, #12101f 100%);
        }

        .main-sidebar .brand-link {
            border-bottom: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.02);
        }

        .main-sidebar .brand-image {
            box-shadow: 0 0 0 3px rgba(99,102,241,.4);
        }

        /* Thin, theme-matched scrollbar for long menus */
        .sidebar { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.15) transparent; }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.28); }

        /* Top-level menu items: rounded, glowing gradient when active */
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link {
            font-weight: 500;
            padding: 0.5rem 0.85rem;
            margin: 0.05rem 0.6rem;
            line-height: 1.3;
            border-radius: 0.7rem;
            border-left: none;
            color: rgba(255,255,255,.75);
            transition: background .25s ease, transform .18s ease, box-shadow .25s ease, color .2s ease;
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link:hover {
            background: rgba(255,255,255,.08);
            color: #fff;
            transform: translateX(2px);
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
            color: #fff !important;
            box-shadow: 0 6px 16px -4px rgba(79,70,229,.55);
        }

        /* Icon "chip" for top-level items */
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link > .nav-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255,255,255,.07);
            margin-right: .65rem;
            font-size: .85rem;
            transition: background .25s ease, transform .25s ease;
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link:hover > .nav-icon {
            background: rgba(255,255,255,.16);
            transform: scale(1.08) rotate(-4deg);
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active > .nav-icon {
            background: rgba(255,255,255,.22);
        }

        /* Parent (has-treeview) row while its submenu is open */
        .sidebar-dark-primary .nav-item.has-treeview.menu-open > .nav-link {
            background: rgba(255,255,255,.06);
            color: #fff;
        }

        .sidebar-dark-primary .nav-item.has-treeview > .nav-link .right {
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
            opacity: .6;
        }

        .sidebar-dark-primary .nav-item.has-treeview.menu-open > .nav-link .right {
            transform: rotate(90deg);
            opacity: 1;
        }

        /* First level submenu (nav-treeview) */
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link {
            padding: 0.32rem 1rem 0.32rem 2.4rem;
            margin: 0.05rem 0.6rem 0.05rem 0.4rem;
            border-radius: 0.55rem;
            line-height: 1.25;
            font-size: 0.86rem;
            color: rgba(255,255,255,.62);
            transition: background .2s ease, color .2s ease, padding-left .2s ease;
        }

        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #fff;
            padding-left: 2.6rem;
        }

        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link.active {
            background: rgba(99,102,241,.2);
            color: #fff;
        }

        /* Second level submenu (nested nav-treeview) */
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link {
            padding: 0.28rem 1rem 0.28rem 3.4rem;
            margin: 0.05rem 0.6rem 0.05rem 0.4rem;
            border-radius: 0.5rem;
            line-height: 1.2;
            font-size: 0.82rem;
            color: rgba(255,255,255,.52);
            transition: background .2s ease, color .2s ease;
        }

        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link:hover {
            background: rgba(255,255,255,.05);
            color: #fff;
        }

        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link.active {
            background: rgba(99,102,241,.16);
            color: #fff;
        }

        /* Third level submenu */
        .sidebar-dark-primary .nav-treeview .nav-treeview .nav-treeview > .nav-item > .nav-link {
            padding: 0.24rem 1rem 0.24rem 4.3rem;
            margin: 0.05rem 0.6rem 0.05rem 0.4rem;
            border-radius: 0.5rem;
            line-height: 1.2;
            font-size: 0.78rem;
            color: rgba(255,255,255,.42);
        }

        /* Submenu dot icons: recolor + glow instead of a plain grey dot */
        .nav-treeview .nav-icon {
            font-size: 0.55rem;
            margin-right: 0.55rem;
            color: rgba(255,255,255,.35);
            transition: color .2s ease, text-shadow .2s ease;
        }

        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link:hover .nav-icon,
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link:hover .nav-icon {
            color: rgba(255,255,255,.75);
        }

        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link.active .nav-icon,
        .sidebar-dark-primary .nav-treeview .nav-treeview > .nav-item > .nav-link.active .nav-icon {
            color: #a5b4fc;
            text-shadow: 0 0 6px rgba(165,180,252,.85);
        }

        /* Spacing between top-level groups */
        .nav-sidebar > .nav-item {
            margin-bottom: 0.05rem;
        }

        /* Submenu container: soft rounded "card" feel instead of a flat strip */
        .nav-treeview {
            background: rgba(0,0,0,.14);
            border-radius: 0.6rem;
            margin: 0.15rem 0.4rem 0.35rem 0.4rem;
            padding: 0.25rem 0;
        }

        .nav-treeview .nav-treeview {
            background: rgba(0,0,0,.16);
            margin: 0.1rem 0;
        }

        .nav-treeview .nav-treeview .nav-treeview {
            background: rgba(0,0,0,.22);
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    @if(session()->has('impersonated_by'))
    <div class="bg-warning text-dark text-center py-2 font-weight-bold shadow-sm" style="position: relative; z-index: 1050;">
        আপনি এখন {{ session('impersonated_school_name', 'প্রতিষ্ঠান') }} এর প্রধান হিসেবে লগইন আছেন। 
        <form action="{{ route('impersonate.leave') }}" method="POST" class="d-inline ml-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark"><i class="fas fa-sign-out-alt mr-1"></i> Leave Impersonation</button>
        </form>
    </div>
    @endif

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
                        @if(\Illuminate\Support\Facades\Route::has('profile.show'))
                            <a href="{{ route('profile.show') }}" class="dropdown-item">
                                <i class="fas fa-id-card mr-2"></i> Profile
                            </a>
                        @endif
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
        @php
            $__u = auth()->user();
            $__school = null;
            if ($__u && method_exists($__u, 'primarySchool')) {
                $__school = $__u->primarySchool();
            }
            $logoUrl = ($__school && $__school->logo) ? asset('storage/'.$__school->logo).'?v=' . ($__school->id ?? time()) : asset('images/batighorsoft.png');
        @endphp
        <a href="#" class="brand-link">
            <img src="{{ $logoUrl }}" alt="Logo" class="brand-image img-circle elevation-3" width="33" height="33" style="opacity:.9">
            <span class="brand-text font-weight-light">Batighor EIMS</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="true">
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
                            <a href="{{ route('superadmin.app-updates.index') }}" class="nav-link {{ request()->routeIs('superadmin.app-updates.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-mobile-alt"></i>
                                <p>App Updates</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs('superadmin.designations.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('superadmin.designations.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Settings <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('superadmin.designations.index') }}" class="nav-link {{ request()->routeIs('superadmin.designations.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Designations</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('superadmin.mobile-settings.edit') }}" class="nav-link {{ request()->routeIs('superadmin.mobile-settings.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Mobile App</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs('superadmin.website.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('superadmin.website.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-globe"></i>
                                <p>Website <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('superadmin.website.themes.index') }}" class="nav-link {{ request()->routeIs('superadmin.website.themes.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Themes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('superadmin.website.menu-templates.index') }}" class="nav-link {{ request()->routeIs('superadmin.website.menu-templates.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Menu Templates</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('superadmin.website.page-templates.index') }}" class="nav-link {{ request()->routeIs('superadmin.website.page-templates.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Page Templates</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{--Principal Menu Items--}}
                    @elseif($u && $u->isPrincipal())
                        <li class="nav-item">
                            <a href="{{ route('principal.dashboard') }}" class="nav-link {{ request()->routeIs('principal.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        {{-- Academic Setup (remaining items) --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.teams.*') || request()->routeIs('principal.institute.academic-years.*') || request()->routeIs('principal.institute.extra-classes.*') || request()->routeIs('principal.institute.routine.*') || request()->routeIs('principal.institute.result-settings.*') || request()->routeIs('principal.institute.fcm.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') || request()->routeIs('principal.institute.sections.*') || request()->routeIs('principal.institute.groups.*') || request()->routeIs('principal.institute.classes.*') || request()->routeIs('principal.institute.subjects.*') || request()->routeIs('principal.institute.teams.*') || request()->routeIs('principal.institute.academic-years.*') || request()->routeIs('principal.institute.extra-classes.*') || request()->routeIs('principal.institute.routine.*') || request()->routeIs('principal.institute.result-settings.*') || request()->routeIs('principal.institute.fcm.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>Academic Setup <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.fcm.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.fcm.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Push Diagnostics</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.shifts.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.shifts.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Shift</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.classes.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Class</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sections.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sections.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Section</p></a></li>
                                    @if(auth()->user()->hasModule('routine'))
                                    <li class="nav-item"><a href="{{ route('principal.institute.routine.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.panel') || request()->routeIs('principal.institute.routine.print') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Class Routine</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.routine.teacher-panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.teacher*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Teacher Routine</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.routine.master', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.master') || request()->routeIs('principal.institute.routine.master-print') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>School Routine</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.routine.master-all', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.routine.master-all') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Full Master Routine</p></a></li>
                                    @endif
                                    <li class="nav-item"><a href="{{ route('principal.institute.groups.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.groups.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Group</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.subjects.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.subjects.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Subject</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.academic-years.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.academic-years.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Academic Year</p></a></li>
                                    @if(auth()->user()->hasModule('extra_class'))
                                    <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Extra Class</p></a></li>
                                    @endif
                                    <li class="nav-item"><a href="{{ route('principal.institute.teams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teams.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Special Team/Group</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.result-settings.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.result-settings.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Result Settings</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.payments.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.payments.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Online Payments</p></a></li>
                                </ul>
                            </li>

                                                            {{-- Manage HR (Teachers + Staff) --}}
                            <li class="nav-item has-treeview {{ (request()->routeIs('principal.institute.teachers.*') || request()->routeIs('principal.institute.staff.*')) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ (request()->routeIs('principal.institute.teachers.*') || request()->routeIs('principal.institute.staff.*')) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users-cog"></i>
                                    <p>Manage HR <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.teachers.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teachers.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                            <p>Teachers</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.staff.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.staff.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-id-badge"></i>
                                            <p>Staff</p>
                                        </a>
                                    </li>
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
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.create', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.create') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Add Student</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.blank-form', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.blank-form') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Blank Form</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.print-controls', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.print-controls') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Student Info Print</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.public-exam-info', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.public-exam-info') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Add Public Exam Info</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.bulk', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.bulk') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Bulk student add</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.report-cards.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.report-cards.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Report Card</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.students.id-cards.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.students.id-cards.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>ID Card</p></a></li>
                                </ul>
                            </li>

                                 {{-- Teacher Attendance --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.teacher-attendance.*') || request()->routeIs('teacher.attendance.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.*') || request()->routeIs('teacher.attendance.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-clock"></i>
                                    <p>Teacher Attendance <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('teacher.attendance.index') }}" class="nav-link {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>My Attendance</p></a></li>
                                    <li class="nav-item"><a href="{{ route('teacher.attendance.my-attendance') }}" class="nav-link {{ request()->routeIs('teacher.attendance.my-attendance') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Attendance Records</p></a></li>
                                    <li class="nav-item"><a href="{{ route('teacher.leave.index') }}" class="nav-link {{ request()->routeIs('teacher.leave.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>My Leaves</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-attendance.reports.daily', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.reports.daily') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Daily Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-attendance.reports.monthly', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-attendance.reports.monthly') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Monthly Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.teacher-leaves.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.teacher-leaves.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Teacher Leaves</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.student-leaves.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.student-leaves.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Student Leaves</p></a></li>
                                </ul>
                            </li>

                            @if(method_exists($u,'primarySchool') && $u->primarySchool())
                                @if(auth()->user()->hasModule('attendance'))
                                {{-- Attendance (top-level) --}}
                                <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance*') || request()->routeIs('principal.institute.holidays*') || request()->routeIs('principal.institute.biometric*') ? 'menu-open' : '' }}">
                                    <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance*') || request()->routeIs('principal.institute.holidays*') || request()->routeIs('principal.institute.biometric*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-calendar-check"></i>
                                        <p>Student Attendance <i class="right fas fa-angle-left"></i></p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        {{-- Class Attendance --}}
                                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.attendance.class*') || request()->routeIs('principal.institute.attendance.dashboard') ? 'menu-open' : '' }}">
                                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.attendance.class*') || request()->routeIs('principal.institute.attendance.dashboard') ? 'active' : '' }}">
                                                <i class="far fa-dot-circle nav-icon"></i>
                                                <p>Class Attendance <i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.dashboard', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.dashboard') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Attendance Dashboard</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.class.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.class.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Attendance</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.daily_report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.daily_report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Daily Report</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.monthly_report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.monthly_report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Monthly Report</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.attendance.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.settings') ? 'active' : '' }}"><i class="fas fa-cog nav-icon"></i><p>Settings</p></a></li>
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
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.dashboard', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.dashboard') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Attendance Dashboard</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.take') || request()->routeIs('principal.institute.extra-classes.attendance.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Attendance</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.daily-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Daily Report</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.extra-classes.attendance.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.extra-classes.attendance.monthly-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Monthly Report</p></a></li>
                                            </ul>
                                        </li>

                                        {{-- Biometric Attendance --}}
                                        <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.biometric*') ? 'menu-open' : '' }}">
                                            <a href="#" class="nav-link {{ request()->routeIs('principal.institute.biometric*') ? 'active' : '' }}">
                                                <i class="far fa-dot-circle nav-icon"></i>
                                                <p>Biometric System <i class="right fas fa-angle-left"></i></p>
                                            </a>
                                            <ul class="nav nav-treeview">
                                                <li class="nav-item"><a href="{{ route('principal.institute.biometric.dashboard', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.biometric.dashboard') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Dashboard</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.biometric.reports.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.biometric.reports.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Reports</p></a></li>
                                                <li class="nav-item"><a href="{{ route('principal.institute.biometric.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.biometric.settings') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                            </ul>
                                        </li>

                                        {{-- Common Items --}}
                                        <li class="nav-item"><a href="{{ route('principal.institute.holidays.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.holidays.*') || request()->routeIs('principal.institute.weekly-holidays.*') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Holiday Management</p></a></li>
                                    </ul>
                                </li>
                                @endif

                            {{-- Lesson Evaluation (Principal Reports) --}}
                            @if(auth()->user()->hasModule('lesson_evaluation'))
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.lesson-evaluations.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.lesson-evaluations.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clipboard-check"></i>
                                    <p>Lesson Evaluation <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.lesson-evaluations.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.lesson-evaluations.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Reports</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.lesson-evaluations.entry-report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.lesson-evaluations.entry-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Entry Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.lesson-evaluations.teacher-report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.lesson-evaluations.teacher-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Teacher-wise Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.lesson-evaluations.routine-wise-report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.lesson-evaluations.routine-wise-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Routine-wise Report</p></a></li>
                                </ul>
                            </li>
                            @endif


                            @if(auth()->user()->hasModule('exams'))
                            {{-- Manage Exam --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.exams.*') || request()->routeIs('principal.institute.seat-plans.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.exams.*') || request()->routeIs('principal.institute.seat-plans.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>Manage Exams <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.index') || request()->routeIs('principal.institute.exams.create') || request()->routeIs('principal.institute.exams.edit') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Exam List</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.invigilations.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.invigilations.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Room Invigilator</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.room-attendance', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.room-attendance') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Room Attendance</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.attendance-report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.attendance-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Attendance Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.attendance-report.absentee', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.attendance-report.absentee') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Absentee Report</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.exams.find-seat', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.exams.find-seat') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Find Students Seat</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.seat-plans.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.seat-plans.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Seat Plans</p></a></li>
                                </ul>
                            </li>
                            @endif

                            @if(auth()->user()->hasModule('results'))
                            {{-- Results --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.results.*') || request()->routeIs('principal.institute.marks.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.results.*') || request()->routeIs('principal.institute.marks.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-poll"></i>
                                    <p>Results <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.marks.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.marks.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Entry</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.results.exams', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.results.exams') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Result List</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.results.marksheet', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.results.marksheet') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Marksheet</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.results.tabulation', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.results.tabulation') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Tabulation Sheet</p></a></li>
                                </ul>
                            </li>
                            @endif

                            {{-- Documents --}}
                            @if(auth()->user()->hasModule('documents'))
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.documents.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.documents.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-signature"></i>
                                    <p>Documents <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.prottayon.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.prottayon.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Prottayon</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.certificate.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.certificate.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Certificate</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.testimonial.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.testimonial.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Testimonial</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.guardian_pledge.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.guardian_pledge.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Guardian Pledge</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.interview_token.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.interview_token.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Interview Token</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.documents.settings.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.documents.settings.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                </ul>
                            </li>

                            {{-- Game and Sports --}}
                            <li class="nav-item has-treeview {{ request()->is('principal/institute/*/game-and-sports*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->is('principal/institute/*/game-and-sports*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-running"></i>
                                    <p>Games & Sports <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.game-and-sports.consent.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.game-and-sports.consent.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Permission Letter</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.game-and-sports.interschool.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.game-and-sports.interschool.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Inter-school Competition</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            @endif

                            @if($u->primarySchool() && auth()->user()->hasModule('notices'))
                            <li class="nav-item">
                                <a href="{{ route('principal.institute.notices', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.notices') || request()->is('principal/institute/*/notices*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-bullhorn"></i>
                                    <p>Notice Board</p>
                                </a>
                            </li>
                            @endif

                            @if(auth()->user()->hasModule('accounts'))
                            <li class="nav-item has-treeview {{ request()->is('billing*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->is('billing*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>Billing <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('billing.collect') }}" class="nav-link {{ request()->routeIs('billing.collect') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Collect</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.config') }}" class="nav-link {{ request()->routeIs('billing.config') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Fees</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.waivers') }}" class="nav-link {{ request()->routeIs('billing.waivers') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Waivers</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.reports') }}" class="nav-link {{ request()->routeIs('billing.reports') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Reports</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.collection_reports') }}" class="nav-link {{ request()->routeIs('billing.collection_reports') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Collection Reports</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.due') }}" class="nav-link {{ request()->routeIs('billing.due') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Due Preview</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.detailed_due_report') }}" class="nav-link {{ request()->routeIs('billing.detailed_due_report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Due Collection List</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.statement') }}" class="nav-link {{ request()->routeIs('billing.statement') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Statement</p></a></li>
                                    <li class="nav-item"><a href="{{ route('billing.cashier_setup') }}" class="nav-link {{ request()->routeIs('billing.cashier_setup') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Cashier Settings</p></a></li>
                                </ul>
                            </li>
                            @endif


                            {{-- SMS (top-level) --}}
                            @if(auth()->user()->hasModule('sms'))
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.sms.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.sms.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-sms"></i>
                                    <p>SMS <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.panel', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.panel') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Panel</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.logs', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.logs*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Logs</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.sms.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.sms.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                </ul>
                            </li>
                            @endif

                            {{-- Admissions --}}
                            @if(auth()->user()->hasModule('admission'))
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.admissions.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.admissions.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>Admission <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.applications', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.applications*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Applications</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.enrollment.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.enrollment.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Confirm Enrollment</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.payments', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.payments*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Payments</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.settings*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.exams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.exams.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Exams</p></a></li>
                                    <li class="nav-item"><a href="{{ route('principal.institute.admissions.seat-plans.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.admissions.seat-plans.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Seat Plans</p></a></li>
                                </ul>
                            </li>
                            @endif

                            {{-- Settings Group --}}
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.background_settings.*') || request()->routeIs('principal.institute.public_exams.*') || request()->routeIs('principal.institute.attendance.settings*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.background_settings.*') || request()->routeIs('principal.institute.public_exams.*') || request()->routeIs('principal.institute.attendance.settings*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cog"></i>
                                    <p>Settings <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.background_settings.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.background_settings.index') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Documents Background</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.public_exams.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.public_exams.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Public Exams</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.attendance.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.attendance.settings*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Attendance Settings</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            @if(auth()->user()->hasModule('frontend_website'))
                            <li class="nav-item has-treeview {{ request()->routeIs('principal.institute.frontend.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('principal.institute.frontend.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-globe"></i>
                                    <p>Website <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.website-template', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.website-template*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Website Templates</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.settings') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Website Settings</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.front-page-elements', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.front-page-elements*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>FrontPage Element</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.contact-settings', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.contact-settings*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Contact Settings</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.gallery', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.gallery*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Gallery</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.menus', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.menus*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Menus</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.pages.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.pages.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Pages</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('principal.institute.frontend.posts.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.frontend.posts.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Blog Posts</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            @endif
                        @endif

                    @elseif($u && ($u->isTeacher() || $u->isExamController($u->primarySchool()?->id)))
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
                        @if(auth()->user()->hasModule('attendance'))
                        {{-- Student Attendance (Class, Extra Class, Team) --}}
                        <li class="nav-item has-treeview {{ request()->routeIs('teacher.institute.attendance.class.*') || request()->routeIs('teacher.institute.attendance.extra-classes.*') || request()->routeIs('teacher.institute.attendance.team.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('teacher.institute.attendance.class.*') || request()->routeIs('teacher.institute.attendance.extra-classes.*') || request()->routeIs('teacher.institute.attendance.team.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-check"></i>
                                <p>Student Attendance <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.class.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.class.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Class Attendance</p></a></li>
                                @if(auth()->user()->hasModule('extra_class'))
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.extra-classes.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.extra-classes.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Extra Class Attendance</p></a></li>
                                @endif
                                <li class="nav-item"><a href="{{ route('teacher.institute.attendance.team.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.attendance.team.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Team Attendance</p></a></li>
                            </ul>
                        </li>
                        @endif

                        @if(auth()->user()->hasModule('homework'))
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.homework.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.homework.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book-reader"></i>
                                <p>Homework</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasModule('lesson_evaluation'))
                        {{-- Lesson Evaluation --}}
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.lesson-evaluation.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.lesson-evaluation.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-clipboard-check"></i>
                                <p>Lesson Evaluation</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasModule('routine'))
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.routine.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.routine.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>My Routine</p>
                            </a>
                        </li>
                        @endif

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

                        @if(auth()->user()->hasModule('notices'))
                        <li class="nav-item">
                            <a href="{{ route('teacher.institute.notices', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.notices') || request()->is('teacher/institute/*/notices*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Notice Board</p>
                            </a>
                        </li>
                        @endif
                        @endif

                        @if(auth()->user()->hasModule('exams'))
                        {{-- Manage Exams --}}
                        <li class="nav-item has-treeview {{ request()->routeIs('teacher.institute.exams.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('teacher.institute.exams.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Manage Exams <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.mark-entry', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.mark-entry') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Mark Entry</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.room-attendance', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.room-attendance') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Room Attendance</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.find-seat', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.find-seat') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Find Students Seat</p></a></li>
                                <li class="nav-item"><a href="{{ route('principal.institute.seat-plans.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('principal.institute.seat-plans.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Seat Plans</p></a></li>
                                @if($u->isPrincipal($u->primarySchool()?->id) || $u->isExamController($u->primarySchool()?->id))
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.invigilations.index', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.invigilations.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Invigilation Duty</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.attendance-report', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.attendance-report') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Attendance Report</p></a></li>
                                <li class="nav-item"><a href="{{ route('teacher.institute.exams.attendance-report.absentee', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.exams.attendance-report.absentee') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Absentee Report</p></a></li>
                                @endif
                            </ul>
                        </li>
                        @endif

                        @if(auth()->user()->hasModule('accounts'))
                        {{-- Billing (Teacher) --}}
                        <li class="nav-item has-treeview {{ request()->is('teacher/institute/*/billing*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('teacher/institute/*/billing*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Billing <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('teacher.institute.billing.collect', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.billing.collect') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Fees Collect</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('teacher.institute.billing.my_collections', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.billing.my_collections') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>My Collections</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('teacher.institute.billing.cash_transfer', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.billing.cash_transfer') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Cash Transfer</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('teacher.institute.billing.deposit_history', $u->primarySchool()) }}" class="nav-link {{ request()->routeIs('teacher.institute.billing.deposit_history') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Deposit History</p>
                                    </a>
                                </li>
                                @if(auth()->user()->isCashier($u->primarySchool()?->id))
                                <li class="nav-item">
                                    <a href="{{ route('billing.cashier_dashboard') }}" class="nav-link {{ request()->routeIs('billing.cashier_dashboard') ? 'active' : '' }}">
                                        <i class="fas fa-cash-register nav-icon text-emerald-400"></i>
                                        <p>Cashier Dashboard</p>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif

                    @elseif($u && $u->isParent())
                        {{-- Parent Menu --}}
                        <li class="nav-item">
                            <a href="{{ route('parent.dashboard') }}" class="nav-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('parent.profile') }}" class="nav-link {{ request()->routeIs('parent.profile') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-graduate"></i>
                                <p>Student Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('parent.subjects') }}" class="nav-link {{ request()->routeIs('parent.subjects') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Enrolled Subjects</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('parent.fees') }}" class="nav-link {{ request()->routeIs('parent.fees') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-wallet"></i>
                                <p>Fee Account</p>
                            </a>
                        </li>
                        @if(auth()->user()->hasModule('routine'))
                        <li class="nav-item">
                            <a href="{{ route('parent.routine') }}" class="nav-link {{ request()->routeIs('parent.routine') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Class Routine</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasModule('homework'))
                        <li class="nav-item">
                            <a href="{{ route('parent.homework') }}" class="nav-link {{ request()->routeIs('parent.homework') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-edit"></i>
                                <p>Homework</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasModule('attendance'))
                        <li class="nav-item {{ request()->routeIs('parent.attendance*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('parent.attendance*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-check"></i>
                                <p>
                                    হাজিরা রিপোর্ট
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('parent.attendance.class') }}" class="nav-link {{ request()->routeIs('parent.attendance.class') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Class Attendance</p>
                                    </a>
                                </li>
                                @if(auth()->user()->hasModule('extra_class'))
                                <li class="nav-item">
                                    <a href="{{ route('parent.attendance.extra') }}" class="nav-link {{ request()->routeIs('parent.attendance.extra') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Extra Class Attendance</p>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif
                        @if(auth()->user()->hasModule('lesson_evaluation'))
                        <li class="nav-item">
                            <a href="{{ route('parent.evaluations') }}" class="nav-link {{ request()->routeIs('parent.evaluations') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Lesson Evaluation</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('parent.leaves') }}" class="nav-link {{ request()->routeIs('parent.leaves') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-envelope-open-text"></i>
                                <p>Leave Application</p>
                            </a>
                        </li>
                        @if(auth()->user()->hasModule('notices'))
                        <li class="nav-item">
                            <a href="{{ route('parent.notices') }}" class="nav-link {{ request()->routeIs('parent.notices') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Notice Board</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('parent.teachers') }}" class="nav-link {{ request()->routeIs('parent.teachers') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Teacher List</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('parent.feedback') }}" class="nav-link {{ request()->routeIs('parent.feedback') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-comment-dots"></i>
                                <p>Feedback & Complaints</p>
                            </a>
                        </li>
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
        <section class="content-header pb-2">
            <div class="container-fluid pt-2 px-md-4">
                @yield('content_header')
            </div>
        </section>

        <section class="content pt-1">
            <div class="container-fluid px-md-4">
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
