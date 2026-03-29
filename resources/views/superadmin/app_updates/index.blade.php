@extends('layouts.admin')

@section('title', 'অ্যাপ আপডেট তালিকা')

@section('content')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">অ্যাপ আপডেট সমূহ</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('superadmin.app-updates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> নতুন আপডেট রিলিজ করুন
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>ভার্সন নাম</th>
                            <th>ভার্সন কোড</th>
                            <th>ম্যান্ডেটরি</th>
                            <th>স্ট্যাটাস</th>
                            <th>রিলিজ তারিখ</th>
                            <th class="text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($updates as $update)
                            <tr>
                                <td>{{ $update->id }}</td>
                                <td>{{ $update->version_name }}</td>
                                <td><code>{{ $update->version_code }}</code></td>
                                <td>
                                    <span class="badge badge-{{ $update->is_mandatory ? 'danger' : 'secondary' }}">
                                        {{ $update->is_mandatory ? 'হ্যাঁ' : 'না' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $update->is_active ? 'success' : 'warning' }}">
                                        {{ $update->is_active ? 'অ্যাক্টিভ' : 'ইন-অ্যাক্টিভ' }}
                                    </span>
                                </td>
                                <td>{{ $update->created_at->format('d M, Y h:i A') }}</td>
                                <td class="text-right">
                                    <a href="{{ $update->apk_url }}" target="_blank" class="btn btn-sm btn-info" title="APK ডাউনলোড"><i class="fas fa-download"></i></a>
                                    <a href="{{ route('superadmin.app-updates.edit', $update) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('superadmin.app-updates.destroy', $update) }}" method="post" class="d-inline" onsubmit="return confirm('এই আপডেটটি ডিলিট করতে চান?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">কোনো আপডেট রিলিজ করা হয়নি</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $updates->links() }}
            </div>
        </div>
    </div>
@endsection
