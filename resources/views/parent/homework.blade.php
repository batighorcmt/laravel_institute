@extends('layouts.admin')
@section('title', 'হোমওয়ার্ক লিস্ট')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-book-reader mr-2"></i> হোমওয়ার্ক লিস্ট</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="card">
        <div class="card-header bg-success">
            <h3 class="card-title">হোমওয়ার্ক লিস্ট</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>বিষয়</th>
                        <th>শ্রেণী/সেকশন</th>
                        <th>বিস্তারিত</th>
                        <th>শিক্ষক</th>
                        <th>সংযুক্তি</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($homeworkList as $hw)
                    <tr>
                        <td>{{ $hw->date->format('d M, Y') }}</td>
                        <td>{{ $hw->subject->name }}</td>
                        <td>{{ $hw->class->name }} ({{ $hw->section->name ?? 'All' }})</td>
                        <td>{{ $hw->description }}</td>
                        <td>{{ $hw->teacher->name }}</td>
                        <td>
                            @if($hw->file)
                            <a href="{{ asset('storage/'.$hw->file) }}" target="_blank" class="btn btn-xs btn-info">সংযুক্তি দেখুন</a>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">কোনো হোমওয়ার্ক পাওয়া যায়নি।</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-3">
                {{ $homeworkList->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
