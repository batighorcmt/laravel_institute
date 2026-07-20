@extends('layouts.admin')

@section('title', 'মোবাইল অ্যাপ সেটিংস')

@section('content')
    <div class="row mb-2">
        <div class="col-sm-12"><h1 class="m-0">মোবাইল অ্যাপ সেটিংস</h1></div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            <p class="text-muted">
                ইনস্টল করা প্রতিটি মোবাইল অ্যাপ চালু হওয়ার সময় এই সার্ভার URL-টি স্বয়ংক্রিয়ভাবে
                পড়ে নেয় এবং এরপর থেকে সব ডেটা অনুরোধ এই URL-এ পাঠায়। জরুরি প্রয়োজনে সার্ভার
                পরিবর্তন করতে হলে (যেমন হোস্টিং বদল), শুধু এই মান পরিবর্তন করলেই — অ্যাপ স্টোরে
                নতুন ভার্সন না দিয়েই — সব ব্যবহারকারীর অ্যাপ নতুন সার্ভারে চলে যাবে।
            </p>

            <form action="{{ route('superadmin.mobile-settings.update') }}" method="post" class="mt-3">
                @csrf
                <div class="form-group">
                    <label for="api_base_url">API সার্ভার URL</label>
                    <input type="url" name="api_base_url" id="api_base_url" class="form-control @error('api_base_url') is-invalid @enderror"
                        value="{{ old('api_base_url', $apiBaseUrl) }}" placeholder="https://institute.batighorbd.com/api/v1/" required>
                    @error('api_base_url')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">শেষে অবশ্যই "/" (স্ল্যাশ) দিতে হবে।</small>
                </div>
                <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
            </form>
        </div>
    </div>
@endsection
