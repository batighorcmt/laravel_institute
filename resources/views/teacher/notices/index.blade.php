@extends('layouts.admin')

@section('title', 'নোটিশ')

@section('content_header')
    <h1>নোটিশ বোর্ড</h1>
@stop

@section('content')
    <div id="app">
        <notice-inbox :school-id="{{ $school->id }}"></notice-inbox>
    </div>
@stop

@push('js')
    @vite(['resources/js/app.js'])
@endpush
