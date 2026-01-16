@extends('layouts.admin')

@section('title', 'পরীক্ষা তালিকা')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">পরীক্ষা তালিকা</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item active">পরীক্ষা তালিকা</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সকল পরীক্ষা</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.exams.create', $school) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> নতুন পরীক্ষা তৈরি করুন
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

                @if($exams->count() > 0)
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">ক্রমিক</th>
                                <th>পরীক্ষার নাম</th>
                                <th>শ্রেণি</th>
                                <th>শিক্ষাবর্ষ</th>
                                <th>অবস্থা</th>
                                <th>তারিখ</th>
                                <th width="15%">কার্যক্রম</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exams as $exam)
                                <tr>
                                    <td>{{ $loop->iteration + ($exams->currentPage() - 1) * $exams->perPage() }}</td>
                                    <td>
                                        <strong>{{ $exam->name }}</strong>
                                        @if($exam->name_bn)
                                            <br><small class="text-muted">{{ $exam->name_bn }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $exam->class->name ?? 'N/A' }}</td>
                                    <td>{{ $exam->academicYear->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($exam->status == 'active')
                                            <span class="badge badge-success">সক্রিয়</span>
                                        @elseif($exam->status == 'completed')
                                            <span class="badge badge-info">সম্পন্ন</span>
                                        @elseif($exam->status == 'cancelled')
                                            <span class="badge badge-danger">বাতিল</span>
                                        @else
                                            <span class="badge badge-secondary">খসড়া</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($exam->start_date)
                                            {{ $exam->start_date->format('d/m/Y') }}
                                            @if($exam->end_date)
                                                - {{ $exam->end_date->format('d/m/Y') }}
                                            @endif
                                        @else
                                            <span class="text-muted">তারিখ নির্ধারিত হয়নি</span>
                                        @endif
                                    </td>
                                    <td>
                                        <style>
                                            .custom-dropdown {
                                                position: relative;
                                                display: inline-block;
                                            }
                                            .custom-dropdown-menu {
                                                display: none;
                                                position: absolute;
                                                z-index: 1000;
                                                min-width: 200px;
                                                background-color: white;
                                                border: 1px solid #ddd;
                                                box-shadow: 0 2px 5px rgba(0,0,0,0.15);
                                                right: 0;
                                                margin-top: 5px;
                                            }
                                            .custom-dropdown-menu a {
                                                display: block;
                                                padding: 8px 12px;
                                                text-decoration: none;
                                                color: #333;
                                                font-size: 14px;
                                            }
                                            .custom-dropdown-menu a:hover {
                                                background-color: #f1f1f1;
                                            }
                                            .custom-dropdown.show .custom-dropdown-menu {
                                                display: block;
                                            }
                                        </style>
                                        <div class="custom-dropdown">
                                            <button class="btn btn-sm btn-primary" onclick="toggleDropdown(this, event)">Actions</button>
                                            <div class="custom-dropdown-menu">
                                                <a href="{{ route('principal.institute.exams.show', [$school, $exam]) }}"><i class="fas fa-eye"></i> Details</a>
                                                <a href="{{ route('principal.institute.exams.bulk-update', [$school, $exam]) }}"><i class="fas fa-tasks"></i> Bulk Update</a>
                                                <a href="{{ route('principal.institute.exams.edit', [$school, $exam]) }}"><i class="fas fa-pencil-alt"></i> Edit</a>
                                                <a href="{{ route('principal.institute.seat-plans.index', $school) }}?exam_id={{ $exam->id }}"><i class="fas fa-chair"></i> Seat Plan</a>
                                                <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}"><i class="fas fa-pen"></i> Mark Entry</a>
                                                <a href="{{ route('principal.institute.results.marksheet', $school) }}?exam_id={{ $exam->id }}"><i class="fas fa-file-alt"></i> Results</a>
                                                <div style="border-top: 1px solid #ddd; margin: 5px 0;"></div>
                                                <form action="{{ route('principal.institute.exams.destroy', [$school, $exam]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে আপনি এই পরীক্ষা মুছে ফেলতে চান?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="custom-dropdown-menu" style="width: 100%; text-align: left; border: none; background: none; padding: 8px 12px; color: #dc3545; cursor: pointer;">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $exams->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এখনো কোনো পরীক্ষা তৈরি করা হয়নি।
                        <a href="{{ route('principal.institute.exams.create', $school) }}" class="alert-link">নতুন পরীক্ষা তৈরি করুন</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function toggleDropdown(button, event) {
        event.stopPropagation();
        const dropdown = button.parentElement;
        dropdown.classList.toggle('show');
        
        // Close others
        document.querySelectorAll('.custom-dropdown').forEach(d => {
            if (d !== dropdown) d.classList.remove('show');
        });
    }

    // Close when clicking outside
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
</script>
@endpush
@endsection
