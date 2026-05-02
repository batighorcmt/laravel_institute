@extends('layouts.admin')

@section('title', 'লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট')

@push('styles')
<style>
    .badge-entered { background-color: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
    .badge-missing { background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
    .badge-total { background-color: #17a2b8; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
</style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">তারিখ হতে</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">তারিখ পর্যন্ত</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">শ্রেণি নির্বাচন</label>
                        <select name="class_id" id="class_select" class="form-control form-control-sm" required>
                            <option value="">নির্বাচন করুন</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small font-weight-bold">শাখা নির্বাচন</label>
                        <select name="section_id" id="section_select" class="form-control form-control-sm" required>
                            <option value="">নির্বাচন করুন</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}" {{ $sectionId == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <button type="submit" class="btn btn-sm btn-primary px-3">রিপোর্ট দেখুন</button>
                        @if($classId && $sectionId && $reportData->isNotEmpty())
                            <a href="{{ route('principal.institute.lesson-evaluations.entry-report-print', [$school] + request()->query() + ['lang' => 'bn']) }}" target="_blank" class="btn btn-sm btn-info ml-1">
                                <i class="fa fa-print"></i> প্রিন্ট করুন
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if($classId && $sectionId)
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th width="50" rowspan="2" class="align-middle">ক্র.নং</th>
                                <th class="text-left align-middle" rowspan="2">বিষয়ের নাম</th>
                                <th class="text-left align-middle" rowspan="2">শিক্ষকের নাম</th>
                                <th colspan="3">ক্লাস তথ্য</th>
                                <th colspan="4">শিক্ষার্থী পরিসংখ্যান (এন্ট্রিকৃত ক্লাস সমূহের মোট)</th>
                            </tr>
                            <tr class="text-center">
                                <th title="Total Scheduled Classes">মোট ক্লাস</th>
                                <th title="Entries Done">এন্ট্রি হয়েছে</th>
                                <th title="Missing Entries">এন্ট্রি হয়নি</th>
                                <th class="bg-success text-white">পড়া হয়েছে</th>
                                <th class="bg-warning">আংশিক</th>
                                <th class="bg-danger text-white">পড়া হয়নি</th>
                                <th class="bg-secondary text-white">অনুপস্থিত</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totals = ['routine'=>0, 'entered'=>0, 'missing'=>0, 'comp'=>0, 'part'=>0, 'not'=>0, 'abs'=>0])
                            
                            @forelse($reportData as $index => $row)
                                @php($totals['routine'] += $row['total_classes'])
                                @php($totals['entered'] += $row['entered'])
                                @php($totals['missing'] += $row['missing'])
                                @php($totals['comp'] += $row['completed_students'])
                                @php($totals['part'] += $row['partial_students'])
                                @php($totals['not'] += $row['not_done_students'])
                                @php($totals['abs'] += $row['absent_students'])
                                
                                <tr class="text-center">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-left font-weight-bold">{{ $row['subject'] }}</td>
                                    <td class="text-left">{{ $row['teacher'] }}</td>
                                    <td><span class="badge-total">{{ $row['total_classes'] }}</span></td>
                                    <td><span class="badge-entered">{{ $row['entered'] }}</span></td>
                                    <td><span class="badge-missing">{{ $row['missing'] }}</span></td>
                                    <td class="text-success font-weight-bold">{{ $row['completed_students'] }}</td>
                                    <td class="text-warning font-weight-bold">{{ $row['partial_students'] }}</td>
                                    <td class="text-danger font-weight-bold">{{ $row['not_done_students'] }}</td>
                                    <td class="text-secondary font-weight-bold">{{ $row['absent_students'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-3">এই শ্রেণি ও শাখার রুটিনে কোন ক্লাস খুঁজে পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($reportData->isNotEmpty())
                        <tfoot class="bg-dark text-white">
                            <tr class="text-center font-weight-bold">
                                <td colspan="3" class="text-right">সর্বমোট = </td>
                                <td>{{ $totals['routine'] }}</td>
                                <td>{{ $totals['entered'] }}</td>
                                <td>{{ $totals['missing'] }}</td>
                                <td>{{ $totals['comp'] }}</td>
                                <td>{{ $totals['part'] }}</td>
                                <td>{{ $totals['not'] }}</td>
                                <td>{{ $totals['abs'] }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    রিপোর্ট দেখতে অনুগ্রহ করে শ্রেণি ও শাখা নির্বাচন করুন।
                </div>
            @endif
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
        sectionSelect.innerHTML = '<option value="">' + (label || 'নির্বাচন করুন') + '</option>';
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
                    if (sec.id == '{{ $sectionId }}') opt.selected = true;
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
    }
});
</script>
@endpush
