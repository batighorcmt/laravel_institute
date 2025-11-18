@extends('layouts.admin')

@section('title','ছুটির দিন ব্যবস্থাপনা')

@push('styles')
<style>
    .print-only { display: none; }
    @media print {
        @page { size: A4 portrait; margin: 0.7cm; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        /* Hide global layout chrome in print */
        .main-footer, .main-header, .main-sidebar, nav.main-header, aside.main-sidebar { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; margin-top: 90px !important; }
        .card { border: 0 !important; box-shadow: none !important; }
        .card-header { display: none !important; }
        .table { font-size: 0.85rem; }
        .print-footer { position: fixed; bottom: 0; left: 0; right: 0; }
        .print-header { position: fixed; top: 0; left: 0; right: 0; z-index: 999; background: #fff; }
    }
    .holiday-card .table th, .holiday-card .table td { vertical-align: middle; }
    .weekly-holiday-card .alert { padding: 6px 8px; }
    .print-actions { display:flex; justify-content: end; align-items: center; margin-bottom: 10px; }
}
</style>
@endpush

@section('content')
@php
    // Bengali digits helper for localizing dates in print and screen
    $bnDigits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
    $toBn = function($value) use ($bnDigits) {
        return strtr((string)$value, $bnDigits);
    };
@endphp
<div class="print-only">
    @include('partials.print.header', [
        'reportTitle' => 'ছুটির দিন ব্যবস্থাপনা',
        'reportType' => 'Holidays',
    ])
</div>
<div class="container-fluid">
    <div class="print-actions no-print">
        <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> প্রিন্ট</button>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card holiday-card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i> ছুটির দিন</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('principal.institute.holidays.store', $school) }}" class="mb-3 no-print">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>শিরোনাম *</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>তারিখ *</label>
                                <input type="date" name="date" value="{{ old('date') }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>বিবরণ</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>স্ট্যাটাস</label>
                            <select name="status" class="form-control">
                                <option value="active">সক্রিয়</option>
                                <option value="inactive">নিষ্ক্রিয়</option>
                            </select>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-plus-circle mr-1"></i> যোগ করুন</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>তারিখ</th>
                                    <th>শিরোনাম</th>
                                    <th>বিবরণ</th>
                                    <th>স্ট্যাটাস</th>
                                    <th>ক্রিয়া</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $h)
                                    <tr>
                                        <td>{{ $toBn(\Carbon\Carbon::parse($h->date)->format('d/m/Y')) }}</td>
                                        <td>{{ $h->title }}</td>
                                        <td>{{ $h->description ?: '—' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $h->status==='active'?'success':'danger' }}">{{ $h->status==='active'?'সক্রিয়':'নিষ্ক্রিয়' }}</span>
                                        </td>
                                        <td class="d-flex no-print">
                                            <button type="button" class="btn btn-sm btn-info mr-1" onclick="openHolidayEdit({{ $h->id }})" title="আপডেট"><i class="fas fa-edit"></i></button>
                                            <form method="POST" action="{{ route('principal.institute.holidays.update', [$school, $h]) }}" class="mr-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="title" value="{{ $h->title }}" />
                                                <input type="hidden" name="date" value="{{ $h->date->toDateString() }}" />
                                                <input type="hidden" name="description" value="{{ $h->description }}" />
                                                <input type="hidden" name="status" value="{{ $h->status==='active'?'inactive':'active' }}" />
                                                <button class="btn btn-sm btn-warning" title="স্ট্যাটাস টগল"><i class="fas fa-sync"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('principal.institute.holidays.destroy', [$school, $h]) }}" onsubmit="return confirm('মুছে ফেলবেন?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr id="holiday-edit-row-{{ $h->id }}" class="d-none no-print">
                                        <td colspan="5">
                                            <form method="POST" action="{{ route('principal.institute.holidays.update', [$school, $h]) }}" class="border rounded p-2 bg-light">
                                                @csrf
                                                @method('PATCH')
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label>শিরোনাম *</label>
                                                        <input type="text" name="title" value="{{ $h->title }}" class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>তারিখ *</label>
                                                        <input type="date" name="date" value="{{ $h->date->toDateString() }}" class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>স্ট্যাটাস</label>
                                                        <select name="status" class="form-control form-control-sm">
                                                            <option value="active" {{ $h->status==='active'?'selected':'' }}>সক্রিয়</option>
                                                            <option value="inactive" {{ $h->status==='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>বিবরণ</label>
                                                    <textarea name="description" class="form-control form-control-sm" rows="2">{{ $h->description }}</textarea>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="closeHolidayEdit({{ $h->id }})">বাতিল</button>
                                                    <button class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i> আপডেট</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">কোনো ছুটির দিন যোগ করা হয়নি</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card weekly-holiday-card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar-week mr-2"></i> সাপ্তাহিক ছুটি</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('principal.institute.weekly-holidays.update', $school) }}" class="no-print">
                        @csrf
                        <div class="row">
                            @foreach($weekly as $day)
                                <div class="col-sm-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="weekly_holidays[]" value="{{ $day->id }}" id="day_{{ $day->id }}" {{ $day->status==='active'?'checked':'' }}>
                                        <label class="form-check-label" for="day_{{ $day->id }}">
                                            <i class="fas fa-calendar-day mr-1"></i> {{ $day->day_name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-primary mt-2"><i class="fas fa-save mr-1"></i> সংরক্ষণ করুন</button>
                    </form>

                    <hr>
                    <h6 class="mb-2"><i class="fas fa-check-circle mr-1 text-success"></i> সক্রিয় সাপ্তাহিক ছুটি</h6>
                    <div class="row">
                        @php($active = $weekly->where('status','active'))
                        @forelse($active as $d)
                            <div class="col-sm-6 mb-2">
                                <div class="alert alert-success py-2 mb-0"><i class="fas fa-calendar-check mr-1"></i> {{ $d->day_name }}</div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">কোন সাপ্তাহিক ছুটি সক্রিয় নেই</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="print-only">
    @include('partials.print.footer')
</div>
@push('scripts')
<script>
    function openHolidayEdit(id){
        var row = document.getElementById('holiday-edit-row-'+id);
        if(row){ row.classList.remove('d-none'); }
    }
    function closeHolidayEdit(id){
        var row = document.getElementById('holiday-edit-row-'+id);
        if(row){ row.classList.add('d-none'); }
    }
</script>
@endpush
@endsection
