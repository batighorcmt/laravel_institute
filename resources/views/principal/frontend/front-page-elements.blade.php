@extends('layouts.admin')

@section('title', 'FrontPage Element')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-gray-800">FrontPage Element</h1>
            <p class="text-muted small mb-0">ফ্রন্টপেজের মিশন, ভিশন, অর্জন, সুবিধা, গ্যালারি ও ব্লগ সেকশন পরিচালনা</p>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                <li class="breadcrumb-item active">FrontPage Element</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div id="app">
        <front-page-elements-manager
            :school-id="{{ $school->id }}"
            blog-posts-url="{{ route('principal.institute.frontend.posts.index', $school) }}"
            gallery-url="{{ route('principal.institute.frontend.gallery', $school) }}"
        ></front-page-elements-manager>

        <div class="mt-4">
            <h5 class="font-weight-bold text-muted mb-3">৮. এক নজরে প্রতিষ্ঠানের পরিসংখ্যান</h5>
            <school-stats-manager :school-id="{{ $school->id }}"></school-stats-manager>
        </div>
    </div>
@endsection
