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
                <h3 class="card-title">{{ $status == 'completed' ? 'সকল পরীক্ষা' : 'সক্রিয় পরীক্ষা' }}</h3>
                <div class="card-tools">
                    @if($status == 'active')
                        <a href="{{ route('principal.institute.results.exams', ['school' => $school->id, 'status' => 'completed']) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-list"></i> সকল পরীক্ষা
                        </a>
                    @else
                        <a href="{{ route('principal.institute.results.exams', $school) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-check-circle"></i> সক্রিয় পরীক্ষা
                        </a>
                    @endif
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
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
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
                                            <button class="btn btn-sm btn-primary" onclick="toggleDropdown(this, event)">Actions <i class="fas fa-caret-down"></i></button>
                                            <div class="custom-dropdown-menu">
                                                <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}"><i class="fas fa-pen text-primary"></i> মার্ক এন্ট্রি</a>
                                                <a href="{{ route('principal.institute.results.exams.result-sheet.print', [$school, $exam]) }}" target="_blank"><i class="fas fa-print text-success"></i> রেজাল্ট শীট</a>
                                                <a href="{{ route('principal.institute.results.marksheet', $school) }}?exam_id={{ $exam->id }}&class_id={{ $exam->class_id }}&academic_year_id={{ $exam->academic_year_id }}"><i class="fas fa-file-alt text-info"></i> মার্কশীট</a>
                                                <a href="{{ route('principal.institute.results.tabulation', $school) }}?exam_id={{ $exam->id }}&class_id={{ $exam->class_id }}&academic_year_id={{ $exam->academic_year_id }}"><i class="fas fa-table text-warning"></i> টেবুলেশন শীট</a>
                                                <a href="{{ route('principal.institute.results.statistics', $school) }}?exam_id={{ $exam->id }}&class_id={{ $exam->class_id }}"><i class="fas fa-chart-pie text-secondary"></i> সারাংশ</a>
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
                        <i class="fas fa-info-circle"></i> এখনো কোনো পরীক্ষা পাওয়া যায়নি।
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
        
        // Close others
        document.querySelectorAll('.custom-dropdown').forEach(d => {
            if (d !== dropdown) d.classList.remove('show');
        });

        // Calculate position to prevent cutoff
        const menu = dropdown.querySelector('.custom-dropdown-menu');
        const rect = button.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        
        // Show temporarily to get height
        dropdown.classList.add('show');
        const menuHeight = menu.offsetHeight;
        
        if (rect.bottom + menuHeight > windowHeight && rect.top > menuHeight) {
            // Dropup
            menu.style.top = 'auto';
            menu.style.bottom = '100%';
            menu.style.marginTop = '0';
            menu.style.marginBottom = '5px';
        } else {
            // Dropdown
            menu.style.top = '100%';
            menu.style.bottom = 'auto';
            menu.style.marginTop = '5px';
            menu.style.marginBottom = '0';
        }
    }

    // Close when clicking outside
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('show'));
        }
    });

    // Close when scrolling in table container if present
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.addEventListener('scroll', function() {
            document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('show'));
        });
    }
</script>
@endpush
@endsection
