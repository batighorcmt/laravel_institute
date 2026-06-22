@extends('layouts.admin')
@section('title', 'শিক্ষক ব্যবস্থাপনা')

@section('content')
@php
    $totalTeachers = $teachers->count();
    $activeTeachers = $teachers->where('status', 'active')->count();
    $websiteTeachers = $teachers->where('show_on_website', true)->count();
    $designations = $teachers->pluck('designation')->filter()->unique();
    $jobTypes = $teachers->pluck('job_type')->filter()->unique();
@endphp

<div class="teachers-page">
    <div class="teachers-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="teachers-hero-title mb-1">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>শিক্ষক ব্যবস্থাপনা
                </h1>
                <p class="teachers-hero-sub mb-0">{{ $school->name_bn ?? $school->name }} — সকল শিক্ষক এক জায়গায় পরিচালনা করুন</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-teachers-print" data-toggle="modal" data-target="#printSettingsModal">
                    <i class="fas fa-print mr-1"></i> তালিকা প্রিন্ট
                </button>
                <a href="{{ route('principal.institute.teachers.create', $school) }}" class="btn btn-teachers-add">
                    <i class="fas fa-plus mr-1"></i> নতুন শিক্ষক
                </a>
            </div>
        </div>

        <div class="row mt-4 g-3">
            <div class="col-sm-4">
                <div class="teachers-stat-card">
                    <span class="teachers-stat-label">মোট শিক্ষক</span>
                    <span class="teachers-stat-value">{{ $totalTeachers }}</span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="teachers-stat-card teachers-stat-card--green">
                    <span class="teachers-stat-label">সক্রিয়</span>
                    <span class="teachers-stat-value">{{ $activeTeachers }}</span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="teachers-stat-card teachers-stat-card--purple">
                    <span class="teachers-stat-label">ওয়েবসাইটে</span>
                    <span class="teachers-stat-value">{{ $websiteTeachers }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card teachers-card border-0 shadow-sm">
        <div class="card-header teachers-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="teachers-search-wrap">
                <i class="fas fa-search teachers-search-icon"></i>
                <input type="text" id="teacherSearchInput" class="form-control teachers-search-input" placeholder="নাম, পদবী, মোবাইল বা ইউজারনেম দিয়ে খুঁজুন...">
            </div>
            <button type="button" id="resetTeacherSearch" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-redo-alt mr-1"></i> রিসেট
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table teachers-table mb-0" id="teachersTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:56px">#</th>
                            <th style="width:72px">ছবি</th>
                            <th>শিক্ষক</th>
                            <th>পদবী</th>
                            <th>যোগাযোগ</th>
                            <th>অ্যাকাউন্ট</th>
                            <th class="text-center" style="width:200px">কার্যক্রম</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $t)
                            @php
                                $isPrincipalUser = isset($principalUserIds) && in_array($t->user_id, $principalUserIds);
                                $displayNameBn = trim($t->full_name_bn) ?: trim($t->first_name_bn.' '.$t->last_name_bn);
                                $displayNameEn = $t->full_name;
                            @endphp
                            <tr class="teacher-row" data-search="{{ strtolower($displayNameBn.' '.$displayNameEn.' '.($t->designation ?? '').' '.($t->phone ?? '').' '.($t->user?->username ?? '').' '.($t->initials ?? '')) }}">
                                <td class="text-center align-middle">
                                    <span class="teacher-serial">{{ $t->serial_number ?? $loop->iteration }}</span>
                                </td>
                                <td class="align-middle">
                                    @if($t->photo_url)
                                        <img src="{{ $t->photo_url }}" alt="" class="teacher-avatar">
                                    @else
                                        <div class="teacher-avatar teacher-avatar--placeholder">
                                            {{ mb_substr($displayNameBn ?: $t->first_name, 0, 1) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="teacher-name-block">
                                        <a href="{{ route('principal.institute.teachers.show', [$school, $t->id]) }}" class="text-decoration-none">
                                            <strong class="teacher-name-bn text-primary hover-underline">{{ $displayNameBn ?: $displayNameEn }}</strong>
                                        </a>
                                        @if($displayNameBn && $displayNameEn)
                                            <span class="teacher-name-en d-block">{{ $displayNameEn }}</span>
                                        @endif
                                        @if($t->initials)
                                            <span class="badge badge-light border mt-1">{{ $t->initials }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="teacher-designation">{{ $t->designation ?? '—' }}</span>
                                    @if($t->status !== 'active')
                                        <span class="badge badge-secondary mt-1">নিষ্ক্রিয়</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($t->phone)
                                        <a href="tel:{{ $t->phone }}" class="teacher-contact-link d-block"><i class="fas fa-phone-alt mr-1 text-muted"></i>{{ $t->phone }}</a>
                                    @endif
                                    @if($t->user?->email)
                                        <a href="mailto:{{ $t->user->email }}" class="teacher-contact-link d-block text-truncate" style="max-width:180px"><i class="fas fa-envelope mr-1 text-muted"></i>{{ $t->user->email }}</a>
                                    @endif
                                    @if(!$t->phone && !$t->user?->email)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($t->user?->username)
                                        <code class="teacher-username">{{ $t->user->username }}</code>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                    @if($t->plain_password)
                                        <span class="d-block small text-muted mt-1" title="সাময়িক পাসওয়ার্ড"><i class="fas fa-key mr-1"></i>{{ $t->plain_password }}</span>
                                    @endif
                                </td>
                                <td class="align-middle text-right">
                                    <div class="teacher-actions">
                                        @if($isPrincipalUser)
                                            @if(Auth::user() && Auth::user()->id === $t->user_id)
                                                <a class="btn btn-sm btn-teachers-edit" href="{{ route('principal.institute.teachers.edit', [$school, $t->id]) }}" title="সম্পাদনা">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            <span class="badge badge-info">Principal</span>
                                        @else
                                            <a class="btn btn-sm btn-teachers-edit" href="{{ route('principal.institute.teachers.edit', [$school, $t->id]) }}" title="সম্পাদনা">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('principal.institute.teachers.reset-password', [$school, $t->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('পাসওয়ার্ড রিসেট করতে নিশ্চিত?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-teachers-reset" title="পাসওয়ার্ড রিসেট"><i class="fas fa-key"></i></button>
                                            </form>
                                            <form action="{{ route('principal.institute.teachers.destroy', [$school, $t->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('মুছতে নিশ্চিত?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-teachers-delete" title="মুছুন"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="teachersEmptyRow">
                                <td colspan="7" class="text-center py-5">
                                    <div class="teachers-empty">
                                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                        <p class="mb-2 font-weight-bold">কোনো শিক্ষক পাওয়া যায়নি</p>
                                        <a href="{{ route('principal.institute.teachers.create', $school) }}" class="btn btn-teachers-add btn-sm">প্রথম শিক্ষক যোগ করুন</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="teachersNoMatchRow" class="d-none">
                            <td colspan="7" class="text-center py-4 text-muted">খোঁজার সাথে মিলে এমন কোনো শিক্ষক নেই</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Settings Modal -->
<div class="modal fade" id="printSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-print mr-2 text-primary"></i>প্রিন্ট সেটিংস</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="GET" action="{{ route('principal.institute.teachers.print', $school) }}" target="_blank">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">পদবী অনুযায়ী ফিল্টার</label>
                        <select name="designation" class="form-control">
                            <option value="">সকল পদবী</option>
                            @foreach($designations as $desig)
                                <option value="{{ $desig }}">{{ $desig }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">চাকুরী টাইপ</label>
                        <select name="job_type" class="form-control">
                            <option value="">সকল টাইপ</option>
                            @foreach($jobTypes as $jt)
                                <option value="{{ $jt }}">{{ $jt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">স্ট্যাটাস</label>
                        <select name="status" class="form-control">
                            <option value="">সকল স্ট্যাটাস</option>
                            <option value="active">সক্রিয়</option>
                            <option value="inactive">নিষ্ক্রিয়</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">প্রিন্ট ভাষা (সংখ্যা ও অন্যান্য)</label>
                        <select name="lang" class="form-control">
                            <option value="bn">বাংলা</option>
                            <option value="en">English</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">প্রিন্ট কলামসমূহ</label>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="col_photo" name="columns[]" value="col-photo" checked>
                                    <label class="custom-control-label" for="col_photo">ছবি</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_name_bn" name="columns[]" value="col-name-bn" checked>
                                    <label class="custom-control-label" for="col_name_bn">নাম (বাংলা)</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_name_en" name="columns[]" value="col-name-en">
                                    <label class="custom-control-label" for="col_name_en">নাম (ইংরেজি)</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_father_bn" name="columns[]" value="col-father-bn">
                                    <label class="custom-control-label" for="col_father_bn">পিতার নাম (বাংলা)</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_father_en" name="columns[]" value="col-father-en">
                                    <label class="custom-control-label" for="col_father_en">পিতার নাম (English)</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_mother_bn" name="columns[]" value="col-mother-bn">
                                    <label class="custom-control-label" for="col_mother_bn">মাতার নাম (বাংলা)</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_mother_en" name="columns[]" value="col-mother-en">
                                    <label class="custom-control-label" for="col_mother_en">মাতার নাম (English)</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="col_designation" name="columns[]" value="col-designation" checked>
                                    <label class="custom-control-label" for="col_designation">পদবী</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_mobile" name="columns[]" value="col-mobile" checked>
                                    <label class="custom-control-label" for="col_mobile">মোবাইল</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_dob" name="columns[]" value="col-dob">
                                    <label class="custom-control-label" for="col_dob">জন্ম তারিখ</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_join_date" name="columns[]" value="col-join-date">
                                    <label class="custom-control-label" for="col_join_date">যোগদান তারিখ</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_present_addr" name="columns[]" value="col-present-addr">
                                    <label class="custom-control-label" for="col_present_addr">বর্তমান ঠিকানা</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_permanent_addr" name="columns[]" value="col-permanent-addr">
                                    <label class="custom-control-label" for="col_permanent_addr">স্থায়ী ঠিকানা</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_username" name="columns[]" value="col-username">
                                    <label class="custom-control-label" for="col_username">ইউজারনেম</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="col_password" name="columns[]" value="col-password">
                                    <label class="custom-control-label" for="col_password">পাসওয়ার্ড (সাময়িক)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary" onclick="$('#printSettingsModal').modal('hide')"><i class="fas fa-print mr-1"></i> প্রিন্ট করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .teachers-page { --tp-indigo: #4f46e5; --tp-violet: #7c3aed; --tp-slate: #0f172a; }

    .teachers-hero {
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 50%, #faf5ff 100%);
        border: 1px solid #e0e7ff;
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
    }
    .teachers-hero-title {
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--tp-slate);
    }
    .teachers-hero-sub { color: #64748b; font-size: 0.95rem; }

    .teachers-stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .teachers-stat-card--green { border-color: #bbf7d0; background: linear-gradient(180deg, #fff, #f0fdf4); }
    .teachers-stat-card--purple { border-color: #ddd6fe; background: linear-gradient(180deg, #fff, #faf5ff); }
    .teachers-stat-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94a3b8;
    }
    .teachers-stat-value {
        display: block;
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--tp-slate);
        line-height: 1.2;
    }

    .btn-teachers-add {
        background: var(--tp-indigo);
        color: #fff;
        font-weight: 700;
        border: none;
        border-radius: 10px;
        padding: 0.5rem 1.1rem;
    }
    .btn-teachers-add:hover { background: #4338ca; color: #fff; }
    .btn-teachers-print {
        background: #fff;
        color: var(--tp-indigo);
        font-weight: 700;
        border: 2px solid var(--tp-indigo);
        border-radius: 10px;
        padding: 0.5rem 1.1rem;
    }
    .btn-teachers-print:hover { background: #eef2ff; color: #4338ca; }

    .teachers-card { border-radius: 16px; overflow: hidden; }
    .teachers-card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.25rem;
    }
    .teachers-search-wrap { position: relative; flex: 1; min-width: 220px; max-width: 420px; }
    .teachers-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        z-index: 2;
    }
    .teachers-search-input {
        padding-left: 2.5rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        height: 42px;
    }
    .teachers-search-input:focus {
        border-color: var(--tp-indigo);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .teachers-table thead th {
        background: #f8fafc;
        border-top: none;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 0.85rem 1rem;
        white-space: nowrap;
    }
    .teachers-table tbody td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        border-color: #f1f5f9;
    }
    .teacher-row:hover { background: #f8fafc; }

    .teacher-serial {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #eef2ff;
        color: var(--tp-indigo);
        font-weight: 800;
        font-size: 0.8rem;
    }
    .teacher-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }
    .teacher-avatar--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--tp-indigo), var(--tp-violet));
        color: #fff;
        font-weight: 800;
        font-size: 1.1rem;
    }
    .teacher-name-bn { color: var(--tp-slate); font-size: 0.95rem; }
    .teacher-name-en { color: #64748b; font-size: 0.8rem; }
    .teacher-designation { font-weight: 600; color: #475569; }
    .teacher-contact-link { font-size: 0.82rem; color: #334155; text-decoration: none; }
    .teacher-contact-link:hover { color: var(--tp-indigo); }
    .teacher-username {
        font-size: 0.78rem;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        color: #475569;
    }

    .teacher-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: flex-end;
    }
    .btn-teachers-edit {
        background: #eef2ff;
        color: var(--tp-indigo);
        border: none;
        border-radius: 8px;
    }
    .btn-teachers-edit:hover { background: var(--tp-indigo); color: #fff; }
    .btn-teachers-reset {
        background: #fffbeb;
        color: #d97706;
        border: none;
        border-radius: 8px;
    }
    .btn-teachers-reset:hover { background: #f59e0b; color: #fff; }
    .btn-teachers-delete {
        background: #fef2f2;
        color: #dc2626;
        border: none;
        border-radius: 8px;
    }
    .btn-teachers-delete:hover { background: #dc2626; color: #fff; }

    @media (max-width: 992px) {
        .teachers-search-wrap { max-width: 100%; }
        .teacher-actions { justify-content: flex-start; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('teacherSearchInput');
    const resetBtn = document.getElementById('resetTeacherSearch');
    const rows = document.querySelectorAll('.teacher-row');
    const noMatchRow = document.getElementById('teachersNoMatchRow');

    function filterTable() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const hay = row.getAttribute('data-search') || '';
            const show = !term || hay.includes(term);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noMatchRow) {
            noMatchRow.classList.toggle('d-none', visible > 0 || rows.length === 0);
        }
    }

    searchInput?.addEventListener('input', filterTable);
    resetBtn?.addEventListener('click', function () {
        if (searchInput) searchInput.value = '';
        filterTable();
    });
});
</script>
@endpush
