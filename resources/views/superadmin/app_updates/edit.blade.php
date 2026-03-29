@extends('layouts.admin')

@section('title', 'আপডেট এডিট')

@section('content')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">অ্যাপ আপডেট এডিট করুন</h1></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('superadmin.app-updates.update', $appUpdate) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ভার্সন নাম</label>
                        <input type="text" name="version_name" value="{{ old('version_name', $appUpdate->version_name) }}" class="form-control @error('version_name') is-invalid @enderror">
                        @error('version_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ভার্সন কোড</label>
                        <input type="number" name="version_code" value="{{ old('version_code', $appUpdate->version_code) }}" class="form-control @error('version_code') is-invalid @enderror">
                        @error('version_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">নতুন APK ফাইল (সর্বোচ্চ ৫০ এমবি)</label>
                    <input type="file" name="apk_file" class="form-control @error('apk_file') is-invalid @enderror" accept=".apk">
                    <div class="small mt-1 text-muted">ফাইল আপডেট না করতে চাইলে ফাঁকা রাখুন। বর্তমান ফাইল: <code>{{ basename($appUpdate->apk_url) }}</code></div>
                    @error('apk_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">রিলিজ নোটস</label>
                    <textarea name="release_notes" rows="4" class="form-control">{{ old('release_notes', $appUpdate->release_notes) }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_mandatory" value="1" class="custom-control-input" id="is_mandatory" {{ old('is_mandatory', $appUpdate->is_mandatory) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_mandatory">এটি কি ম্যান্ডেটরি আপডেট?</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active" {{ old('is_active', $appUpdate->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">আপডেটটি কি এখন অ্যাক্টিভ থাকবে?</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-4">সেভ করুন</button>
                    <a href="{{ route('superadmin.app-updates.index') }}" class="btn btn-default ml-2">পূর্বের পেজে যান</a>
                </div>
            </form>
        </div>
    </div>
@endsection
