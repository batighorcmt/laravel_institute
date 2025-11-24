@extends('layouts.admin')

@section('title', 'রুম ব্যবস্থাপনা - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">রুম ব্যবস্থাপনা</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">সিট প্ল্যান</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}">{{ $seatPlan->name }}</a></li>
                    <li class="breadcrumb-item active">রুম ব্যবস্থাপনা</li>
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

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Seat Plan Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>সিট প্ল্যান:</strong> {{ $seatPlan->name }}
                            </div>
                            <div class="col-md-3">
                                <strong>শিফট:</strong> {{ $seatPlan->shift ?? 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>মোট রুম:</strong> {{ count($rooms) }}
                            </div>
                            <div class="col-md-3">
                                <strong>মোট আসন:</strong> {{ collect($rooms)->sum('total_capacity') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add Room Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">নতুন রুম যুক্ত করুন</h3>
                    </div>
                    <form id="roomForm" action="{{ route('principal.institute.seat-plans.rooms.store', [$school, $seatPlan]) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="room_no">রুম নম্বর <span class="text-danger">*</span></label>
                                <input type="text" name="room_no" id="room_no" class="form-control @error('room_no') is-invalid @enderror" 
                                       value="{{ old('room_no') }}" required>
                                @error('room_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="title">রুমের নাম</label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                       value="{{ old('title') }}" placeholder="যেমন: কম্পিউটার ল্যাব">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="building">ভবন</label>
                                <input type="text" name="building" id="building" class="form-control" 
                                       value="{{ old('building') }}" placeholder="যেমন: মূল ভবন">
                            </div>

                            <div class="form-group">
                                <label for="floor">তলা</label>
                                <input type="text" name="floor" id="floor" class="form-control" 
                                       value="{{ old('floor') }}" placeholder="যেমন: ২য় তলা">
                            </div>

                            <div class="form-group">
                                <label for="columns_count">কলাম সংখ্যা <span class="text-danger">*</span></label>
                                <select name="columns_count" id="columns_count" class="form-control @error('columns_count') is-invalid @enderror" required>
                                    <option value="1" {{ old('columns_count') == 1 ? 'selected' : '' }}>১টি কলাম</option>
                                    <option value="2" {{ old('columns_count', 2) == 2 ? 'selected' : '' }}>২টি কলাম</option>
                                    <option value="3" {{ old('columns_count') == 3 ? 'selected' : '' }}>৩টি কলাম</option>
                                </select>
                                @error('columns_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">রুমে কতটি সারি থাকবে</small>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="col1_benches">কলাম ১ বেঞ্চ</label>
                                        <input type="number" name="col1_benches" id="col1_benches" class="form-control" 
                                               value="{{ old('col1_benches', 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="col2_benches">কলাম ২ বেঞ্চ</label>
                                        <input type="number" name="col2_benches" id="col2_benches" class="form-control" 
                                               value="{{ old('col2_benches', 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="col3_benches">কলাম ৩ বেঞ্চ</label>
                                        <input type="number" name="col3_benches" id="col3_benches" class="form-control" 
                                               value="{{ old('col3_benches', 0) }}" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="rows">সারি সংখ্যা</label>
                                <input type="number" name="rows" id="rows" class="form-control" 
                                       value="{{ old('rows', 0) }}" min="0">
                                <small class="text-muted">প্রতি কলামে কতগুলো সারি</small>
                            </div>

                            <div class="form-group">
                                <label for="seats_per_row">প্রতি সারিতে আসন</label>
                                <input type="number" name="seats_per_row" id="seats_per_row" class="form-control" 
                                       value="{{ old('seats_per_row', 2) }}" min="1">
                                <small class="text-muted">সাধারণত ২ (বাম ও ডান)</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-save"></i> সংরক্ষণ করুন
                            </button>
                            <a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> ফিরে যান
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rooms List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info">
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
                                    <th>কলাম</th>
                                    <th>সারি</th>
                                    <th>আসন/সারি</th>
                                    <th>মোট আসন</th>
                                    <th>বরাদ্দ</th>
                                    <th class="text-right">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rooms as $room)
                                    <tr>
                                        <td>{{ $room->room_no }}</td>
                                        <td>{{ $room->title ?? 'N/A' }}</td>
                                        <td>{{ $room->building ?? 'N/A' }}</td>
                                        <td>{{ $room->floor ?? 'N/A' }}</td>
                                        <td>{{ $room->columns_count }}</td>
                                        <td>{{ $room->rows ?? 0 }}</td>
                                        <td>{{ $room->seats_per_row ?? 2 }}</td>
                                        <td>
                                            <span class="badge badge-primary">{{ $room->total_capacity }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ $room->allocated_count ?? 0 }}</span>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editRoom({{ $room->id }})" title="সম্পাদনা">
                                                <i class="fas fa-edit"></i>
                                            </button>
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
                                        <td colspan="10" class="text-center text-muted">
                                            কোনো রুম যুক্ত করা হয়নি
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
                            <option value="1">১টি কলাম</option>
                            <option value="2">২টি কলাম</option>
                            <option value="3">৩টি কলাম</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col1_benches">কলাম ১ বেঞ্চ</label>
                                <input type="number" name="col1_benches" id="edit_col1_benches" class="form-control" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col2_benches">কলাম ২ বেঞ্চ</label>
                                <input type="number" name="col2_benches" id="edit_col2_benches" class="form-control" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_col3_benches">কলাম ৩ বেঞ্চ</label>
                                <input type="number" name="col3_benches" id="edit_col3_benches" class="form-control" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_rows">সারি সংখ্যা</label>
                        <input type="number" name="rows" id="edit_rows" class="form-control" min="0">
                    </div>

                    <div class="form-group">
                        <label for="edit_seats_per_row">প্রতি সারিতে আসন</label>
                        <input type="number" name="seats_per_row" id="edit_seats_per_row" class="form-control" min="1">
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

@push('scripts')
<script>
// Prevent double submission
let isSubmitting = false;
document.getElementById('roomForm').addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> সংরক্ষণ হচ্ছে...';
    
    setTimeout(() => {
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> সংরক্ষণ করুন';
    }, 5000);
});

// Edit room
function editRoom(roomId) {
    // Fetch room data
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
            document.getElementById('edit_rows').value = data.rows || 0;
            document.getElementById('edit_seats_per_row').value = data.seats_per_row || 2;
            
            document.getElementById('editRoomForm').action = 
                `{{ route('principal.institute.seat-plans.rooms.update', [$school, $seatPlan, '__ROOM__']) }}`.replace('__ROOM__', roomId);
            
            $('#editRoomModal').modal('show');
        })
        .catch(error => {
            alert('রুম তথ্য লোড করতে ব্যর্থ হয়েছে');
            console.error(error);
        });
}
</script>
@endpush
@endsection
