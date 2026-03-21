@extends('layouts.admin')

@section('title', 'Fee Collection')

@section('content_header')
    <h1>ফি কালেকশন</h1>
@endsection

@section('content')
<div id="app">
    <fee-collection 
        :academic-year-id="{{ \App\Models\AcademicYear::where('is_current', true)->first()->id ?? 0 }}"
        role="{{ auth()->user()->isPrincipal($school?->id) ? 'principal' : (auth()->user()->isTeacher($school?->id) ? 'teacher' : 'principal') }}"
        :initial-classes="{{ isset($classes) ? $classes->toJson() : '[]' }}"
        :initial-sections="{{ isset($sections) ? $sections->toJson() : (isset($sectionsByClass) ? $sectionsByClass->flatten()->toJson() : '[]') }}"
    ></fee-collection>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .content-wrapper { background-color: #f8fafc !important; }
    * { font-family: 'Hind Siliguri', sans-serif; }
    /* Full-width layout — remove default container constraints */
    .content-wrapper > .content > .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .content-wrapper > .content {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    #app, #app > div {
        max-width: 100% !important;
        width: 100% !important;
    }
    /* Inner padding handled by the component's px-4 */
    .fee-collection-container {
        padding: 1.5rem 1rem !important;
    }
</style>
@endpush
