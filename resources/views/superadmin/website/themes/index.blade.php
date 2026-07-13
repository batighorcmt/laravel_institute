@extends('layouts.admin')

@section('title', 'Website Themes')
@section('nav.superadmin.website.themes', 'active')

@section('content')
<div class="row">
    <div class="col-12">
        <div id="app">
            <website-theme-manager
                api-url="{{ route('superadmin.website.themes.index') }}"
                upload-url="{{ route('superadmin.website.themes.upload-preview') }}"
                csrf-token="{{ csrf_token() }}"
            ></website-theme-manager>
        </div>
    </div>
</div>
@endsection
