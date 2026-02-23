@extends('layouts.admin')
@section('title', 'ছুটির আবেদন')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-envelope-open-text mr-2"></i> ছুটির আবেদন</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">নতুন আবেদন করুন</h3>
                </div>
                <form action="{{ route('parent.leaves.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                    <div class="card-body">
                        <div class="form-group">
                            <label>আবেদনের ধরণ</label>
                            <select name="type" class="form-control">
                                <option value="Sick Leave">অসুস্থতা জনিত ছুটি</option>
                                <option value="Casual Leave">নৈমিত্তিক ছুটি</option>
                                <option value="Other">অন্যান্য</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>শুরুর তারিখ</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>শেষের তারিখ</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>আবেদনের কারণ</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">আবেদন জমা দিন</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">আবেদনের তালিকা</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ধরণ</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $leave)
                            <tr>
                                <td>{{ $leave->type }}</td>
                                <td>{{ $leave->start_date }} - {{ $leave->end_date }}</td>
                                <td>
                                    <span class="badge badge-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center">কোনো আবেদন নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
