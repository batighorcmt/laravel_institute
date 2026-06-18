@extends('layouts.admin')

@section('title', 'পদবীসমূহ (Designations)')
@section('nav.superadmin.designations', 'active')

@section('content')
<div class="row">
    <div class="col-12">
        <div id="app">
            <designation-manager 
                api-url="{{ route('superadmin.designations.index') }}"
                csrf-token="{{ csrf_token() }}"
            ></designation-manager>
        </div>
    </div>
</div>
@endsection
