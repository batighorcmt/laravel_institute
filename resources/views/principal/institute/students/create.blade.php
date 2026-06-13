@extends('layouts.admin')
@section('title', 'নতুন শিক্ষার্থী ভর্তি')

@section('content')
    @php
        $validationErrors = $errors->all();
        $oldInput = old();
    @endphp

    <div id="app">
        <student-create-form
            :school-id="{{ $school->id }}"
            store-url="{{ route('principal.institute.students.store', $school) }}"
            back-url="{{ route('principal.institute.students.index', $school) }}"
            meta-sections-url="{{ route('principal.institute.meta.sections', $school) }}"
            meta-groups-url="{{ route('principal.institute.meta.groups', $school) }}"
            meta-next-roll-url="{{ route('principal.institute.meta.next-roll', $school) }}"
            :initial-years="{{ Js::from($years) }}"
            :initial-classes="{{ Js::from(\App\Models\SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get()) }}"
            :current-year-id="{{ $currentYear ? $currentYear->id : 'null' }}"
            csrf-token="{{ csrf_token() }}"
            :old-input="{{ Js::from($oldInput) }}"
            :validation-errors="{{ Js::from($validationErrors) }}"
            default-photo-url="{{ asset('images/default-avatar.png') }}"
        ></student-create-form>
    </div>
@endsection