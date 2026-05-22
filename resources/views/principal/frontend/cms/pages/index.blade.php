@extends('layouts.admin')

@section('title', 'ওয়েবসাইট পৃষ্ঠা')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">ওয়েবসাইট পৃষ্ঠা</h1>
        <div>
            <a href="{{ route('principal.institute.frontend.settings', $school) }}" class="btn btn-secondary btn-sm mr-1">
                <i class="fas fa-cog"></i> সেটিংস
            </a>
            <a href="{{ route('principal.institute.frontend.pages.create', $school) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> নতুন পৃষ্ঠা
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
                        <th>ক্রম</th>
                        <th>আপডেট</th>
                        <th class="text-right">কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>{{ $page->title }}</td>
                            <td><code>/{{ $page->slug }}</code></td>
                            <td>
                                @if($page->status === \App\Models\CmsPage::STATUS_PUBLISHED)
                                    <span class="badge badge-success">প্রকাশিত</span>
                                @else
                                    <span class="badge badge-secondary">খসড়া</span>
                                @endif
                            </td>
                            <td>{{ $page->sort_order }}</td>
                            <td>{{ $page->updated_at?->format('d M Y') }}</td>
                            <td class="text-right text-nowrap">
                                @if($page->isPublished())
                                    <a href="{{ url('/'.$page->slug) }}" target="_blank" class="btn btn-xs btn-outline-info" title="দেখুন">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                                <a href="{{ route('principal.institute.frontend.pages.edit', [$school, $page]) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('principal.institute.frontend.pages.destroy', [$school, $page]) }}" method="post" class="d-inline"
                                      onsubmit="return confirm('এই পৃষ্ঠা মুছে ফেলতে চান?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">কোনো পৃষ্ঠা নেই। WordPress-এর মতো কাস্টম পৃষ্ঠা তৈরি করুন।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pages->hasPages())
            <div class="card-footer">{{ $pages->links() }}</div>
        @endif
    </div>

    <p class="text-muted small mt-2">
        <i class="fas fa-info-circle"></i>
        প্রকাশিত পৃষ্ঠা স্কুল ডোমেইনে <code>yourschool.com/পৃষ্ঠা-স্লাগ</code> ঠিকানায় দেখা যাবে।
        <a href="{{ route('principal.institute.frontend.posts.index', $school) }}">ব্লগ পোস্ট</a> আলাদা <code>/blog</code> এ।
    </p>
@stop
