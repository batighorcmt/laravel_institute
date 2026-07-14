@extends('layouts.admin')

@section('title', 'Contact Settings')

@section('nav.principal.frontend.contact-settings', 'active')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">যোগাযোগ সেটিংস</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">যোগাযোগ সেটিংস</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <contact-settings-manager :school-id="{{ $school->id }}"></contact-settings-manager>
    </div>
@endsection
