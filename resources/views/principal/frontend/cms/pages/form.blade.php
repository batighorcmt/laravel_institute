@extends('layouts.admin')

@section('title', $isEdit ? 'পৃষ্ঠা সম্পাদনা' : 'নতুন পৃষ্ঠা')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">{{ $isEdit ? 'পৃষ্ঠা সম্পাদনা' : 'নতুন পৃষ্ঠা' }}</h1>
        <a href="{{ route('principal.institute.frontend.pages.index', $school) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> তালিকা
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post"
          action="{{ $isEdit ? route('principal.institute.frontend.pages.update', [$school, $page]) : route('principal.institute.frontend.pages.store', $school) }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>শিরোনাম *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="{{ old('title', $page->title) }}">
                        </div>
                        <div class="form-group">
                            <label>URL স্লাগ</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">/</span></div>
                                <input type="text" name="slug" class="form-control" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                       value="{{ old('slug', $page->slug) }}"
                                       placeholder="স্বয়ংক্রিয় (শিরোনাম থেকে)">
                            </div>
                            <small class="text-muted">ইংরেজি ছোট হাতের অক্ষর, সংখ্যা ও হাইফেন। যেমন: about-us</small>
                        </div>
                        <div class="form-group">
                            <label>কনটেন্ট মোড</label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-4">
                                    <input id="mode_static" type="radio" name="content_mode" value="static" class="custom-control-input"
                                           {{ old('content_mode', $page->content_mode ?? 'static') === 'static' ? 'checked' : '' }}
                                           onchange="document.getElementById('static_content_wrap').style.display='block';document.getElementById('dynamic_source_wrap').style.display='none';">
                                    <label class="custom-control-label" for="mode_static">স্ট্যাটিক (এডিটরে টাইপ করা)</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input id="mode_dynamic" type="radio" name="content_mode" value="dynamic" class="custom-control-input"
                                           {{ old('content_mode', $page->content_mode ?? 'static') === 'dynamic' ? 'checked' : '' }}
                                           onchange="document.getElementById('static_content_wrap').style.display='none';document.getElementById('dynamic_source_wrap').style.display='block';">
                                    <label class="custom-control-label" for="mode_dynamic">ডাইনামিক (সফটওয়্যার থেকে)</label>
                                </div>
                            </div>
                        </div>
                        <div id="dynamic_source_wrap" class="form-group" style="display: {{ old('content_mode', $page->content_mode ?? 'static') === 'dynamic' ? 'block' : 'none' }};">
                            <label>ডাটা সোর্স</label>
                            <select name="data_source" class="form-control">
                                <option value="">— নির্বাচন করুন —</option>
                                @foreach(['teachers' => 'শিক্ষকমণ্ডলী', 'notices' => 'নোটিশ বোর্ড', 'gallery' => 'গ্যালারি', 'about' => 'পরিচিতি', 'contact' => 'যোগাযোগ', 'committee' => 'কমিটি তালিকা'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('data_source', $page->data_source) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="static_content_wrap" class="form-group" style="display: {{ old('content_mode', $page->content_mode ?? 'static') === 'dynamic' ? 'none' : 'block' }};">
                            <label>বিষয়বস্তু</label>
                            <textarea id="cms_content_editor" name="content" class="form-control">{{ old('content', $page->content) }}</textarea>
                        </div>
                    </div>
                </div>
                @php $item = $page; @endphp
                @include('principal.frontend.cms._seo-fields')
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">প্রকাশনা</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>স্ট্যাটাস *</label>
                            <select name="status" class="form-control" required>
                                <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>খসড়া</option>
                                <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>প্রকাশিত</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>প্রকাশের তারিখ</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="form-group">
                            <label>ক্রম (sort)</label>
                            <input type="number" name="sort_order" class="form-control" min="0"
                                   value="{{ old('sort_order', $page->sort_order ?? 0) }}">
                        </div>
                        @if($isEdit && $page->isPublished())
                            <a href="{{ url('/'.$page->slug) }}" target="_blank" class="btn btn-outline-info btn-block btn-sm">
                                <i class="fas fa-external-link-alt"></i> লাইভ দেখুন
                            </a>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-save"></i> সংরক্ষণ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
    </form>
@stop

@include('principal.frontend.cms._editor-scripts')
