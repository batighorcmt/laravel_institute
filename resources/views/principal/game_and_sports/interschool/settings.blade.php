@extends('layouts.admin')

@section('title', 'আন্তঃস্কুল প্রতিযোগিতা সেটিংস')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
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
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div id="app">
                <interschool-settings :school-id="{{ $school->id }}"></interschool-settings>
            </div>
        </div>
    </div>
</div>
@endsection
