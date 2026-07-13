@extends('layouts.admin')

@section('title', 'Website Page Templates')
@section('nav.superadmin.website.page-templates', 'active')

@section('content')
<div class="row">
    <div class="col-12">
        <div id="app">
            <website-page-template-manager
                api-url="{{ route('superadmin.website.page-templates.index') }}"
                csrf-token="{{ csrf_token() }}"
            ></website-page-template-manager>
        </div>
    </div>
</div>
@endsection
