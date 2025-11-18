@extends('layouts.admin')
@section('title','শিক্ষার্থী প্রোফাইল')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="m-0">{{ $student->full_name }} <small class="text-muted">({{ $student->student_id }})</small></h1>
    <div class="mt-1">
      @php($st=$student->status)
      <span class="badge badge-{{ $st==='active'?'success':($st==='inactive'?'secondary':($st==='graduated'?'info':'warning')) }}">{{ ucfirst($st) }}</span>
      @if($currentYear)
        <span class="badge badge-primary">বর্তমান বছর: {{ $currentYear->name }}</span>
      @endif
      <span class="badge badge-dark">মোট ভর্তি বর্ষ: {{ $totalYears }}</span>
      @if($activeEnrollment)
        <span class="badge badge-info">বর্তমান ক্লাস: {{ $activeEnrollment->class?->name }} @if($activeEnrollment->section) ({{ $activeEnrollment->section->name }}) @endif</span>
      @endif
    </div>
  </div>
  <div class="text-right">
    <a href="{{ route('principal.institute.students.index',$school) }}" class="btn btn-secondary mr-1"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
    <a href="{{ route('principal.institute.students.edit',[$school,$student]) }}" class="btn btn-primary"><i class="fas fa-edit mr-1"></i> সম্পাদনা</a>
  </div>
