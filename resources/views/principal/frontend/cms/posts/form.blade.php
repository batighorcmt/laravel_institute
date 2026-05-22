@extends('layouts.admin')

@section('title', $isEdit ? 'পোস্ট সম্পাদনা' : 'নতুন ব্লগ পোস্ট')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">{{ $isEdit ? 'পোস্ট সম্পাদনা' : 'নতুন ব্লগ পোস্ট' }}</h1>
        <a href="{{ route('principal.institute.frontend.posts.index', $school) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> তালিকা
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post"
          action="{{ $isEdit ? route('principal.institute.frontend.posts.update', [$school, $post]) : route('principal.institute.frontend.posts.store', $school) }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>শিরোনাম *</label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title', $post->title) }}">
                        </div>
                        <div class="form-group">
                            <label>URL স্লাগ</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">/blog/</span></div>
                                <input type="text" name="slug" class="form-control" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                       value="{{ old('slug', $post->slug) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>সংক্ষিপ্ত বিবরণ (excerpt)</label>
                            <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>বিষয়বস্তু</label>
                            <textarea id="cms_content_editor" name="content" class="form-control">{{ old('content', $post->content) }}</textarea>
                        </div>
                    </div>
                </div>
                @php $item = $post; @endphp
                @include('principal.frontend.cms._seo-fields')
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">প্রকাশনা</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>স্ট্যাটাস *</label>
                            <select name="status" class="form-control" required>
                                <option value="draft" {{ old('status', $post->status) === 'draft' ? 'selected' : '' }}>খসড়া</option>
                                <option value="published" {{ old('status', $post->status) === 'published' ? 'selected' : '' }}>প্রকাশিত</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>প্রকাশের তারিখ</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="form-group">
                            <label>ফিচার ছবি</label>
                            <input type="file" name="featured_image" class="form-control-file" accept="image/*">
                            @if($post->featured_image)
                                <img src="{{ asset('storage/'.$post->featured_image) }}" alt="" class="img-thumbnail mt-2 w-100">
                            @endif
                        </div>
                        @if($isEdit && $post->isPublished())
                            <a href="{{ route('frontend.blog.show', $post->slug) }}" target="_blank" class="btn btn-outline-info btn-block btn-sm">
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
