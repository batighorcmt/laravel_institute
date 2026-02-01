@extends('layouts.admin')

@section('title', 'পরীক্ষার বিস্তারিত')

@section('content')
@push('styles')
<style>
/* Limit Select2 dropdown height to ~5 items and enable scrolling */
.select2-container .select2-results__options { max-height: 180px; overflow-y: auto; }
.select2-container--bootstrap4 .select2-results__option { white-space: nowrap; }
</style>
@endpush
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $exam->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">বিস্তারিত</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        {{-- Warning/error shown as toast instead of inline alert --}}

        <!-- Exam Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.exams.index', $school) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> পরীক্ষা তালিকা
                    </a>
                    <a href="{{ route('principal.institute.exams.create', $school) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> নতুন পরীক্ষা
                    </a>
                    <button onclick="window.print()" type="button" class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="printLandscape()" type="button" class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-print"></i> Print (Landscape)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">পরীক্ষার নাম:</th>
                                <td>{{ $exam->name }}</td>
                            </tr>
                            <tr>
                                <th>পরীক্ষার নাম (বাংলা):</th>
                                <td>{{ $exam->name_bn ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>পরীক্ষার ধরন:</th>
                                <td>{{ $exam->exam_type ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শ্রেণি:</th>
                                <td>{{ $exam->class->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শিক্ষাবর্ষ:</th>
                                <td>{{ $exam->academicYear->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>মোট বিষয় (৪র্থ বাদে):</th>
                                <td>{{ $exam->total_subjects_without_fourth ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">শুরুর তারিখ:</th>
                                <td>{{ $exam->start_date ? $exam->start_date->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শেষের তারিখ:</th>
                                <td>{{ $exam->end_date ? $exam->end_date->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>অবস্থা:</th>
                                <td>
                                    @if($exam->status == 'active')
                                        <span class="badge badge-success">সক্রিয়</span>
                                    @elseif($exam->status == 'completed')
                                        <span class="badge badge-info">সম্পন্ন</span>
                                    @elseif($exam->status == 'cancelled')
                                        <span class="badge badge-danger">বাতিল</span>
                                    @else
                                        <span class="badge badge-secondary">খসড়া</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>বিবরণ:</th>
                                <td>{{ $exam->description ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Subjects Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার বিষয়সমূহ</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> নতুন বিষয় যুক্ত করুন
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($exam->examSubjects->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th rowspan="2">ক্রমিক</th>
                                    <th rowspan="2">বিষয়</th>
                                    <th rowspan="2">পরীক্ষার তারিখ</th>
                                    <th rowspan="2">সময়</th>
                                    <th rowspan="2">নম্বর Entry শেষ তারিখ</th>
                                    <th colspan="2">সৃজনশীল</th>
                                    <th colspan="2">MCQ</th>
                                    <th colspan="2">ব্যবহারিক</th>
                                    <th rowspan="2">মোট</th>
                                    <th rowspan="2">শিক্ষক</th>
                                    <th rowspan="2" class="d-print-none">কার্যক্রম</th>
                                    <th rowspan="2">স্বাক্ষর</th>
                                </tr>
                                <tr>
                                    <th>পূর্ণমান</th>
                                    <th>পাস</th>
                                    <th>পূর্ণমান</th>
                                    <th>পাস</th>
                                    <th>পূর্ণমান</th>
                                    <th>পাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $examSubject->subject->name ?? 'N/A' }}</strong></td>
                                        <td>
                                            @if($examSubject->exam_date)
                                                {{ $examSubject->exam_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($examSubject->exam_start_time)
                                                {{ \Carbon\Carbon::parse($examSubject->exam_start_time)->format('h:i A') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($examSubject->mark_entry_deadline)
                                                {{ $examSubject->mark_entry_deadline->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $examSubject->creative_full_mark }}</td>
                                        <td>{{ $examSubject->creative_pass_mark }}</td>
                                        <td>{{ $examSubject->mcq_full_mark }}</td>
                                        <td>{{ $examSubject->mcq_pass_mark }}</td>
                                        <td>{{ $examSubject->practical_full_mark }}</td>
                                        <td>{{ $examSubject->practical_pass_mark }}</td>
                                        <td><strong>{{ $examSubject->total_full_mark }}</strong></td>
                                        <td>{{ $examSubject->teacher->name ?? 'Not Assigned' }}</td>
                                        <td class="d-print-none">
                                            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editSubjectModal{{ $examSubject->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('principal.institute.exams.subjects.remove', [$school, $exam, $examSubject]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="signature-cell"></td>
                                    </tr>

                                    <!-- Edit Subject Modal -->
                                    <div class="modal fade" id="editSubjectModal{{ $examSubject->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="{{ route('principal.institute.exams.subjects.update', [$school, $exam, $examSubject]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">বিষয় সম্পাদনা করুন</h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>বিষয়</label>
                                                                    <input type="text" class="form-control" value="{{ $examSubject->subject->name }}" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>শিক্ষক</label>
                                                                    <select name="teacher_id" class="form-control">
                                                                        <option value="">-- নির্বাচন করুন --</option>
                                                                        @foreach($teachers as $teacher)
                                                                            <option value="{{ $teacher->id }}" {{ $examSubject->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                                                {{ $teacher->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>সৃজনশীল পূর্ণমান</label>
                                                                    <input type="number" name="creative_full_mark" class="form-control" value="{{ $examSubject->creative_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>MCQ পূর্ণমান</label>
                                                                    <input type="number" name="mcq_full_mark" class="form-control" value="{{ $examSubject->mcq_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ব্যবহারিক পূর্ণমান</label>
                                                                    <input type="number" name="practical_full_mark" class="form-control" value="{{ $examSubject->practical_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>সৃজনশীল পাস মার্ক</label>
                                                                    <input type="number" name="creative_pass_mark" class="form-control" value="{{ $examSubject->creative_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>MCQ পাস মার্ক</label>
                                                                    <input type="number" name="mcq_pass_mark" class="form-control" value="{{ $examSubject->mcq_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ব্যবহারিক পাস মার্ক</label>
                                                                    <input type="number" name="practical_pass_mark" class="form-control" value="{{ $examSubject->practical_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>পাস টাইপ</label>
                                                                    <select name="pass_type" class="form-control" required>
                                                                        <option value="combined" {{ $examSubject->pass_type == 'combined' ? 'selected' : '' }}>সম্মিলিত</option>
                                                                        <option value="each" {{ $examSubject->pass_type == 'each' ? 'selected' : '' }}>প্রতিটিতে আলাদা</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>পরীক্ষার তারিখ</label>
                                                                    <input type="date" name="exam_date" class="form-control" value="{{ $examSubject->exam_date ? $examSubject->exam_date->format('Y-m-d') : '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ক্রম নম্বর</label>
                                                                    <input type="number" name="display_order" class="form-control" value="{{ $examSubject->display_order }}" min="0">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>শুরুর সময়</label>
                                                                    <input type="time" name="exam_start_time" class="form-control" value="{{ $examSubject->exam_start_time }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>শেষের সময়</label>
                                                                    <input type="time" name="exam_end_time" class="form-control" value="{{ $examSubject->exam_end_time }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>নম্বর Entry শেষ তারিখ</label>
                                                                    <input type="date" name="mark_entry_deadline" class="form-control" value="{{ $examSubject->mark_entry_deadline ? $examSubject->mark_entry_deadline->format('Y-m-d') : '' }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                                                        <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এই পরীক্ষায় এখনো কোনো বিষয় যুক্ত করা হয়নি।
                    </div>
                @endif
            </div>
        </div>

        <!-- Add Subject Modal -->
        <div class="modal fade" id="addSubjectModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('principal.institute.exams.subjects.add', [$school, $exam]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">নতুন বিষয় যুক্ত করুন</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>বিষয় <span class="text-danger">*</span></label>
                                        <select name="subject_id" class="form-control" required>
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শিক্ষক</label>
                                        <select name="teacher_id" class="form-control">
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>সৃজনশীল পূর্ণমান</label>
                                        <input type="number" name="creative_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>MCQ পূর্ণমান</label>
                                        <input type="number" name="mcq_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ব্যবহারিক পূর্ণমান</label>
                                        <input type="number" name="practical_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>সৃজনশীল পাস মার্ক</label>
                                        <input type="number" name="creative_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>MCQ পাস মার্ক</label>
                                        <input type="number" name="mcq_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ব্যবহারিক পাস মার্ক</label>
                                        <input type="number" name="practical_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>পাস টাইপ</label>
                                        <select name="pass_type" class="form-control" required>
                                            <option value="combined">সম্মিলিত</option>
                                            <option value="each">প্রতিটিতে আলাদা</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>পরীক্ষার তারিখ</label>
                                        <input type="date" name="exam_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ক্রম নম্বর</label>
                                        <input type="number" name="display_order" class="form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শুরুর সময়</label>
                                        <input type="time" name="exam_start_time" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শেষের সময়</label>
                                        <input type="time" name="exam_end_time" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>নম্বর Entry শেষ তারিখ</label>
                                <input type="date" name="mark_entry_deadline" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                            <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    function _showToast(type, msg){
        if (!msg) return;
        if (window.showToast) { window.showToast(type === 'error' ? 'danger' : type, msg); return; }

        var container = document.getElementById('app-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-toast-container';
            container.className = 'app-toast-container';
            document.body.appendChild(container);
            var style = document.createElement('style');
            style.innerHTML = ".app-toast-container{position:fixed;top:80px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:10px}.app-toast{min-width:260px;padding:12px 16px;border-radius:6px;color:#fff}.app-toast--success{background:#28a745}.app-toast--warning{background:#ffc107;color:#212529}.app-toast--danger{background:#dc3545}";
            document.head.appendChild(style);
        }

        var toast = document.createElement('div');
        toast.className = 'app-toast app-toast--' + (type === 'error' ? 'danger' : type);
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(function(){ toast.remove(); }, 3500);
    }

    @if(session('warning'))
        _showToast('warning', {!! json_encode(session('warning')) !!});
    @endif
    @if(session('error'))
        _showToast('error', {!! json_encode(session('error')) !!});
    @endif
});
</script>
@endpush

<style>
@media print {
    html, body { background:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .content-header, .main-footer, .d-print-none, .sidebar, .navbar { display:none !important; }
    .table th, .table td { font-size:12px; padding:8px 6px; line-height:1.2; }
    .table th { background:transparent !important; color:#000 !important; text-align:center; vertical-align:middle; }
    .table tr { background:transparent !important; }
    .table td, .table th { border:1px solid #444 !important; }
    .signature-cell { min-width: 95px; height: 28px; }
}
</style>

@push('scripts')
<script>
    function printLandscape() {
        var st = document.createElement('style');
        st.id = 'landscapeStyle';
        st.media = 'print';
        st.appendChild(document.createTextNode('@page { size: landscape; }'));
        document.head.appendChild(st);
        window.print();
        setTimeout(function(){ var s=document.getElementById('landscapeStyle'); if (s) s.remove(); }, 1000);
    }
</script>
@endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        function ensureSelect2AndInit(modal){
            if(!modal) return;
            var selects = modal.querySelectorAll('select[name="teacher_id"]');
            if(!selects || selects.length===0) return;

            function initAll(){
                selects.forEach(function(el){
                    try{
                        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                            if ($(el).data('select2')) { try { $(el).select2('destroy'); } catch(e){} }
                            $(el).select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $(modal), placeholder: '-- নির্বাচন করুন --', allowClear: true });
                        }
                    } catch(err){ console.error('Select2 init error', err); }
                });
            }

            if (window.jQuery && jQuery.fn && jQuery.fn.select2) { initAll(); return; }

            if (!window.__select2_fallback_loading) {
                window.__select2_fallback_loading = true;
                var css = document.createElement('link'); css.rel='stylesheet'; css.href='https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css'; document.head.appendChild(css);
                var themeCss = document.createElement('link'); themeCss.rel='stylesheet'; themeCss.href='https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.3.1/dist/select2-bootstrap4.min.css'; document.head.appendChild(themeCss);
                var s = document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js'; s.async = true;
                s.onload = function(){ initAll(); };
                s.onerror = function(){ console.error('Select2 CDN failed to load'); };
                document.head.appendChild(s);
            } else {
                var tries = 40;
                (function wait(){ if (window.jQuery && jQuery.fn && jQuery.fn.select2) { initAll(); } else if (tries-->0) { setTimeout(wait,100); } })();
            }
        }

        if (window.jQuery) {
            $(document).on('shown.bs.modal', '.modal', function(){ ensureSelect2AndInit(this); });
        } else {
            document.querySelectorAll('.modal').forEach(function(modal){ modal.addEventListener('shown.bs.modal', function(){ ensureSelect2AndInit(modal); }); });
        }
    });
    </script>
    @endpush
@endsection
