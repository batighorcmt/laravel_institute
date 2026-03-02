@extends('layouts.admin')

@section('title', 'নোটিশ ব্যবস্থাপনা')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>নোটিশ ব্যবস্থাপনা</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">নোটিশ</li>
            </ol>
        </nav>
    </div>
@stop

@section('content')
    <div id="app">
        <notice-manager :school-id="{{ $school->id }}"></notice-manager>
    </div>
@stop

@push('js')
    @vite(['resources/js/app.js'])
@endpush
