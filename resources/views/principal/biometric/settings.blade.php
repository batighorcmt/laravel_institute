@extends('layouts.admin')

@section('title', 'বায়োমেট্রিক সেটিংস')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">⚙️ বায়োমেট্রিক সেটিংস</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> ড্যাশবোর্ডে ফিরে যান
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold"><i class="fas fa-key text-primary me-2"></i> ডেস্কটপ এজেন্ট টোকেন</h5>
                    <p class="text-muted small">এই টোকেনটি ডেস্কটপ এজেন্ট সফটওয়্যারে ব্যবহার করতে হবে</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">বর্তমান টোকেন:</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace text-muted bg-light" value="{{ $school->agent_token ?? 'কোনো টোকেন তৈরি করা হয়নি' }}" readonly id="agentTokenInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">
                                <i class="fas fa-copy"></i> কপি
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('principal.institute.biometric.generate_token', $school) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" onclick="return confirm('আপনি কি নিশ্চিত? নতুন টোকেন তৈরি করলে পুরনো ডেস্কটপ এজেন্ট সংযোগ বিচ্ছিন্ন হয়ে যাবে।')">
                            <i class="fas fa-sync-alt me-2"></i> {{ $school->agent_token ? 'নতুন টোকেন জেনারেট করুন' : 'টোকেন তৈরি করুন' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold"><i class="fas fa-network-wired text-info me-2"></i> API সংযোগ তথ্য</h5>
                    <p class="text-muted small">ডেস্কটপ এজেন্টে নিচের API URL টি ব্যবহার করুন</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">API Base URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace text-muted bg-light" value="{{ url('/api') }}" readonly id="apiUrlInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyApiUrl()">
                                <i class="fas fa-copy"></i> কপি
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyToken() {
        var copyText = document.getElementById("agentTokenInput");
        if(copyText.value === 'কোনো টোকেন তৈরি করা হয়নি') return;
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert("টোকেন কপি করা হয়েছে");
    }

    function copyApiUrl() {
        var copyText = document.getElementById("apiUrlInput");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert("API URL কপি করা হয়েছে");
    }
</script>
@endpush
@endsection
