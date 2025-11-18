@extends('layouts.admin')

@section('title', 'স্কুল সমূহ')
@section('nav.superadmin.dashboard')
@endsection

@section('content')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">স্কুল সমূহ</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('superadmin.schools.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> নতুন স্কুল</a>
        </div>
    </div>

    @if(session('default_admin'))
        <div class="alert alert-info">
            <strong>ডিফল্ট এডমিন ইউজার তৈরি হয়েছে:</strong><br>
            ইমেইল: <code>{{ session('default_admin.email') }}</code><br>
            পাসওয়ার্ড: <code>{{ session('default_admin.password') }}</code>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="get" class="mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="form-inline mb-2 mb-sm-0">
                        <label class="mr-2">প্রতি পৃষ্ঠায় দেখাও:</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach($allowed as $size)
                                <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-inline" style="max-width:400px;">
                        <div class="input-group input-group-sm w-100">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="সার্চ (নাম/কোড/ফোন/ইমেইল)">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th style="width:80px">লোগো</th>
                            <th>নাম</th>
                            <th>কোড</th>
                            <th>ফোন</th>
                            <th>ইমেইল</th>
                            <th>পাসওয়ার্ড</th>
                            <th>স্ট্যাটাস</th>
                            <th class="text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            <tr>
                                <td>{{ $school->id }}</td>
                                <td>
                                    @if($school->logo)
                                        <img src="{{ Storage::url($school->logo) }}" alt="logo" width="64" height="64" class="rounded" style="object-fit:cover"> 
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('superadmin.schools.manage', $school) }}">{{ $school->name }}</a>
                                </td>
                                <td>{{ $school->code }}</td>
                                <td>{{ $school->phone }}</td>
                                <td>{{ $school->email }}</td>
                                <td>
                                    <form action="{{ route('superadmin.schools.reset-password', $school) }}" method="post" onsubmit="return confirm('এই স্কুলের প্রিন্সিপালের পাসওয়ার্ড রিসেট করবেন? নতুন পাসওয়ার্ডটি স্ক্রিনে দেখানো হবে।');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">রিসেট ও দেখাও</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $school->status === 'active' ? 'success' : 'secondary' }}">{{ $school->status }}</span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('superadmin.schools.show', $school) }}" class="btn btn-sm btn-secondary" title="বিস্তারিত"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('superadmin.schools.manage', $school) }}" class="btn btn-sm btn-info"><i class="fas fa-cogs"></i></a>
                                    <a href="{{ route('superadmin.schools.edit', $school) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('superadmin.schools.destroy', $school) }}" method="post" class="d-inline" onsubmit="return confirm('ডিলিট করতে চান?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">কোনো তথ্য নেই</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                <div class="text-muted small mb-2 mb-sm-0">
                    @if($schools->total() > 0)
                        মোট {{ $schools->total() }}টির মধ্যে {{ $schools->firstItem() }} থেকে {{ $schools->lastItem() }}টি দেখানো হচ্ছে
                    @else
                        কোনো রেকর্ড পাওয়া যায়নি
                    @endif
                </div>
                <div>
                    {{ $schools->appends(['q'=>request('q'),'per_page'=>$perPage])->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
