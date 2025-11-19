@extends('layouts.admin')
@section('title','Admission Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-graduate mr-1"></i> Admission Settings</h1>
  <div>
    <a href="{{ route('principal.institute.admissions.applications', $school) }}" class="btn btn-outline-primary">Applications</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.admissions.settings.update', $school) }}">
      @csrf
      <div class="form-group form-check">
        <input type="checkbox" name="admissions_enabled" value="1" class="form-check-input" id="admissions_enabled" {{ $school->admissions_enabled ? 'checked' : '' }}>
        <label class="form-check-label" for="admissions_enabled">Enable public admission form</label>
      </div>
      <div class="form-group mt-3">
        <label for="admission_academic_year_id" class="font-weight-semibold">Admission Academic Year *</label>
        <select name="admission_academic_year_id" id="admission_academic_year_id" class="form-control" required>
          <option value="">-- Choose Year --</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected($school->admission_academic_year_id==$year->id)>{{ $year->name }} ({{ $year->start_date }} - {{ $year->end_date }})</option>
          @endforeach
        </select>
        <small class="text-muted">এ শিক্ষাবর্ষের জন্য আবেদন গ্রহণ করা হবে।</small>
      </div>
      <div class="form-row mt-3">
        <div class="form-group col-md-4">
          <label class="font-weight-semibold">Exam Date & Time</label>
          @php($dtVal = isset($examDatetime) && $examDatetime ? \Carbon\Carbon::parse($examDatetime)->format('Y-m-d\TH:i') : '')
          <input type="datetime-local" name="exam_datetime" class="form-control" value="{{ $dtVal }}">
          <small class="text-muted">পরীক্ষার তারিখ ও সময় নির্ধারণ করুন</small>
        </div>
      </div>
      <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="mb-0 font-weight-semibold">Exam Venues (multiple)</label>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addVenueRow()">Add Venue</button>
        </div>
        <div id="venuesWrap">
          @php($vv = isset($venues) && is_array($venues) ? $venues : [])
          @if(empty($vv))
            @php($vv = [["name"=>"","address"=>""]])
          @endif
          @foreach($vv as $i => $v)
            <div class="form-row align-items-center venue-row mb-2">
              <div class="col-md-4 mb-1"><input type="text" name="venues_name[]" value="{{ $v['name'] ?? '' }}" class="form-control" placeholder="Venue name"></div>
              <div class="col-md-6 mb-1"><input type="text" name="venues_address[]" value="{{ $v['address'] ?? '' }}" class="form-control" placeholder="Address"></div>
              <div class="col-md-2 mb-1"><button type="button" class="btn btn-outline-danger btn-block" onclick="this.closest('.venue-row').remove()">Remove</button></div>
            </div>
          @endforeach
        </div>
      </div>
      <button class="btn btn-primary mt-2">Save</button>
    </form>

    <hr>
    <h5>Public Form Link</h5>
    @if($school->admissions_enabled && $school->admission_academic_year_id)
      <code>{{ url('/admission/'.$school->code) }}</code>
      <div class="text-muted">Share this link for public applications.</div>
    @else
      <div class="text-muted">Enable admissions to generate a public link.</div>
    @endif

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Per-Class Admission Settings</h5>
      <a href="{{ route('principal.institute.admissions.class-settings.index', $school) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
    </div>
    @if(isset($classSettings) && $classSettings->count())
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0">
          <thead class="bg-light">
            <tr>
              <th style="width:80px">Class</th>
              <th style="width:80px">Fee (৳)</th>
              <th style="width:140px">Deadline</th>
              <th style="width:110px">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($classSettings as $cs)
              @php($deadline = $cs->deadline)
              <tr>
                <td class="align-middle">{{ $cs->class_code }}</td>
                <td class="align-middle">{{ (int)$cs->fee_amount }}</td>
                <td class="align-middle">{{ $deadline ? $deadline->format('d-m-Y') : '—' }}</td>
                <td class="align-middle">
                  @if($cs->active && (!$deadline || $deadline->isFuture()))
                    <span class="badge badge-success">Open</span>
                  @elseif($cs->active && $deadline && $deadline->isPast())
                    <span class="badge badge-warning text-dark">Expired</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="text-muted">কোনো শ্রেণির ফি ও সময়সীমা এখনো সেট করা হয়নি। <a href="{{ route('principal.institute.admissions.class-settings.index', $school) }}">Manage</a></div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  function addVenueRow(){
    var w = document.getElementById('venuesWrap');
    if(!w) return;
    var row = document.createElement('div');
    row.className = 'form-row align-items-center venue-row mb-2';
    row.innerHTML = '<div class="col-md-4 mb-1"><input type="text" name="venues_name[]" class="form-control" placeholder="Venue name"></div>'+
                    '<div class="col-md-6 mb-1"><input type="text" name="venues_address[]" class="form-control" placeholder="Address"></div>'+
                    '<div class="col-md-2 mb-1"><button type="button" class="btn btn-outline-danger btn-block" onclick="this.closest(\' .venue-row \' ).remove()">Remove</button></div>';
    w.appendChild(row);
  }
</script>
@endpush
