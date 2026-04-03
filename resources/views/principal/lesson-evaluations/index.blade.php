@extends('layouts.admin')

@section('title', 'লেসন ইভালুয়েশন রিপোর্ট')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 31px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 31px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 31px !important; }
    .badge-c { background-color: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    .badge-p { background-color: #ffc107; color: black; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    .badge-n { background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    .badge-a { background-color: #6c757d; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    .badge-t { background-color: #343a40; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
</style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">লেসন ইভালুয়েশন রিপোর্ট</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-2 form-group">
                        <label class="small">তারিখ হতে</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small">তারিখ পর্যন্ত</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small">শ্রেণি</label>
                        <select name="class_id" id="class_select" class="form-control form-control-sm">
                            <option value="">সকল শ্রেণি</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small">শাখা</label>
                        <select name="section_id" id="section_select" class="form-control form-control-sm">
                            <option value="">সকল শাখা</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small">শিক্ষক</label>
                        <select name="teacher_id" class="form-control form-control-sm select2">
                            <option value="">সকল শিক্ষক</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small">বিষয়</label>
                        <select name="subject_id" class="form-control form-control-sm select2">
                            <option value="">সকল বিষয়</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 form-group">
                        <label class="small">পেজ</label>
                        <select name="per_page" class="form-control form-control-sm">
                            @foreach([10,25,50,100,200] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <button type="submit" class="btn btn-sm btn-primary px-3">ফিল্টার</button>
                        <a href="{{ route('principal.institute.lesson-evaluations.index', [$school]) }}" class="btn btn-sm btn-outline-secondary">রিসেট</a>
                        <a href="{{ route('principal.institute.lesson-evaluations.print', [$school] + request()->query() + ['lang' => 'bn']) }}" target="_blank" class="btn btn-sm btn-info ml-2">
                            <i class="fa fa-print"></i> প্রিন্ট
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th>তারিখ</th>
                            <th>শিক্ষক</th>
                            <th>শ্রেণি</th>
                            <th>শাখা</th>
                            <th>বিষয়</th>
                            <th title="Total">মোট</th>
                            <th title="Completed">পড়া হয়েছে</th>
                            <th title="Partial">আংশিক</th>
                            <th title="Not Done">পড়া হয়নি</th>
                            <th title="Absent">অনুপস্থিত</th>
                            <th class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $ev)
                            @php($s = $ev->getCompletionStats())
                            <tr>
                                <td>
                                    {{ optional($ev->evaluation_date)->format('d-m-Y') }}<br>
                                    <small>{{ $ev->evaluation_time ? $ev->evaluation_time->format('h:i A') : '' }}</small>
                                </td>
                                <td class="text-left">{{ $ev->teacher->full_name }}</td>
                                <td>{{ optional($ev->class)->name ?? '-' }}</td>
                                <td>{{ optional($ev->section)->name ?? '-' }}</td>
                                <td>
                                    {{ optional($ev->subject)->name ?? '-' }}
                                    @if($ev->notes)
                                        <div class="small text-muted" style="line-height:1.2;">{{ $ev->notes }}</div>
                                    @endif
                                </td>
                                <td><span class="badge-t">{{ $s['total'] }}</span></td>
                                <td><span class="badge-c">{{ $s['completed'] }}</span></td>
                                <td><span class="badge-p">{{ $s['partial'] }}</span></td>
                                <td><span class="badge-n">{{ $s['not_done'] }}</span></td>
                                <td><span class="badge-a">{{ $s['absent'] }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('principal.institute.lesson-evaluations.show', [$school, $ev]) }}" class="btn btn-xs btn-primary">
                                        দেখুন
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center">কোন তথ্য পাওয়া যায়নি</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    মোট তথ্য: {{ $evaluations->total() }}
                </div>
                <div>
                    <div class="d-none d-sm-block">
                        {{ $evaluations->onEachSide(1)->links() }}
                    </div>

                    <div class="d-block d-sm-none">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0 justify-content-center">
                                @if($evaluations->onFirstPage())
                                    <li class="page-item disabled"><span class="page-link">&laquo; পূর্ববর্তী</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $evaluations->previousPageUrl() }}" rel="prev">&laquo; পূর্ব</a></li>
                                @endif

                                <li class="page-item disabled"><span class="page-link">পৃষ্ঠা {{ $evaluations->currentPage() }} / {{ $evaluations->lastPage() }}</span></li>

                                @if($evaluations->hasMorePages())
                                    <li class="page-item"><a class="page-link" href="{{ $evaluations->nextPageUrl() }}" rel="next">পরবর্তী &raquo;</a></li>
                                @else
                                    <li class="page-item disabled"><span class="page-link">পরবর্তী &raquo;</span></li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_select');
    const sectionSelect = document.getElementById('section_select');
    const sectionsUrl = '{{ route("principal.institute.meta.sections", ["school" => $school->id]) }}';

    function resetSections(label) {
        sectionSelect.innerHTML = '<option value="">' + (label || 'সকল শাখা') + '</option>';
    }

    async function loadSections(classId) {
        resetSections('লোড হচ্ছে...');
        sectionSelect.disabled = true;
        try {
            const resp = await fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId));
            if (!resp.ok) throw new Error('Network error');
            const data = await resp.json();

            resetSections();
            if (Array.isArray(data) && data.length) {
                data.forEach(sec => {
                    const opt = document.createElement('option');
                    opt.value = sec.id;
                    opt.textContent = sec.name;
                    sectionSelect.appendChild(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'কোন শাখা নেই';
                sectionSelect.appendChild(opt);
            }
        } catch (e) {
            resetSections('লোড হতে ব্যর্থ');
            console.error('Section load failed:', e);
        } finally {
            sectionSelect.disabled = false;
        }
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const val = this.value;
            if (val) {
                loadSections(val);
            } else {
                resetSections();
            }
        });

        if (classSelect.value && sectionSelect.children.length <= 1) {
            loadSections(classSelect.value);
        }
    }

    // Handle Select2 initialization safely
    const initSelect2 = () => {
        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
            jQuery('.select2').select2({ width: '100%' });
        }
    };
    initSelect2();
    // Fallback for Vite deferred loading
    let checkJq = setInterval(() => {
        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
            initSelect2();
            clearInterval(checkJq);
        }
    }, 200);
    setTimeout(() => clearInterval(checkJq), 5000);
});
</script>
@endpush
