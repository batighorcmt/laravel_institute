@extends('layouts.admin')

@section('title', 'কর্মচারী ব্যবস্থাপনা')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">কর্মচারী ব্যবস্থাপনা</h1>
            <p class="text-muted small mb-0">প্রতিষ্ঠানের নন-টিচিং স্টাফ যুক্ত, সম্পাদনা ও পরিচালনা করুন</p>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">কর্মচারী</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <staff-manager
            :school-id="{{ $school->id }}"
            print-url="{{ route('principal.institute.staff.print', $school) }}"
        ></staff-manager>
    </div>
@endsection
