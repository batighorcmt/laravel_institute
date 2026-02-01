@extends('layouts.admin')

@section('title', 'Lesson Evaluation Reports')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Lesson Evaluation Reports</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <div class="form-group mr-2">
                    <label class="mr-2">Date</label>
                    <select name="date" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach($dates as $d)
                            <option value="{{ $d }}" {{ (isset($filterDate) && $filterDate == $d) ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <label class="mr-2">Per page</label>
                    <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([10,25,50,100] as $n)
                            <option value="{{ $n }}" {{ (isset($perPage) && $perPage == $n) ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <a href="{{ route('principal.institute.lesson-evaluations.index', $school) }}" class="btn btn-sm btn-link">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Stats</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $ev)
                            <tr>
                                <td>{{ optional($ev->evaluation_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($ev->teacher)->full_name ?? optional($ev->teacher->user)->name ?? '-' }}</td>
                                <td>{{ optional($ev->class)->name ?? '-' }}</td>
                                <td>{{ optional($ev->section)->name ?? '-' }}</td>
                                <td>{{ optional($ev->subject)->name ?? '-' }}</td>
                                <td>
                                    @php($s = $ev->getCompletionStats())
                                    <small>মোট: {{ $s['total'] }} • পড়া হয়েছে: {{ $s['completed'] }} • আংশিক: {{ $s['partial'] }} • নট ডান: {{ $s['not_done'] }} • অনুপস্থিত: {{ $s['absent'] }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('principal.institute.lesson-evaluations.show', [$school, $ev]) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No evaluations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing {{ $evaluations->count() }} of {{ $evaluations->total() }} records
                </div>
                <div>
                    {{ $evaluations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
