@extends('layouts.admin')

@section('title', 'রোল ও পজিশন আপডেট')

@php
    $decimal = \App\Models\Setting::getDecimalPosition($school->id);
@endphp

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">রোল নম্বর ও পজিশন আপডেট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.results.exams', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">রোল ও পজিশন আপডেট</li>
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
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    {{ $exam->name }} &mdash; {{ $class->name ?? 'N/A' }}
                    <small class="text-muted">({{ $exam->academicYear->name ?? 'N/A' }})</small>
                </h3>
                <div>
                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#smsModal">
                        <i class="fas fa-sms"></i> শিক্ষার্থীদের মেসেজ পাঠান
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" id="applyRollBtn">
                        <i class="fas fa-sort-numeric-down"></i> নির্বাচিতদের রোল/শাখা আপডেট করুন
                    </button>
                    <a href="{{ route('principal.institute.results.exams.result-sheet.print', [$school, $exam]) }}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-print"></i> প্রিন্ট ভিউ
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="alert alert-info m-3 py-2 mb-0">
                    <i class="fas fa-info-circle"></i>
                    &quot;নতুন পজিশন&quot; কলামের মান-ই হবে শিক্ষার্থীর নতুন শ্রেণি রোল নম্বর। প্রয়োজনে &quot;নতুন শাখা&quot; কলাম থেকে
                    শিক্ষার্থীর শাখাও পরিবর্তন করা যাবে (যেমন: রোল ৫০ থেকে ২ হলে গ শাখা থেকে ক শাখায় স্থানান্তর)।
                    প্রথম কলামের চেকবক্স দিয়ে নির্বাচন করুন কাদের রোল/শাখা পরিবর্তন হবে &mdash; শুধুমাত্র নির্বাচিতদেরই আপডেট হবে,
                    একজন একজন করে ধারাবাহিকভাবে আপডেট হবে এবং প্রতিটি সারিতে লোডিং/সফল/ব্যর্থ চিহ্ন দেখাবে।
                </div>
                <div id="rollProgressWrap" class="d-none mx-3 mt-2">
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar bg-warning" id="rollProgressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <small class="text-muted">সফল: <span id="rollSuccessCount">0</span> | ব্যর্থ: <span id="rollFailCount">0</span> | মোট: <span id="rollTotalCount">0</span></small>
                </div>
                <div class="table-responsive m-3">
                    <table class="table table-bordered table-sm table-striped mb-0" id="resultTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:3%;"><input type="checkbox" id="selectAllRoll" checked></th>
                                <th>ক্রমিক</th>
                                <th>আইডি</th>
                                <th>শিক্ষার্থীর নাম</th>
                                <th>বর্তমান শাখা</th>
                                <th class="bg-warning">নতুন শাখা</th>
                                <th>বর্তমান রোল</th>
                                <th>মোট নম্বর</th>
                                <th>জিপিএ</th>
                                <th>ফলাফল</th>
                                <th class="bg-warning">নতুন পজিশন (নতুন রোল)</th>
                                <th>রোল স্ট্যাটাস</th>
                                <th>মেসেজ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $result)
                                @php
                                    $enrollment = optional($result->student)->currentEnrollment;
                                    $studentName = $result->student->student_name_bn ?: $result->student->student_name_en;
                                @endphp
                                <tr
                                    data-student-id="{{ $result->student->id }}"
                                    data-name="{{ $studentName }}"
                                    data-roll="{{ optional($enrollment)->roll_no ?? 'N/A' }}"
                                    data-position="{{ $result->new_position }}"
                                    data-marks="{{ number_format($result->computed_total_marks, $decimal, '.', '') }}"
                                    data-status="{{ $result->computed_status }}"
                                    data-gpa="{{ number_format($result->computed_gpa, 2) }}">
                                    <td>
                                        @if($enrollment)
                                            <input type="checkbox" class="roll-select" value="{{ $enrollment->id }}" checked>
                                        @else
                                            <input type="checkbox" disabled title="সক্রিয় ভর্তি নেই">
                                        @endif
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $result->student->student_id }}</td>
                                    <td class="text-left">{{ $studentName }}</td>
                                    <td class="current-section-cell">{{ optional($enrollment)->section->name ?? 'N/A' }}</td>
                                    <td class="bg-light">
                                        @if($enrollment && $sections->isNotEmpty())
                                            <select class="form-control form-control-sm section-select">
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}" @selected($enrollment->section_id == $section->id)>{{ $section->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="current-roll-cell">{{ optional($enrollment)->roll_no ?? 'N/A' }}</td>
                                    <td>{{ number_format($result->computed_total_marks, $decimal, '.', '') }}</td>
                                    <td>{{ number_format($result->computed_gpa, 2) }}</td>
                                    <td>
                                        @if($result->computed_letter === 'F')
                                            <span class="text-danger font-weight-bold">অকৃতকার্য</span>
                                            @if($result->fail_count)
                                                <span class="text-danger">({{ $result->fail_count }})</span>
                                            @endif
                                        @else
                                            <span class="text-success font-weight-bold">উত্তীর্ণ</span>
                                        @endif
                                    </td>
                                    <td class="bg-light"><strong>{{ $result->new_position }}</strong></td>
                                    <td class="text-center" id="roll-status-{{ $enrollment->id ?? 'none-'.$result->student->id }}"></td>
                                    <td class="text-center" id="msg-status-{{ $result->student->id }}"></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center py-4">কোনো ফলাফল পাওয়া যায়নি</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                মোট শিক্ষার্থী: {{ $results->count() }} জন
            </div>
        </div>
    </div>
