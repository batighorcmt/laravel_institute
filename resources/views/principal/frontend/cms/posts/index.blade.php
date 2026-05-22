@extends('layouts.admin')

@section('title', 'ব্লগ পোস্ট')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">ব্লগ পোস্ট</h1>
        <div>
            <a href="{{ route('principal.institute.frontend.pages.index', $school) }}" class="btn btn-secondary btn-sm mr-1">
                <i class="fas fa-file-alt"></i> পৃষ্ঠা
            </a>
            <a href="{{ route('principal.institute.frontend.posts.create', $school) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> নতুন পোস্ট
            </a>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>শিরোনাম</th>
                        <th>স্লাগ</th>
                        <th>স্ট্যাটাস</th>
                        <th>প্রকাশ</th>
                        <th class="text-right">কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td>{{ $post->title }}</td>
                            <td><code>/blog/{{ $post->slug }}</code></td>
                            <td>
                                @if($post->status === \App\Models\CmsPost::STATUS_PUBLISHED)
                                    <span class="badge badge-success">প্রকাশিত</span>
                                @else
                                    <span class="badge badge-secondary">খসড়া</span>
                                @endif
                            </td>
                            <td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-right text-nowrap">
                                @if($post->isPublished())
                                    <a href="{{ route('frontend.blog.show', $post->slug) }}" target="_blank" class="btn btn-xs btn-outline-info">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                                <a href="{{ route('principal.institute.frontend.posts.edit', [$school, $post]) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('principal.institute.frontend.posts.destroy', [$school, $post]) }}" method="post" class="d-inline"
                                      onsubmit="return confirm('এই পোস্ট মুছে ফেলতে চান?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">কোনো ব্লগ পোস্ট নেই।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($posts->hasPages())
            <div class="card-footer">{{ $posts->links() }}</div>
        @endif
    </div>
@stop
