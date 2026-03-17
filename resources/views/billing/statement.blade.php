@extends('layouts.admin')

@section('title', 'Student Monthly Statement')

@section('content_header')
    <h1>শিক্ষার্থীর মাসিক স্টেটমেন্ট</h1>
@endsection

@section('content')
<div id="app">
    <fee-statement
        :academic-year-id="{{ \App\Models\AcademicYear::where('is_current', true)->first()->id ?? 0 }}"
    ></fee-statement>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .content-wrapper { background-color: #f8fafc !important; }
    /* Make page full-width like fee collection */
    .content-wrapper > .content > .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .content-wrapper > .content { padding-left: 0 !important; padding-right: 0 !important; }
    #app, #app > div { max-width: 100% !important; width: 100% !important; }
    * { font-family: 'Hind Siliguri', sans-serif; }
    .content { padding: 0 !important; }
    .fee-collection-container { padding: 1.5rem 1rem !important; }
    .card { border-radius: 1rem; }
</style>
@endpush
