@extends('layouts.admin')
@section('title','Documents: Prottayon')

@section('content')
<div id="app">
    <prottayon-generator :school-id="{{ $school->id }}" :initial-classes="{{ $classes->toJson() }}"></prottayon-generator>
</div>
@endsection

@push('scripts')
{{-- Resources handled by app.js --}}
@endpush
