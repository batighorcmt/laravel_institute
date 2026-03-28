@extends('layouts.admin')

@section('title', 'Public Exams')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">পাবলিক পরীক্ষাসমূহ</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="{{ route('principal.institute.public_exams.create', $school) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> নতুন যুক্ত করুন
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">পাবলিক পরীক্ষার তালিকা</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>শর্ট নাম (Short Name)</th>
                            <th>পূর্ণ নাম (Full Name)</th>
                            <th>স্ট্যাটাস</th>
                            <th class="text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($publicExams as $exam)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $exam->short_name }}</td>
                            <td>{{ $exam->full_name }}</td>
                            <td>
                                @if($exam->status == 'active')
                                    <span class="badge badge-success">সক্রিয়</span>
                                @else
                                    <span class="badge badge-danger">নিষ্ক্রিয়</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('principal.institute.public_exams.edit', [$school, $exam]) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('principal.institute.public_exams.destroy', [$school, $exam]) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('আপনি কি নিশ্চিত?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">কোনো ডাটা পাওয়া যায়নি।</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
