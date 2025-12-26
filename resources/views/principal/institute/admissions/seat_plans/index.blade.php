@extends('layouts.admin')
@section('title','সীট প্ল্যান')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ভর্তি পরীক্ষা সীট প্ল্যান</h4>
    <a class="btn btn-primary" href="{{ route('principal.institute.admissions.seat-plans.create',$school) }}">নতুন প্ল্যান</a>
</div>
<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead><tr><th>#</th><th>নাম</th><th>পরীক্ষা(শ্রেণি)</th><th>শিফট</th><th>রুম</th><th>স্ট্যাটাস</th><th>ক্রিয়া</th></tr></thead>
            <tbody>
            @forelse($plans as $p)
                @if($p instanceof \App\Models\AdmissionExamSeatPlan)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->name }}</td>
                    <td>
                        @php($examList = ($p->relationLoaded('exams') ? $p->exams : collect()))
                        @if($examList && $examList->count())
                            {{ $examList->map(fn($e)=>($e->name.' ('.$e->class_name.')'))->implode(', ') }}
                        @else
                            {{ $p->exam?->name }} @if($p->exam?->class_name) ({{ $p->exam?->class_name }}) @endif
                        @endif
                    </td>
                    <td>{{ $p->shift }}</td>
                    <td>{{ $p->rooms()->count() }}</td>
                    <td>{{ $p->status }}</td>
                    <td class="text-right d-flex justify-content-end" style="gap:4px;">
                        <a href="{{ route('principal.institute.admissions.seat-plans.rooms',[$school,$p]) }}" class="btn btn-sm btn-outline-secondary">Rooms</a>
                        <a href="{{ route('principal.institute.admissions.seat-plans.edit',[$school,$p]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('principal.institute.admissions.seat-plans.destroy',[$school,$p]) }}" onsubmit="return confirm('এই প্ল্যান মুছে ফেললে সংশ্লিষ্ট সকল রুম ও বরাদ্দ মুছে যাবে. নিশ্চিত?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-center py-4">কোন সীট প্ল্যান নেই</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">@if($plans instanceof \Illuminate\Contracts\Pagination\Paginator || $plans instanceof \Illuminate\Pagination\LengthAwarePaginator) {{ $plans->links() }} @endif</div>
</div>
@endsection