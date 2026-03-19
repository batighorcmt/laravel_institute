@extends('layouts.admin')

@section('title', 'Detailed Due Report')

@section('content_header')
    <h1>মাসিক বকেয়া আদায় রিপোর্ট</h1>
@endsection

@section('content')
<div id="app">
    <detailed-due-report></detailed-due-report>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .content-wrapper { background-color: #f8fafc !important; }
    * { font-family: 'Hind Siliguri', sans-serif; }
    @media print {
        .main-header, .main-sidebar, .content-header, .main-footer { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        .content { padding: 0 !important; }
    }
</style>
@endpush
