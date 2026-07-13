@extends('layouts.admin')

@section('title', 'Website Templates')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">Website Templates</h1>
            <p class="text-muted small mb-0">সুপার এডমিনের ডিফল্ট থিম, মেনু ও পৃষ্ঠা টেমপ্লেট থেকে বেছে নিয়ে ওয়েবসাইট চালু করুন</p>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">Website Templates</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <apply-website-template :school-id="{{ $school->id }}"></apply-website-template>
    </div>
@endsection
