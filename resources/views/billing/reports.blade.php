@extends('layouts.admin')

@section('title', 'Fee Reports')

@section('content_header')
    <h1>ফি রিপোর্টস</h1>
@endsection

@section('content')
<div id="app">
    <fee-reports></fee-reports>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .content-wrapper { background-color: #f8fafc !important; }
    * { font-family: 'Hind Siliguri', sans-serif; }
</style>
@endpush
