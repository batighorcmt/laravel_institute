@extends('layouts.admin')

@section('title', 'Seat Plan Details - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Seat Plan Details</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">Seat Plans</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Seat Plan Info -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">সিট প্ল্যান তথ্য</h3>
                        <div class="card-tools">
                            <a href="{{ route('principal.institute.seat-plans.edit', [$school, $seatPlan]) }}" class="btn btn-tool btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">নাম:</th>
                                <td>{{ $seatPlan->name }}</td>
                            </tr>
                            <tr>
                                <th>শিফট:</th>
                                <td>
                                    @if($seatPlan->shift === 'Morning')
                                        Morning (সকাল)
                                    @elseif($seatPlan->shift === 'Afternoon')
                                        Afternoon (বিকাল)
                                    @else
                                        {{ $seatPlan->shift ?? 'N/A' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>অবস্থা:</th>
                                <td>
                                    @if($seatPlan->status === 'active')
                                        <span class="badge badge-success">সক্রিয়</span>
                                    @elseif($seatPlan->status === 'completed')
                                        <span class="badge badge-secondary">সম্পন্ন</span>
                                    @else
                                        <span class="badge badge-warning">খসড়া</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>তৈরির তারিখ:</th>
                                <td>{{ $seatPlan->created_at->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">শ্রেণী</h3>
                    </div>
                    <div class="card-body">
                        @if($seatPlan->classes->count() > 0)
                            <div class="d-flex flex-wrap">
                                @foreach($seatPlan->classes as $class)
                                    <span class="badge badge-info mr-2 mb-2">{{ $class->name }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">কোনো শ্রেণী নির্বাচিত নেই</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">পরীক্ষা</h3>
                    </div>
                    <div class="card-body">
                        @if($seatPlan->exams->count() > 0)
                            <ul class="mb-0 pl-3">
                                @foreach($seatPlan->exams as $exam)
                                    <li>{{ $exam->name }} ({{ $exam->class->name }})</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">কোনো পরীক্ষা নির্বাচিত নেই</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $seatPlan->rooms->count() }}</h3>
                        <p>মোট রুম</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $seatPlan->rooms->sum('total_capacity') }}</h3>
                        <p>মোট আসন</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chair"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $seatPlan->allocations->count() }}</h3>
                        <p>বরাদ্দকৃত আসন</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $seatPlan->rooms->sum('total_capacity') - $seatPlan->allocations->count() }}</h3>
                        <p>খালি আসন</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chair"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="{{ route('principal.institute.seat-plans.edit', [$school, $seatPlan]) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Add Room
                </button>
                <a href="{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Allocate Seats
                </a>
                <a href="{{ route('principal.institute.seat-plans.print-all', [$school, $seatPlan]) }}" class="btn btn-info" target="_blank">
                    <i class="fas fa-print"></i> Print All
                </a>
                <a href="{{ route('principal.institute.seat-plans.index', $school) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Rooms Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">রুম তালিকা</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>রুম নং</th>
                                    <th>রুমের নাম</th>
                                    <th>ভবন</th>
                                    <th>তলা</th>
                                    <th>সারি সংখ্যা</th>
                                    <th>প্রতি সারিতে আসন</th>
                                    <th>মোট আসন</th>
                                    <th>বরাদ্দকৃত</th>
                                    <th>খালি</th>
                                    <th>অবস্থা</th>
                                    <th class="text-right">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($seatPlan->rooms as $room)
                                    <tr>
                                        <td>{{ $room->room_no }}</td>
                                        <td>{{ $room->title }}</td>
                                        <td>{{ $room->building ?? 'N/A' }}</td>
                                        <td>{{ $room->floor ?? 'N/A' }}</td>
                                        <td>{{ $room->rows }}</td>
                                        <td>{{ $room->seats_per_row }}</td>
                                        <td>
                                            <span class="badge badge-primary">{{ $room->total_capacity }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ $room->allocated_count ?? $room->allocations->count() }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">{{ $room->total_capacity - ($room->allocated_count ?? $room->allocations->count()) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $allocatedCount = $room->allocated_count ?? $room->allocations->count();
                                                $percentage = $room->total_capacity > 0 ? ($allocatedCount / $room->total_capacity) * 100 : 0;
                                            @endphp
                                            @if($percentage >= 100)
                                                <span class="badge badge-success">পূর্ণ</span>
                                            @elseif($percentage >= 50)
                                                <span class="badge badge-warning">আংশিক</span>
                                            @else
                                                <span class="badge badge-secondary">খালি</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editRoom({{ $room->id }})" title="সম্পাদনা">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="{{ route('principal.institute.seat-plans.rooms.print', [$school, $seatPlan, $room]) }}" 
                                               class="btn btn-sm btn-info" target="_blank" title="প্রিন্ট">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan, 'room_id' => $room->id]) }}" 
                                               class="btn btn-sm btn-success" title="সিট বরাদ্দ">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <form action="{{ route('principal.institute.seat-plans.rooms.destroy', [$school, $seatPlan, $room]) }}" 
                                                  method="POST" class="d-inline" 
                                                  onsubmit="return confirm('এই রুমটি মুছে ফেলতে চান?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="মুছে ফেলুন">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle"></i> কোনো রুম যুক্ত করা হয়নি। 
                                            <button type="button" class="btn btn-sm btn-primary ml-2" data-toggle="modal" data-target="#addRoomModal">
                                                <i class="fas fa-plus"></i> রুম যুক্ত করুন
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Allocations -->
        @if($seatPlan->allocations->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success">
                        <h3 class="card-title">সাম্প্রতিক সিট বরাদ্দ (সর্বশেষ ২০টি)</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>রোল</th>
                                    <th>শিক্ষার্থীর নাম</th>
                                    <th>শ্রেণী</th>
                                    <th>রুম নং</th>
                                    <th>সিট নং</th>
                                    <th>বরাদ্দের তারিখ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($seatPlan->allocations->sortByDesc('created_at')->take(20) as $allocation)
                                    <tr>
                                        <td>{{ $allocation->student->student_id ?? 'N/A' }}</td>
                                        <td>{{ $allocation->student->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($allocation->student)
                                                <span class="badge badge-info">{{ $allocation->student->class->name ?? 'N/A' }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($allocation->room)
                                                রুম {{ $allocation->room->room_no }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $allocation->seat_no }}</span>
                                        </td>
                                        <td>{{ $allocation->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($seatPlan->allocations->count() > 20)
                    <div class="card-footer">
                        <a href="{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}" class="btn btn-sm btn-secondary">
                            সকল বরাদ্দ দেখুন
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addRoomForm" action="{{ route('principal.institute.seat-plans.rooms.store', [$school, $seatPlan]) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">নতুন রুম যুক্ত করুন</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="room_no">রুম নম্বর <span class="text-danger">*</span></label>
                        <input type="text" name="room_no" id="room_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="title">রুমের নাম</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="যেমন: কম্পিউটার ল্যাব">
                    </div>

                    <div class="form-group">
                        <label for="building">ভবন</label>
                        <input type="text" name="building" id="building" class="form-control" placeholder="যেমন: মূল ভবন">
                    </div>

                    <div class="form-group">
                        <label for="floor">তলা</label>
                        <input type="text" name="floor" id="floor" class="form-control" placeholder="যেমন: ১ম তলা">
                    </div>

                    <div class="form-group">
                        <label for="columns_count">কলাম সংখ্যা <span class="text-danger">*</span></label>
                        <select name="columns_count" id="columns_count" class="form-control" required>
                            <option value="1">১ টি</option>
                            <option value="2">২ টি</option>
                            <option value="3" selected>৩ টি</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="col1_benches">কলাম ১ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col1_benches" id="col1_benches" class="form-control" value="0" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="col2_benches">কলাম ২ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col2_benches" id="col2_benches" class="form-control" value="0" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="col3_benches">কলাম ৩ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col3_benches" id="col3_benches" class="form-control" value="0" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> সংরক্ষণ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editRoomForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">রুম সম্পাদনা করুন</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_room_no">রুম নম্বর <span class="text-danger">*</span></label>
                        <input type="text" name="room_no" id="edit_room_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_title">রুমের নাম</label>
                        <input type="text" name="title" id="edit_title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_building">ভবন</label>
                        <input type="text" name="building" id="edit_building" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_floor">তলা</label>
                        <input type="text" name="floor" id="edit_floor" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_columns_count">কলাম সংখ্যা <span class="text-danger">*</span></label>
                        <select name="columns_count" id="edit_columns_count" class="form-control" required>
                            <option value="1">১ টি</option>
                            <option value="2">২ টি</option>
                            <option value="3">৩ টি</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col1_benches">কলাম ১ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col1_benches" id="edit_col1_benches" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col2_benches">কলাম ২ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col2_benches" id="edit_col2_benches" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col3_benches">কলাম ৩ বেঞ্চ <span class="text-danger">*</span></label>
                                <input type="number" name="col3_benches" id="edit_col3_benches" class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> আপডেট করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        .content-header,
        .btn,
        .card-tools,
        .breadcrumb,
        .alert {
            display: none !important;
        }
        .card {
            border: 1px solid #000 !important;
            page-break-inside: avoid;
        }
        .small-box {
            border: 1px solid #000 !important;
        }
    }
</style>
@endpush
@push('scripts')
<script>
// Auto-hide success message after 3 seconds
$(document).ready(function() {
    setTimeout(function() {
        $('.alert-success').fadeOut('slow');
    }, 3000);
});

// Edit room function
function editRoom(roomId) {
    fetch(`{{ route('principal.institute.seat-plans.rooms.edit', [$school, $seatPlan, '__ROOM__']) }}`.replace('__ROOM__', roomId))
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_room_no').value = data.room_no;
            document.getElementById('edit_title').value = data.title || '';
            document.getElementById('edit_building').value = data.building || '';
            document.getElementById('edit_floor').value = data.floor || '';
            document.getElementById('edit_columns_count').value = data.columns_count;
            document.getElementById('edit_col1_benches').value = data.col1_benches;
            document.getElementById('edit_col2_benches').value = data.col2_benches;
            document.getElementById('edit_col3_benches').value = data.col3_benches;
            
            document.getElementById('editRoomForm').action = 
                `{{ route('principal.institute.seat-plans.rooms.update', [$school, $seatPlan, '__ROOM__']) }}`.replace('__ROOM__', roomId);
            
            $('#editRoomModal').modal('show');
        })
        .catch(error => {
            alert('রুম তথ্য লোড করতে ব্যর্থ হয়েছে');
            console.error(error);
        });
}

// Prevent double submission
document.addEventListener('DOMContentLoaded', function() {
    let isSubmitting = false;
    
    document.getElementById('addRoomForm').addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
    });
    
    document.getElementById('editRoomForm').addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
    });
});
</script>
@endpush

@endsection
