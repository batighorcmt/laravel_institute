@extends('layouts.admin')

@section('title', 'মাসিক বকেয়া আদায় তালিকা')

@section('content')
<div id="app">
    <detailed-due-report></detailed-due-report>
</div>
@endsection

@push('styles')
<style>
    /* Remove padding for modern look inside layouts */
    .content-wrapper {
        background-color: #f8fafc !important; /* Tailwind slate-50 */
    }
    .content {
        padding: 0 !important;
    }
</style>
@endpush
