@extends('layouts.admin')
@section('title','পরীক্ষার ফলাফল')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ফলাফল: {{ $exam->name }} ({{ $exam->type==='subject'?'বিষয়ভিত্তিক':'সামগ্রিক' }})</h4>
    <div>
        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#smsModal"><i class="fas fa-sms"></i> SMS পাঠান</button>
        <a href="{{ route('principal.institute.admissions.exams.results.print',[$school,$exam]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-print"></i> প্রিন্ট ভিউ</a>
        <a href="{{ route('principal.institute.admissions.exams.index',$school) }}" class="btn btn-sm btn-secondary">পরীক্ষা তালিকা</a>
        <a href="{{ route('principal.institute.admissions.exams.marks',[$school,$exam]) }}" class="btn btn-sm btn-outline-info">Marks Entry</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>মেধাক্রম</th>
                        <th>Roll</th>
                        <th>নাম</th>
                        @if($exam->type==='subject')
                            @foreach($exam->subjects->sortBy('display_order') as $sub)
                                <th>{{ $sub->subject_name }}</th>
                            @endforeach
                        @endif
                        <th>মোট</th>
                        <th>পাস/ফেল</th>
                        <th>ফেল বিষয়</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $res)
                        @php($app = $res->application)
                        <tr>
                            <td><strong>{{ $res->merit_position }}</strong></td>
                            <td>{{ $app?->admission_roll_no }}</td>
                            <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $app?->name_bn ?? $app?->name_en }}</td>
                            @if($exam->type==='subject')
                                @php($marksMap = $exam->marks()->where('application_id',$app->id)->get()->keyBy('subject_id'))
                                @foreach($exam->subjects->sortBy('display_order') as $sub)
                                    <td>{{ $marksMap[$sub->id]->obtained_mark ?? 0 }}</td>
                                @endforeach
                            @endif
                            <td><strong>{{ $res->total_obtained }}</strong></td>
                            <td>
                                @if($res->is_pass)
                                    <span class="badge badge-success">PASS</span>
                                @else
                                    <span class="badge badge-danger">FAIL</span>
                                @endif
                            </td>
                            <td>{{ $res->failed_subjects_count }}</td>
                        </tr>
                    @endforeach
                    @if($results->isEmpty())
                        <tr><td colspan="{{ 6 + ($exam->type==='subject' ? $exam->subjects->count() : 0) }}" class="text-center py-4">কোন ফলাফল নেই</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $results->links() }}</div>
</div>

<!-- SMS Modal -->
<div class="modal fade" id="smsModal" tabindex="-1" role="dialog" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('principal.institute.admissions.exams.results.send-sms',[$school,$exam]) }}" id="smsForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="smsModalLabel"><i class="fas fa-sms"></i> ফলাফল SMS পাঠান</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <strong>মোট প্রাপক:</strong> {{ $results->total() }} জন অভিভাবক
                        <span class="ml-3"><i class="fas fa-info-circle"></i> প্রতিটি শিক্ষার্থীর অভিভাবকের মোবাইলে SMS যাবে</span>
                    </div>

                    <div class="form-group">
                        <label><strong>SMS টেম্পলেট</strong></label>
                        
<textarea name="message_template" id="message_template" class="form-control" rows="8" required placeholder="আপনার বার্তা লিখুন...">{{ $school->name_bn }}
ভর্তি পরীক্ষার ফলাফল
পরীক্ষা: {{ $exam->name }}
নাম: {student_name}
রোল: {roll_no}
প্রাপ্ত নম্বর: {total_marks}
ফলাফল: {result_status}
মেধাক্রম: {merit_position}
</textarea>
                        <div class="mt-2">
                            <small class="text-muted d-block mb-2"><strong>উপলব্ধ কি-ওয়ার্ড:</strong> (ক্লিক করুন টেম্পলেটে যোগ করতে)</small>
                            <div class="d-flex flex-wrap" style="gap:5px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{student_name}">শিক্ষার্থীর নাম</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{roll_no}">রোল নং</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{merit_position}">মেধাক্রম</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{total_marks}">প্রাপ্ত নম্বর</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{result_status}">পাস/ফেল স্ট্যাটাস</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{failed_subjects_info}">ফেল বিষয় তথ্য</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{exam_name}">পরীক্ষার নাম</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn" data-keyword="{school_name}">স্কুলের নাম</button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted" id="sms_counter">0 অক্ষর • 0 SMS</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="only_pass" name="only_pass" value="1">
                            <label class="custom-control-label" for="only_pass">শুধুমাত্র উত্তীর্ণদের পাঠান</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="only_fail" name="only_fail" value="1">
                            <label class="custom-control-label" for="only_fail">শুধুমাত্র অনুত্তীর্ণদের পাঠান</label>
                        </div>
                        <hr>
                        <div class="d-flex align-items-center" style="gap:15px;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="sync" name="sync" value="1">
                                <label class="custom-control-label" for="sync">সরাসরি পাঠান (কিউ ছাড়াই)</label>
                            </div>
                            <div>
                                <label class="mb-0" for="limit"><small>সর্বোচ্চ প্রাপক (টেস্ট)</small></label>
                                <input type="number" class="form-control form-control-sm" id="limit" name="limit" min="1" max="100" placeholder="25" style="width:100px;">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">টেস্টের জন্য উপরে চেকবক্স চালু করে সীমা দিন (যেমন 10–25)। লাইভ পাঠানোর সময় সীমা খালি রাখুন।</small>
                    </div>

                    <div class="alert alert-warning py-2">
                        <i class="fas fa-exclamation-triangle"></i> <strong>সতর্কতা:</strong> SMS পাঠানোর জন্য আপনার SMS ব্যালেন্স থেকে চার্জ কাটা হবে।
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-success" id="sendSmsBtn"><i class="fas fa-paper-plane"></i> SMS পাঠান</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Keyword insertion
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

// SMS Counter
function detectUnicode(str) {
    for(var i=0; i<str.length; i++) {
        if(str.charCodeAt(i) > 127) return true;
    }
    return false;
}

function computeParts(len, unicode) {
    if(len === 0) return {parts: 0, per: unicode ? 70 : 160};
    var single = unicode ? 70 : 160;
    var multi = unicode ? 67 : 153;
    if(len <= single) return {parts: 1, per: single};
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

// Prevent double submit
document.getElementById('smsForm').addEventListener('submit', function() {
    var btn = document.getElementById('sendSmsBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> পাঠানো হচ্ছে...';
});

// Mutual exclusive checkboxes
document.getElementById('only_pass').addEventListener('change', function() {
    if(this.checked) {
        document.getElementById('only_fail').checked = false;
    }
});
document.getElementById('only_fail').addEventListener('change', function() {
    if(this.checked) {
        document.getElementById('only_pass').checked = false;
    }
});
</script>
@endsection