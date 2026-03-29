@extends('layouts.admin')

@section('title', 'নতুন অ্যাপ রিলিজ')

@section('content')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">নতুন আপডেট রিলিজ করুন</h1></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('superadmin.app-updates.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ভার্সন নাম (উদা: 1.0.0)</label>
                        <input type="text" name="version_name" value="{{ old('version_name') }}" class="form-control @error('version_name') is-invalid @enderror" placeholder="ভার্সন নাম লিখুন">
                        @error('version_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ভার্সন কোড (উদা: 1)</label>
                        <input type="number" name="version_code" value="{{ old('version_code') }}" class="form-control @error('version_code') is-invalid @enderror" placeholder="ভার্সন কোড লিখুন">
                        @error('version_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">APK ফাইল (সর্বোচ্চ ৫০ এমবি)</label>
                    <input type="file" name="apk_file" class="form-control @error('apk_file') is-invalid @enderror" accept=".apk">
                    @error('apk_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">রিলিজ নোটস (অপশনাল)</label>
                    <textarea name="release_notes" rows="4" class="form-control" placeholder="আপডেট এর বৈশিষ্ট্য সমুহ লিখুন">{{ old('release_notes') }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_mandatory" value="1" class="custom-control-input" id="is_mandatory" {{ old('is_mandatory') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_mandatory">এটি কি ম্যান্ডেটরি আপডেট? (ম্যান্ডেটরি হলে ব্যবহারকারী আপডেট ছাড়া এপ ব্যবহার করতে পারবে না)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active" checked>
                            <label class="custom-control-label" for="is_active">আপডেটটি কি এখন রিলিজ করতে চান?</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-rocket mr-1"></i> রিলিজ করুন</button>
                    <a href="{{ route('superadmin.app-updates.index') }}" class="btn btn-default ml-2">পূর্বের পেজে যান</a>
                </div>
            </form>
        </div>
    </div>
@endsection
