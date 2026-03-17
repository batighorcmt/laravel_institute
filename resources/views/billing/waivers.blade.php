@extends('layouts.admin')

@section('title', 'Fee Waivers')

@section('content_header')
    <h1>Fee Waivers</h1>
@endsection

@section('content')
<div id="app" class="p-6">
    <div class="max-w-7xl mx-auto">
        <fee-waiver-manager></fee-waiver-manager>
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .content-wrapper { background-color: #f8fafc !important; }
    * { font-family: 'Hind Siliguri', sans-serif; }
</style>
@endpush
