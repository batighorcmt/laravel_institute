@extends('layouts.admin')

@section('title', 'Menus')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">Menus</h1>
            <p class="text-muted small mb-0">ওয়ার্ডপ্রেস স্টাইলে হেডার ও ফুটার মেনু সাজান</p>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">Menus</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <frontend-menu-manager :school-id="{{ $school->id }}"></frontend-menu-manager>
    </div>
@endsection
