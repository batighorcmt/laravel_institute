@extends('layouts.admin')
@section('title', 'নতুন স্কুল')
@section('content')
<div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">নতুন স্কুল যোগ</h1></div>
    <div class="col-sm-6 text-right"><a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a></div>
</div>

@php($errorBag = ($errors instanceof \Illuminate\Support\ViewErrorBag) ? $errors : (session('errors') instanceof \Illuminate\Support\ViewErrorBag ? session('errors') : null))
@if($errorBag && $errorBag->any())
 <div class="alert alert-danger"><ul class="mb-0">@foreach($errorBag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('superadmin.schools.store') }}" method="post" enctype="multipart/form-data">
<div class="card mb-3">
 <div class="card-header"><strong>প্রতিষ্ঠানের তথ্য</strong></div>
 <div class="card-body">
    @csrf
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>স্কুলের নাম (ইংরেজি) *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>স্কুলের নাম (বাংলা)</label>
        <input type="text" name="name_bn" class="form-control" value="{{ old('name_bn') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>কোড *</label>
        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
      </div>
      <div class="form-group col-md-4">
        <label>স্কুল কোড</label>
        <input type="text" name="school_code" class="form-control" value="{{ old('school_code') }}">
      </div>
      <div class="form-group col-md-4">
        <label>স্ট্যাটাস *</label>
        <select name="status" class="form-control" required>
          <option value="active" {{ old('status')=='active'?'selected':'' }}>সক্রিয়</option>
          <option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>ই.আই.আই.এন নম্বর (EIIN)</label>
        <input type="text" name="eiin" class="form-control" value="{{ old('eiin') }}">
      </div>
      <div class="form-group col-md-4">
        <label>এমপিও কোড (MPO Code)</label>
        <input type="text" name="mpo_code" class="form-control" value="{{ old('mpo_code') }}">
      </div>
      <div class="form-group col-md-4">
        <label>প্রতিষ্ঠার সাল</label>
        <input type="text" name="founding_year" class="form-control" value="{{ old('founding_year') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>ফোন</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
      </div>
      <div class="form-group col-md-4">
        <label>মোবাইল নম্বর</label>
        <input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}">
      </div>
      <div class="form-group col-md-4">
        <label>ইমেইল</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>ওয়েবসাইট</label>
        <input type="url" name="website" class="form-control" value="{{ old('website') }}">
      </div>
      <div class="form-group col-md-4">
        <label>লোগো (PNG/JPG)</label>
        <input type="file" name="logo" class="form-control-file">
      </div>
      <div class="form-group col-md-4">
        <label>ডোমেইন (উদাঃ school.batighorbd.com) *</label>
        <input type="text" name="domain" class="form-control" value="{{ old('domain') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>সংক্ষিপ্ত ঠিকানা (ইংরেজি)</label>
        <input type="text" name="short_address_en" class="form-control" value="{{ old('short_address_en') }}">
      </div>
      <div class="form-group col-md-6">
        <label>সংক্ষিপ্ত ঠিকানা (বাংলা)</label>
        <input type="text" name="short_address_bn" class="form-control" value="{{ old('short_address_bn') }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ঠিকানা (ইংরেজি)</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
      </div>
      <div class="form-group col-md-6">
        <label>ঠিকানা (বাংলা)</label>
        <textarea name="address_bn" class="form-control" rows="2">{{ old('address_bn') }}</textarea>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-3">
        <label>বিভাগ</label>
        <select name="division_id" id="division_id" class="form-control select2">
          <option value="">বিভাগ নির্বাচন করুন</option>
          @foreach($divisions as $div)
            <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>
              {{ $div->bn_name }} ({{ $div->name }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <label>জেলা</label>
        <select name="district_id" id="district_id" class="form-control select2">
          <option value="">জেলা নির্বাচন করুন</option>
          @foreach($districts as $dist)
            <option value="{{ $dist->id }}" {{ old('district_id') == $dist->id ? 'selected' : '' }}>
              {{ $dist->bn_name }} ({{ $dist->name }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <label>উপজেলা</label>
        <select name="thana_id" id="thana_id" class="form-control select2">
          <option value="">উপজেলা নির্বাচন করুন</option>
          @foreach($thanas as $thana)
            <option value="{{ $thana->id }}" {{ old('thana_id') == $thana->id ? 'selected' : '' }}>
              {{ $thana->bn_name }} ({{ $thana->name }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <label>ইউনিয়ন</label>
        <select name="union_id" id="union_id" class="form-control select2">
          <option value="">ইউনিয়ন নির্বাচন করুন</option>
          @foreach($unions as $union)
            <option value="{{ $union->id }}" {{ old('union_id') == $union->id ? 'selected' : '' }}>
              {{ $union->bn_name }} ({{ $union->name }})
            </option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>বর্ণনা</label>
      <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>
 </div>
</div>

<div class="card">
  <div class="card-header"><strong>প্রতিষ্ঠান প্রধানের তথ্য</strong></div>
  <div class="card-body">
    <div class="alert alert-info py-2">এই ইমেইলটি প্রতিষ্ঠান লগইনের জন্য ব্যবহার হবে এবং অ্যাডমিন (Principal) রোল পাবে।</div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>প্রতিষ্ঠান প্রধানের নাম (ইংরেজি) *</label>
        <input type="text" name="principal_name_en" class="form-control" value="{{ old('principal_name_en') }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>প্রতিষ্ঠান প্রধানের নাম (বাংলা) *</label>
        <input type="text" name="principal_name_bn" class="form-control" value="{{ old('principal_name_bn') }}" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>পদবী *</label>
        <input type="text" name="principal_designation" class="form-control" value="{{ old('principal_designation','প্রধান শিক্ষক') }}" required>
      </div>
      <div class="form-group col-md-6">
        <label>মোবাইল *</label>
        <input type="text" name="principal_phone" class="form-control" value="{{ old('principal_phone') }}" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>ইমেইল (লগইন) *</label>
        <input type="email" name="principal_email" class="form-control" value="{{ old('principal_email') }}" required>
      </div>
    </div>
    <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
  </div>
 </div>
</div>
</form>
@push('scripts')
<script>
window.addEventListener('DOMContentLoaded', function() {
    console.log('Location Script DOMContentLoaded initialized!');
    
    // When Division changes
    $('#division_id').on('change', function() {
        var divisionId = $(this).val();
        console.log('Division changed event triggered! Value:', divisionId);
        
        // Reset district, thana, union options
        $('#district_id').html('<option value="">জেলা নির্বাচন করুন</option>').trigger('change');
        $('#thana_id').html('<option value="">উপজেলা নির্বাচন করুন</option>').trigger('change');
        $('#union_id').html('<option value="">ইউনিয়ন নির্বাচন করুন</option>').trigger('change');

        if (divisionId) {
            $.ajax({
                url: "/superadmin/location/districts",
                type: "GET",
                data: { division_id: divisionId },
                success: function(data) {
                    $.each(data, function(key, district) {
                        $('#district_id').append('<option value="' + district.id + '">' + district.bn_name + ' (' + district.name + ')</option>');
                    });
                    // Trigger standard change event so Select2 updates and cascades
                    $('#district_id').trigger('change');
                }
            });
        }
    });

    // When District changes
    $('#district_id').on('change', function() {
        var districtId = $(this).val();
        
        // Reset thana, union options
        $('#thana_id').html('<option value="">উপজেলা নির্বাচন করুন</option>').trigger('change');
        $('#union_id').html('<option value="">ইউনিয়ন নির্বাচন করুন</option>').trigger('change');

        if (districtId) {
            $.ajax({
                url: "/superadmin/location/thanas",
                type: "GET",
                data: { district_id: districtId },
                success: function(data) {
                    $.each(data, function(key, thana) {
                        $('#thana_id').append('<option value="' + thana.id + '">' + thana.bn_name + ' (' + thana.name + ')</option>');
                    });
                    // Trigger standard change event so Select2 updates and cascades
                    $('#thana_id').trigger('change');
                }
            });
        }
    });

    // When Thana changes
    $('#thana_id').on('change', function() {
        var thanaId = $(this).val();
        
        // Reset union options
        $('#union_id').html('<option value="">ইউনিয়ন নির্বাচন করুন</option>').trigger('change');

        if (thanaId) {
            $.ajax({
                url: "/superadmin/location/unions",
                type: "GET",
                data: { thana_id: thanaId },
                success: function(data) {
                    $.each(data, function(key, union) {
                        $('#union_id').append('<option value="' + union.id + '">' + union.bn_name + ' (' + union.name + ')</option>');
                    });
                    // Trigger standard change event so Select2 updates and cascades
                    $('#union_id').trigger('change');
                }
            });
        }
    });
});
</script>
@endpush
@endsection
