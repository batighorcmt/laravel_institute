@extends('layouts.admin')

@section('title', 'আন্তঃস্কুল প্রতিযোগিতা সেটিংস')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">আন্তঃস্কুল প্রতিযোগিতা সেটিংস</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('principal.institute.game-and-sports.interschool.index', $school->id) }}">আন্তঃস্কুল প্রতিযোগিতা</a></li>
            <li class="breadcrumb-item active">সেটিংস</li>
        </ol>
    </div>
</div>
@endsection

@section('content')
<div id="app">
    <interschool-settings :school-id="{{ $school->id }}"></interschool-settings>
</div>
@endsection

@push('styles')
<link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
<style>
    body, .content-wrapper, .content, .container-fluid, h1, h2, h3, h4, h5, h6, p, span, a, div, label, input, select, button, table {
        font-family: 'Nikosh', sans-serif !important;
    }
</style>
@endpush