</div>
<div class="row">
  <!-- Left column: Profile & Contacts -->
  <div class="col-lg-4">
    <div class="card mb-3 shadow-sm">
      <div class="card-header"><strong>মৌলিক তথ্য</strong></div>
      <div class="card-body small">
        <table class="table table-sm mb-0">
          <tbody>
          <tr><th class="w-25">বাংলা নাম</th><td>{{ $student->student_name_bn }}</td></tr>
          <tr><th>ইংরেজি নাম</th><td>{{ $student->student_name_en ?: '—' }}</td></tr>
          <tr><th>পিতা</th><td>{{ $student->father_name_bn ?? $student->father_name }}</td></tr>
          <tr><th>মাতা</th><td>{{ $student->mother_name_bn ?? $student->mother_name }}</td></tr>
          <tr><th>জন্ম তারিখ</th><td>{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d-m-Y') }}</td></tr>
          <tr><th>লিঙ্গ</th><td>{{ $student->gender=='male'?'ছেলে':'মেয়ে' }}</td></tr>
          <tr><th>রক্তের গ্রুপ</th><td>{{ $student->blood_group ?: '—' }}</td></tr>
          <tr><th>ভর্তি তারিখ</th><td>{{ \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') }}</td></tr>
          <tr><th>ফোন</th><td>{{ $student->guardian_phone }}</td></tr>
          <tr><th>ঠিকানা</th><td>{{ $student->address }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-3 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center"><strong>দল / কার্যক্রম</strong>
        <button class="btn btn-sm btn-outline-success" data-toggle="collapse" data-target="#teamAttachForm"><i class="fas fa-plus"></i></button>
      </div>
      <div class="collapse p-2" id="teamAttachForm">
        <form class="form-inline" method="post" action="{{ route('principal.institute.students.teams.attach',[$school,$student]) }}">@csrf
          <select name="team_id" class="form-control form-control-sm mr-2">
            @foreach($allTeams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
          </select>
          <input type="date" name="joined_at" class="form-control form-control-sm mr-2" value="{{ now()->toDateString() }}">
          <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
        </form>
      </div>
      <ul class="list-group list-group-flush small">
        @forelse($memberships as $t)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $t->name }} <small class="text-muted">{{ $t->pivot->joined_at ?: '' }}</small></span>
            <form method="post" action="{{ route('principal.institute.students.teams.detach',[$school,$student,$t]) }}" onsubmit="return confirm('সরিয়ে ফেলবেন?');">@csrf @method('DELETE')
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
            </form>
          </li>
        @empty
          <li class="list-group-item text-muted">কোনো দলে নেই</li>
        @endforelse
      </ul>
    </div>

    <div class="card shadow-sm">
      <div class="card-header"><strong>কার্যক্রম টাইমলাইন</strong></div>
      <div class="card-body p-2">
        <ul class="timeline list-unstyled mb-0 small">
          @forelse($timeline as $e)
            <li class="mb-2">
              <div class="d-flex">
                <div class="text-muted" style="width:85px">{{ \Carbon\Carbon::parse($e['date'])->format('d-m-Y') }}</div>
                <div>
                  <span class="font-weight-bold">{{ $e['label'] }}</span><br>
                  <span class="text-muted">{{ $e['detail'] }}</span>
                </div>
              </div>
              <hr class="my-1">
            </li>
          @empty
            <li class="text-muted">কোনো ইভেন্ট নেই</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

  <!-- Right column: Academic History & Subjects -->
  <div class="col-lg-8">
    <div class="card mb-3 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>একাডেমিক ইতিহাস</strong>
        <button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#enrollForm"><i class="fas fa-plus"></i> নতুন ভর্তি</button>
      </div>
      <div class="collapse border-bottom p-3" id="enrollForm">
        <form method="post" action="{{ route('principal.institute.students.enrollments.add',[$school,$student]) }}" class="small">@csrf
          <div class="form-row">
            <div class="form-group col-md-2"><label class="mb-0">বর্ষ *</label><input type="number" name="academic_year" class="form-control form-control-sm" required value="{{ date('Y') }}"></div>
            <div class="form-group col-md-2"><label class="mb-0">ক্লাস *</label>
              <select name="class_id" class="form-control form-control-sm" required>
                @foreach(\App\Models\SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get() as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2"><label class="mb-0">সেকশন</label>
              <select name="section_id" class="form-control form-control-sm">
                <option value="">—</option>
                @foreach(\App\Models\Section::forSchool($school->id)->orderBy('name')->get() as $s)
                  <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2"><label class="mb-0">গ্রুপ</label>
              <select name="group_id" class="form-control form-control-sm">
                <option value="">—</option>
                @foreach(\App\Models\Group::forSchool($school->id)->orderBy('name')->get() as $g)
                  <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2"><label class="mb-0">রোল *</label><input type="number" name="roll_no" class="form-control form-control-sm" required min="1"></div>
            <div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-check mr-1"></i> সংযুক্ত</button></div>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="thead-light"><tr><th>বর্ষ</th><th>ক্লাস</th><th>সেকশন</th><th>গ্রুপ</th><th>রোল</th><th>বিষয়সমূহ</th><th></th></tr></thead>
          <tbody>
          @forelse($enrollments as $en)
            @php($subjectBadges = $en->subjects->map(function($ss){
              $code = optional($ss->subject)->code; if(!$code) return null;
              return '<span class="badge badge-'.($ss->is_optional?'primary':'secondary').' mr-1">'.e($code).($ss->is_optional?' (ঐচ্ছিক)':'').'</span>';
            })->filter()->implode(' '))
            <tr class="{{ $activeEnrollment && $activeEnrollment->id === $en->id ? 'table-info' : '' }}">
              <td>{{ $en->academic_year }}</td>
              <td>{{ $en->class?->name }}</td>
              <td>{{ $en->section?->name ?: '—' }}</td>
              <td>{{ $en->group?->name ?: '—' }}</td>
              <td>{{ $en->roll_no }}</td>
              <td class="small">{!! $subjectBadges ?: '<span class="text-muted">নির্ধারিত নয়</span>' !!}</td>
              <td class="text-nowrap">
                <a href="{{ route('principal.institute.enrollments.subjects.edit',[$school,$en]) }}" class="btn btn-sm btn-outline-dark"><i class="fas fa-book"></i></a>
                <form method="post" action="{{ route('principal.institute.students.enrollments.remove',[$school,$student,$en]) }}" class="d-inline" onsubmit="return confirm('মুছে ফেলবেন?');">@csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">কোনো ভর্তি তথ্য নেই</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($activeEnrollment)
    <div class="card shadow-sm">
      <div class="card-header"><strong>বর্তমান বর্ষের বিষয়সমূহ</strong></div>
      <div class="card-body">
        @php($curr = $currentSubjects->filter(fn($s)=>$s['code']))
        @if($curr->isEmpty())
          <div class="text-muted">কোনো বিষয় নির্বাচন করা হয়নি</div>
        @else
          @foreach($curr as $s)
            <span class="badge badge-{{ $s['optional']?'primary':'secondary' }} mr-1 mb-1">{{ $s['code'] }}{{ $s['optional']?' (ঐচ্ছিক)':'' }}</span>
          @endforeach
        @endif
      </div>
    </div>
    @endif
  </div>
</div>
@endsection