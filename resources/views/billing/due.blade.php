@extends('layouts.admin')

@section('title', 'শিক্ষার্থী বকেয়া প্রিভিউ (Student Due Preview)')

@section('content')
<div id="app">
    <fee-due-preview></fee-due-preview>
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
