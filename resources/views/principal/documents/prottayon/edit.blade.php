@extends('layouts.admin')
@section('title','প্রত্যয়নপত্র সংশোধন')

@section('content')
<div id="app">
    <prottayon-generator 
        :school-id="{{ $school->id }}"
        :school-name-bn="'{{ $school->name_bn }}'"
        :school-name-en="'{{ $school->name_en }}'"
        :initial-classes="{{ $classes->toJson() }}"
        :initial-document="{{ $document->toJson() }}"
    ></prottayon-generator>
</div>
@endsection

@push('scripts')
<script>
    // Ensure Vue is initialized if needed, though usually handled by app.js
</script>
@endpush
