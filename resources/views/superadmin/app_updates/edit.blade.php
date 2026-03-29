@extends('layouts.admin')

@section('title', 'আপডেট এডিট')

@section('content')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">অ্যাপ আপডেট এডিট করুন</h1></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="editForm" action="{{ route('superadmin.app-updates.update', $appUpdate) }}" method="post" enctype="multipart/form-data">
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
                    <label class="form-label">নতুন APK ফাইল (সর্বোচ্চ ২০০ এমবি)</label>
                    <input type="file" name="apk_file" id="apk_file" class="form-control @error('apk_file') is-invalid @enderror" accept=".apk">
                    <div class="small mt-1 text-muted">ফাইল আপডেট না করতে চাইলে ফাঁকা রাখুন। বর্তমান ফাইল: <code>{{ basename($appUpdate->apk_url) }}</code></div>
                    @error('apk_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    
                    <div id="progressContainer" class="mt-3" style="display:none;">
                        <div class="progress" style="height: 25px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div id="statusMessage" class="small mt-1 text-muted">ফাইল আপডেট হচ্ছে, দয়া করে অপেক্ষা করুন...</div>
                    </div>
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
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4">সেভ করুন</button>
                    <a href="{{ route('superadmin.app-updates.index') }}" class="btn btn-default ml-2">পূর্বের পেজে যান</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('editForm').addEventListener('submit', function(e) {
    if (document.getElementById('apk_file').files.length === 0) {
        return; // Normal form submission for metadata-only updates
    }
    
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.getElementById('progressContainer');
    const statusMessage = document.getElementById('statusMessage');
    
    submitBtn.disabled = true;
    progressContainer.style.display = 'block';
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressBar.innerText = percentComplete + '%';
            progressBar.setAttribute('aria-valuenow', percentComplete);
            
            if (percentComplete === 100) {
                statusMessage.innerText = 'ফাইল সেভ করা হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...';
                progressBar.classList.remove('progress-bar-animated');
            }
        }
    });
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200 || xhr.status === 201 || xhr.status === 302) {
                statusMessage.innerHTML = '<span class="text-success fw-bold">সফলভাবে আপডেট করা হয়েছে!</span>';
                setTimeout(() => {
                    window.location.href = "{{ route('superadmin.app-updates.index') }}";
                }, 1000);
            } else {
                submitBtn.disabled = false;
                progressContainer.style.display = 'none';
                alert('Update failed. Please check validation or file size.');
            }
        }
    };
    
    xhr.open('POST', form.action, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
});
</script>
@endpush
