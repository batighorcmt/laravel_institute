@extends('layouts.admin')

@section('title', 'Website Settings')

@section('nav.principal.frontend.settings', 'active')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">Website Settings</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Website Settings</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <frontend-settings :school-id="{{ $school->id }}" :school-code="'{{ $school->code }}'" :school-domain="'{{ $school->domain ?? '' }}'"></frontend-settings>
    </div>
@endsection