</section>

{{-- Message Modal --}}
<div class="modal fade" id="smsModal" tabindex="-1" role="dialog" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel"><i class="fas fa-sms"></i> ফলাফল মেসেজ পাঠান</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <strong>নির্বাচিত প্রাপক:</strong> <span id="smsRecipientCount">0</span> জন শিক্ষার্থী
                    <span class="ml-3"><i class="fas fa-info-circle"></i> টেবিলের চেকবক্স দিয়ে নির্বাচিত প্রতিটি শিক্ষার্থীর অভিভাবকের মোবাইলে একে একে পৃথক মেসেজ যাবে</span>
                </div>

                <div class="form-group">
                    <label><strong>মেসেজ টেম্পলেট</strong></label>
                    <textarea name="message_template" id="message_template" class="form-control" rows="8" required placeholder="আপনার বার্তা লিখুন...">{{ $school->name_bn ?? $school->name }}
পরীক্ষার ফলাফল
পরীক্ষা: {{ $exam->name }}
নাম: {student_name}
রোল: {roll_no}
প্রাপ্ত নম্বর: {total_marks}
ফলাফল: {result_status}
পজিশন: {position}
</textarea>
                    <div class="mt-2">
                        <small class="text-muted d-block mb-2"><strong>উপলব্ধ কি-ওয়ার্ড:</strong> (ক্লিক করুন টেম্পলেটে যোগ করতে)</small>
                        <div class="d-flex flex-wrap" style="gap:5px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{student_name}">শিক্ষার্থীর নাম</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{roll_no}">রোল নং</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{position}">পজিশন</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{total_marks}">প্রাপ্ত নম্বর</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{result_status}">পাস/ফেল স্ট্যাটাস</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{gpa}">জিপিএ</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{exam_name}">পরীক্ষার নাম</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{school_name}">স্কুলের নাম</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="sms_counter">0 অক্ষর • 0 SMS</small>
                    </div>
                </div>

                <div class="alert alert-warning py-2">
                    <i class="fas fa-exclamation-triangle"></i> <strong>সতর্কতা:</strong> মেসেজ পাঠানোর জন্য আপনার SMS ব্যালেন্স থেকে চার্জ কাটা হবে।
                </div>

                <div id="smsProgressWrap" class="d-none">
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar bg-success" id="smsProgressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <small class="text-muted">সফল: <span id="smsSuccessCount">0</span> | ব্যর্থ: <span id="smsFailCount">0</span> | মোট: <span id="smsTotalCount">0</span></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                <button type="button" class="btn btn-success" id="sendSmsBtn"><i class="fas fa-paper-plane"></i> মেসেজ পাঠান</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const applyRollSingleUrl = @json(route('principal.institute.results.exams.roll-position-update.apply-single', [$school, $exam]));

