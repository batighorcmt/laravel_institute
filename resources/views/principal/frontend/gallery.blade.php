@extends('layouts.admin')

@section('title', 'গ্যালারি ম্যানেজমেন্ট')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">গ্যালারি ম্যানেজমেন্ট</h1>
            <p class="text-muted small mb-0">ফ্রন্টএন্ড ওয়েবসাইটের ছবি গ্যালারি ও এলবাম পরিচালনা করুন</p>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">গ্যালারি ম্যানেজমেন্ট</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <gallery-manager :school-id="{{ $school->id }}"></gallery-manager>
    </div>
@endsection
