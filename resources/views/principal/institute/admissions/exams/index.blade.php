@extends('layouts.admin')
@section('title','ভর্তি পরীক্ষা তালিকা')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ভর্তি পরীক্ষা</h4>
    <a class="btn btn-primary" href="{{ route('principal.institute.admissions.exams.create',$school) }}">নতুন পরীক্ষা</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>#</th><th>শ্রেণি</th><th>নাম</th><th>ধরন</th><th>তারিখ</th><th>স্ট্যাটাস</th><th>সাবজেক্ট</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($exams as $ex)
                <tr>
                    <td>{{ $ex->id }}</td>
                    <td>{{ $ex->class_name }}</td>
                    <td>{{ $ex->name }}</td>
                    <td>{{ $ex->type==='subject'?'প্রতি বিষয়':'সামগ্রীক' }}</td>
                    <td>{{ optional($ex->exam_date)->format('d-m-Y') }}</td>
                    <td>{{ $ex->status }}</td>
                    <td>{{ $ex->subjects()->count() }}</td>
                    <td class="text-right">
                        <a href="{{ route('principal.institute.admissions.exams.edit',[$school,$ex]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <a href="{{ route('principal.institute.admissions.exams.marks',[$school,$ex]) }}" class="btn btn-sm btn-outline-primary">নম্বর উত্তোলন</a>
                        <a href="{{ route('principal.institute.admissions.exams.results',[$school,$ex]) }}" class="btn btn-sm btn-outline-info">ফলাফল</a>
                        <form method="POST" action="{{ route('principal.institute.admissions.exams.destroy',[$school,$ex]) }}" class="d-inline" onsubmit="return confirm('মুছবেন?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4">কোন পরীক্ষা নেই</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        @if($exams instanceof \Illuminate\Contracts\Pagination\Paginator || $exams instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $exams->links() }}
        @endif
    </div>
</div>
@endsection