document.getElementById('selectAllRoll').addEventListener('change', function() {
    document.querySelectorAll('.roll-select').forEach(function(cb) {
        cb.checked = this.checked;
    }, this);
    updateSmsRecipientCount();
});

document.querySelectorAll('.roll-select').forEach(function(cb) {
    cb.addEventListener('change', updateSmsRecipientCount);
});

function updateSmsRecipientCount() {
    var count = document.querySelectorAll('.roll-select:checked').length;
    var el = document.getElementById('smsRecipientCount');
    if (el) el.textContent = count;
}

document.querySelectorAll('.keyword-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var keyword = this.getAttribute('data-keyword');
        var textarea = document.getElementById('message_template');
        var cursorPos = textarea.selectionStart;
        var textBefore = textarea.value.substring(0, cursorPos);
        var textAfter = textarea.value.substring(cursorPos);
        textarea.value = textBefore + keyword + textAfter;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = cursorPos + keyword.length;
        updateCounter();
    });
});

function detectUnicode(str) {
    for (var i = 0; i < str.length; i++) {
        if (str.charCodeAt(i) > 127) return true;
    }
    return false;
}

function computeParts(len, unicode) {
    if (len === 0) return {parts: 0, per: unicode ? 70 : 160};
    var single = unicode ? 70 : 160;
    var multi = unicode ? 67 : 153;
    if (len <= single) return {parts: 1, per: single};
    return {parts: Math.ceil(len / multi), per: multi};
}

function updateCounter() {
    var txt = document.getElementById('message_template').value || '';
    var uni = detectUnicode(txt);
    var calc = computeParts(txt.length, uni);
    document.getElementById('sms_counter').textContent = txt.length + ' অক্ষর • ' + calc.parts + ' SMS' + (calc.parts > 1 ? (' (প্রতি অংশ ' + calc.per + ' অক্ষর)') : '');
}

document.getElementById('message_template').addEventListener('input', updateCounter);
updateCounter();
updateSmsRecipientCount();

function fillTemplate(template, row) {
    return template
        .split('{student_name}').join(row.dataset.name || '')
        .split('{roll_no}').join(row.dataset.roll || '')
        .split('{position}').join(row.dataset.position || '')
        .split('{total_marks}').join(row.dataset.marks || '')
        .split('{result_status}').join(row.dataset.status || '')
        .split('{gpa}').join(row.dataset.gpa || '')
        .split('{exam_name}').join(@json($exam->name))
        .split('{school_name}').join(@json($school->name_bn ?? $school->name));
}

const sendSmsUrl = @json(route('principal.institute.results.exams.roll-position-update.send-sms-single', [$school, $exam]));

