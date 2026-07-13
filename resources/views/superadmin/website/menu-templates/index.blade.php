@extends('layouts.admin')

@section('title', 'Website Menu Templates')
@section('nav.superadmin.website.menu-templates', 'active')

@section('content')
<div class="row">
    <div class="col-12">
        <div id="app">
            <website-menu-template-manager
                api-url="{{ route('superadmin.website.menu-templates.index') }}"
                csrf-token="{{ csrf_token() }}"
            ></website-menu-template-manager>
        </div>
    </div>
</div>
@endsection
