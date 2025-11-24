@extends('layouts.admin')

@section('title', 'রুম ম্যানেজমেন্ট - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">রুম ম্যানেজমেন্ট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">সিট প্ল্যান</a></li>
                    <li class="breadcrumb-item active">রুম ম্যানেজমেন্ট</li>
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
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title">{{ $seatPlan->name }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#addRoomModal">
                        <i class="fas fa-plus"></i> নতুন রুম যুক্ত করুন
                    </button>
                </div>
            </div>
        </div>

        <!-- Rooms List -->
        <div class="row">
            @forelse($seatPlan->rooms as $room)
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <strong>রুম নং: {{ $room->room_no }}</strong>
                                @if($room->title)
                                    <br><small>{{ $room->title }}</small>
                                @endif
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editRoomModal{{ $room->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('principal.institute.seat-plans.rooms.destroy', [$school, $seatPlan, $room]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="50%">মোট কলাম:</th>
                                    <td>{{ $room->columns_count }}</td>
                                </tr>
                                <tr>
                                    <th>কলাম ১ বেঞ্চ:</th>
                                    <td>{{ $room->col1_benches }}</td>
                                </tr>
                                @if($room->columns_count >= 2)
                                    <tr>
                                        <th>কলাম ২ বেঞ্চ:</th>
                                        <td>{{ $room->col2_benches }}</td>
                                    </tr>
                                @endif
                                @if($room->columns_count >= 3)
                                    <tr>
                                        <th>কলাম ৩ বেঞ্চ:</th>
                                        <td>{{ $room->col3_benches }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>মোট ধারণক্ষমতা:</th>
                                    <td><strong class="text-primary">{{ $room->total_capacity }}</strong></td>
                                </tr>
                                <tr>
                                    <th>বরাদ্দকৃত:</th>
                                    <td><strong class="text-success">{{ $room->allocated_count }}</strong></td>
                                </tr>
                                <tr>
                                    <th>খালি:</th>
                                    <td><strong class="text-info">{{ $room->total_capacity - $room->allocated_count }}</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}?room_id={{ $room->id }}" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-chair"></i> সিট বরাদ্দ করুন
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Edit Room Modal -->
                <div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('principal.institute.seat-plans.rooms.update', [$school, $seatPlan, $room]) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">রুম সম্পাদনা করুন</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>রুম নম্বর <span class="text-danger">*</span></label>
                                        <input type="text" name="room_no" class="form-control" value="{{ $room->room_no }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label>রুমের শিরোনাম</label>
                                        <input type="text" name="title" class="form-control" value="{{ $room->title }}">
                                    </div>

                                    <div class="form-group">
                                        <label>মোট কলাম <span class="text-danger">*</span></label>
                                        <select name="columns_count" class="form-control" id="columns_edit_{{ $room->id }}" required>
                                            <option value="1" {{ $room->columns_count == 1 ? 'selected' : '' }}>১টি</option>
                                            <option value="2" {{ $room->columns_count == 2 ? 'selected' : '' }}>২টি</option>
                                            <option value="3" {{ $room->columns_count == 3 ? 'selected' : '' }}>৩টি</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>কলাম ১ এ বেঞ্চ সংখ্যা <span class="text-danger">*</span></label>
                                        <input type="number" name="col1_benches" class="form-control" value="{{ $room->col1_benches }}" min="1" required>
                                    </div>

                                    <div class="form-group col2-field">
                                        <label>কলাম ২ এ বেঞ্চ সংখ্যা</label>
                                        <input type="number" name="col2_benches" class="form-control" value="{{ $room->col2_benches }}" min="0">
                                    </div>

                                    <div class="form-group col3-field">
                                        <label>কলাম ৩ এ বেঞ্চ সংখ্যা</label>
                                        <input type="number" name="col3_benches" class="form-control" value="{{ $room->col3_benches }}" min="0">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                                    <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এই সিট প্ল্যানে এখনো কোনো রুম যুক্ত করা হয়নি। নতুন রুম যুক্ত করুন।
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Add Room Modal -->
        <div class="modal fade" id="addRoomModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('principal.institute.seat-plans.rooms.store', [$school, $seatPlan]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">নতুন রুম যুক্ত করুন</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>রুম নম্বর <span class="text-danger">*</span></label>
                                <input type="text" name="room_no" class="form-control" required>
                                <small class="form-text text-muted">উদাহরণ: 101, A-1, Ground Floor</small>
                            </div>

                            <div class="form-group">
                                <label>রুমের শিরোনাম</label>
                                <input type="text" name="title" class="form-control">
                                <small class="form-text text-muted">উদাহরণ: প্রধান কক্ষ, বিজ্ঞান ল্যাব</small>
                            </div>

                            <div class="form-group">
                                <label>মোট কলাম <span class="text-danger">*</span></label>
                                <select name="columns_count" class="form-control" id="columns_count" required>
                                    <option value="1">১টি</option>
                                    <option value="2" selected>২টি</option>
                                    <option value="3">৩টি</option>
                                </select>
                                <small class="form-text text-muted">রুমে কয়টি কলাম আছে?</small>
                            </div>

                            <div class="form-group">
                                <label>কলাম ১ এ বেঞ্চ সংখ্যা <span class="text-danger">*</span></label>
                                <input type="number" name="col1_benches" class="form-control" value="10" min="1" required>
                            </div>

                            <div class="form-group col2-field">
                                <label>কলাম ২ এ বেঞ্চ সংখ্যা</label>
                                <input type="number" name="col2_benches" class="form-control" value="10" min="0">
                            </div>

                            <div class="form-group col3-field" style="display: none;">
                                <label>কলাম ৩ এ বেঞ্চ সংখ্যা</label>
                                <input type="number" name="col3_benches" class="form-control" value="0" min="0">
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> প্রতিটি বেঞ্চে ২ জন শিক্ষার্থী বসতে পারবে (Left/Right)
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                            <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide column fields based on selection
    $('#columns_count').on('change', function() {
        const count = parseInt($(this).val());
        
        if (count >= 2) {
            $('.col2-field').show();
        } else {
            $('.col2-field').hide();
        }
        
        if (count >= 3) {
            $('.col3-field').show();
        } else {
            $('.col3-field').hide();
        }
    });
});
</script>
@endpush
@endsection