document.getElementById('applyRollBtn').addEventListener('click', async function() {
    const rows = Array.from(document.querySelectorAll('#resultTable tbody tr')).filter(function(tr) {
        const cb = tr.querySelector('.roll-select');
        return cb && cb.checked;
    });

    if (rows.length === 0) {
        alert('রোল/শাখা আপডেট করার জন্য কমপক্ষে একজন শিক্ষার্থী নির্বাচন করুন।');
        return;
    }

    if (!confirm('আপনি কি নিশ্চিত? নির্বাচিত ' + rows.length + ' জন শিক্ষার্থীর শ্রেণি রোল নম্বর (ও প্রযোজ্য ক্ষেত্রে শাখা) একজন একজন করে আপডেট হয়ে যাবে। এই কাজটি পূর্বাবস্থায় ফেরানো যাবে না।')) {
        return;
    }

    const btn = this;
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> আপডেট হচ্ছে...';

    const progressWrap = document.getElementById('rollProgressWrap');
    const progressBar = document.getElementById('rollProgressBar');
    const successCountEl = document.getElementById('rollSuccessCount');
    const failCountEl = document.getElementById('rollFailCount');
    document.getElementById('rollTotalCount').textContent = rows.length;
    progressWrap.classList.remove('d-none');

    let successCount = 0;
    let failCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const enrollmentId = row.querySelector('.roll-select').value;
        const statusEl = document.getElementById('roll-status-' + enrollmentId);
        if (statusEl) statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-warning" title="আপডেট হচ্ছে..."></span>';

        const select = row.querySelector('.section-select');
        const sectionId = select ? select.value : null;

        try {
            const res = await fetch(applyRollSingleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    enrollment_id: enrollmentId,
                    roll_no: row.dataset.position,
                    section_id: sectionId,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                successCount++;
                if (statusEl) statusEl.innerHTML = '<i class="fas fa-check-circle text-success" title="আপডেট হয়েছে"></i>';
                row.classList.add('table-success');

                const rollCell = row.querySelector('.current-roll-cell');
                if (rollCell) rollCell.textContent = row.dataset.position;
                row.dataset.roll = row.dataset.position;

                if (select) {
                    const sectionCell = row.querySelector('.current-section-cell');
                    if (sectionCell) sectionCell.textContent = select.options[select.selectedIndex].text;
                }
            } else {
                failCount++;
                if (statusEl) statusEl.innerHTML = '<i class="fas fa-times-circle text-danger" title="' + (data.error || 'ব্যর্থ') + '"></i>';
            }
        } catch (err) {
            failCount++;
            if (statusEl) statusEl.innerHTML = '<i class="fas fa-times-circle text-danger" title="নেটওয়ার্ক সমস্যা"></i>';
        }

        successCountEl.textContent = successCount;
        failCountEl.textContent = failCount;
        const pct = Math.round(((i + 1) / rows.length) * 100);
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
    }

    btn.disabled = false;
    btn.innerHTML = originalHtml;
    alert('রোল/শাখা আপডেট সম্পন্ন। সফল: ' + successCount + ', ব্যর্থ: ' + failCount);
});

document.getElementById('sendSmsBtn').addEventListener('click', async function() {
    const rows = Array.from(document.querySelectorAll('#resultTable tbody tr')).filter(function(tr) {
        const cb = tr.querySelector('.roll-select');
        return cb && cb.checked;
    });

    if (rows.length === 0) {
        alert('মেসেজ পাঠানোর জন্য কমপক্ষে একজন শিক্ষার্থী নির্বাচন করুন।');
        return;
    }

    if (!confirm('নির্বাচিত ' + rows.length + ' জন শিক্ষার্থীর অভিভাবকের কাছে পৃথকভাবে মেসেজ পাঠানো হবে। এগিয়ে যেতে চান?')) {
        return;
    }

    const template = document.getElementById('message_template').value;
    const btn = this;
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> পাঠানো হচ্ছে...';

    const progressWrap = document.getElementById('smsProgressWrap');
    const progressBar = document.getElementById('smsProgressBar');
    const successCountEl = document.getElementById('smsSuccessCount');
    const failCountEl = document.getElementById('smsFailCount');
    document.getElementById('smsTotalCount').textContent = rows.length;
    progressWrap.classList.remove('d-none');

    $('#smsModal').modal('hide');

    let successCount = 0;
    let failCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const studentId = row.dataset.studentId;
        const statusEl = document.getElementById('msg-status-' + studentId);
        if (statusEl) statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" title="পাঠানো হচ্ছে..."></span>';

        const message = fillTemplate(template, row);

        try {
            const res = await fetch(sendSmsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ student_id: studentId, message: message }),
            });
            const data = await res.json();
            if (data.ok) {
                successCount++;
                if (statusEl) statusEl.innerHTML = '<i class="fas fa-check-circle text-success" title="পাঠানো হয়েছে"></i>';
            } else {
                failCount++;
                if (statusEl) statusEl.innerHTML = '<i class="fas fa-times-circle text-danger" title="' + (data.error || 'ব্যর্থ') + '"></i>';
            }
        } catch (err) {
            failCount++;
            if (statusEl) statusEl.innerHTML = '<i class="fas fa-times-circle text-danger" title="নেটওয়ার্ক সমস্যা"></i>';
        }

        successCountEl.textContent = successCount;
        failCountEl.textContent = failCount;
        const pct = Math.round(((i + 1) / rows.length) * 100);
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
    }

    btn.disabled = false;
    btn.innerHTML = originalHtml;
    progressWrap.classList.add('d-none');
    alert('মেসেজ পাঠানো সম্পন্ন। সফল: ' + successCount + ', ব্যর্থ: ' + failCount);
});
</script>
@endpush
