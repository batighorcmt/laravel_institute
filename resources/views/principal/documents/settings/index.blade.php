@extends('layouts.admin')
@section('title','Documents: Settings')

@section('content')
    <div id="app">
        <div class="row mb-4">
            <div class="col-md-12">
                <document-template-manager :school-id="{{ $school->id }}"></document-template-manager>
            </div>
        </div>

        <hr>

        <print-settings-manager 
            :school-id="{{ $school->id }}" 
            :pages="{{ json_encode($pages) }}" 
            :initial-settings="{{ json_encode($settings->toArray()) }}">
        </print-settings-manager>
    </div>
@stop

@push('scripts')
    {{-- Vue is handled by the main layout's app.js --}}
@endpush
