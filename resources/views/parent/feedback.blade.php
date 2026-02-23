@extends('layouts.admin')
@section('title', 'মতামত ও অভিযোগ')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-comment-dots mr-2"></i> মতামত ও অভিযোগ</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">মতামত বা অভিযোগ পাঠান</h3>
                </div>
                <form action="{{ route('parent.feedback.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                    <div class="card-body">
                        <div class="form-group">
                            <label>বিষয়</label>
                            <input type="text" name="subject" class="form-control" placeholder="বিষয় লিখুন" required>
                        </div>
                        <div class="form-group">
                            <label>আপনার বার্তা/অভিযোগ</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="বিস্তারিত লিখুন..." required></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">জমা দিন</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title">আপনার প্রেরিত বার্তার তালিকা</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>তারিখ</th>
                                <th>বিষয়</th>
                                <th>অবস্থা</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feedbacks as $fb)
                            <tr>
                                <td>{{ $fb->created_at->format('d M, Y') }}</td>
                                <td>{{ $fb->subject }}</td>
                                <td>
                                    <span class="badge badge-{{ $fb->status == 'resolved' ? 'success' : ($fb->status == 'pending' ? 'warning' : 'primary') }}">
                                        {{ ucfirst($fb->status) }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-info" data-toggle="modal" data-target="#fbModal{{ $fb->id }}">দেখুন</button>
                                    
                                    <!-- Modal -->
                                    <div class="modal fade" id="fbModal{{ $fb->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ $fb->subject }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <strong>বার্তা:</strong>
                                                    <p>{{ $fb->message }}</p>
                                                    @if($fb->reply)
                                                    <hr>
                                                    <strong>উত্তর:</strong>
                                                    <p class="text-primary">{{ $fb->reply }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">কোনো বার্তা নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-3">
                        {{ $feedbacks->appends(['student_id' => $selectedStudent->id])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
