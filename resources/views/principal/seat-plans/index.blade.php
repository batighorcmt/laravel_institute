@extends('layouts.admin')

@section('title', 'Seat Plan Management')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Seat Plan Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Seat Plans</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সকল সিট প্ল্যান</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.seat-plans.create', $school) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> নতুন সিট প্ল্যান তৈরি করুন
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($seatPlans->count() > 0)
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">ক্রমিক</th>
                                <th>সিট প্ল্যানের নাম</th>
                                <th>শিফট</th>
                                <th>রুম সংখ্যা</th>
                                <th>অবস্থা</th>
                                <th width="20%">কার্যক্রম</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seatPlans as $seatPlan)
                                <tr>
                                    <td>{{ $loop->iteration + ($seatPlans->currentPage() - 1) * $seatPlans->perPage() }}</td>
                                    <td><strong>{{ $seatPlan->name }}</strong></td>
                                    <td>
                                        @if($seatPlan->shift === 'Morning')
                                            Morning (সকাল)
                                        @elseif($seatPlan->shift === 'Afternoon')
                                            Afternoon (বিকাল)
                                        @else
                                            {{ $seatPlan->shift ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>{{ $seatPlan->rooms->count() }}</td>
                                    <td>
                                        @if($seatPlan->status == 'active')
                                            <span class="badge badge-success">Active</span>
                                        @elseif($seatPlan->status == 'completed')
                                            <span class="badge badge-info">Completed</span>
                                        @else
                                            <span class="badge badge-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('principal.institute.seat-plans.edit', [$school, $seatPlan]) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('principal.institute.seat-plans.rooms', [$school, $seatPlan]) }}" class="btn btn-sm btn-secondary" title="Room Management">
                                            <i class="fas fa-door-open"></i>
                                        </a>
                                        <a href="{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}" class="btn btn-sm btn-primary" title="Allocate Seats">
                                            <i class="fas fa-chair"></i>
                                        </a>
                                        <a href="{{ route('principal.institute.seat-plans.print-all', [$school, $seatPlan]) }}" class="btn btn-sm btn-success" target="_blank" title="Print All">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <form action="{{ route('principal.institute.seat-plans.destroy', [$school, $seatPlan]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="মুছে ফেলুন">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $seatPlans->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এখনো কোনো সিট প্ল্যান তৈরি করা হয়নি।
                        <a href="{{ route('principal.institute.seat-plans.create', $school) }}" class="alert-link">নতুন সিট প্ল্যান তৈরি করুন</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
