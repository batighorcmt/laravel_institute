@extends('layouts.admin')

@section('title', 'FCM Diagnostics - ' . $school->name)

@section('content')
<div class="row mb-4">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">FCM Diagnostics & Logs</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="{{ route('principal.institute.manage', $school) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

<div class="row">
    <!-- Health Check Card -->
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-1"></i> Health Check</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Service Account File</b> 
                        <span class="float-right">
                            @if($saExists)
                                <span class="badge badge-success">Found</span>
                            @else
                                <span class="badge badge-danger">Missing</span>
                            @endif
                        </span>
                    </li>
                    @if($saExists && $saDetails)
                    <li class="list-group-item">
                        <b>Project ID</b> <span class="float-right text-muted small">{{ $saDetails['project_id'] ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Client Email</b> <span class="float-right text-muted small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">{{ $saDetails['client_email'] ?? 'N/A' }}</span>
                    </li>
                    @endif
                    <li class="list-group-item">
                        <b>Active Tokens</b> <span class="float-right font-weight-bold">{{ $tokens->total() }}</span>
                    </li>
                </ul>
                @if(!$saExists)
                <div class="alert alert-warning p-2 small">
                    <i class="fas fa-exclamation-triangle"></i> <code>storage/app/firebase-service-account.json</code> ফাইলটি খুঁজে পাওয়া যায়নি।
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Test Send Card -->
    <div class="col-md-8">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Quick Test Push</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('principal.institute.fcm.test-send', $school) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Select User (with token)</label>
                            <select name="token_user_combined" class="form-control select2" required onchange="updateToken(this)">
                                <option value="">ব্যক্তি নির্বাচন করুন...</option>
                                @foreach($tokens as $t)
                                <option value="{{ $t->token }}" data-uid="{{ $t->user_id }}">
                                    {{ $t->user->name }} ({{ $t->user->username }}) - {{ $t->platform ?? 'Unknown' }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="user_id" id="test_user_id">
                            <input type="hidden" name="token" id="test_token">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="Test Notification" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message Body</label>
                        <textarea name="body" class="form-control" rows="2" required>এটি একটি পরীক্ষামূলক নোটিফিকেশন।</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-send"></i> Send Test Push
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Registered Tokens Table -->
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h3 class="card-title">Registered Device Tokens</h3>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 400px;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>User</th>
                            <th>Platform</th>
                            <th>Token (Truncated)</th>
                            <th>Last Used</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $t)
                        <tr>
                            <td>
                                <div><strong>{{ $t->user->name }}</strong></div>
                                <small class="text-muted">{{ $t->user->username }}</small>
                            </td>
                            <td><span class="badge badge-info">{{ ucfirst($t->platform ?? 'N/A') }}</span></td>
                            <td class="small text-muted" title="{{ $t->token }}">
                                {{ Str::limit($t->token, 50) }}
                            </td>
                            <td>{{ $t->updated_at->diffForHumans() }}</td>
                            <td>
                                <form action="{{ route('principal.institute.fcm.token.destroy', [$school, $t]) }}" method="POST" onsubmit="return confirm('Delete this token? User will not receive notifications on this device until they login again.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center p-4 text-muted">No active tokens found for this school.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tokens->hasPages())
            <div class="card-footer py-2">
                {{ $tokens->appends(['logs_page' => $logs->currentPage()])->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Notification Logs Table -->
    <div class="col-md-12">
        <div class="card shadow-sm mt-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <h3 class="card-title">Notification History (Latest)</h3>
                <div class="card-tools d-flex">
                    <form action="{{ route('principal.institute.fcm.logs.clear', $school) }}" method="POST" class="form-inline mr-3" onsubmit="return confirm('পুরানো লোগগুলো মুছে ফেলতে চান?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="days" value="30">
                        <button type="submit" class="btn btn-xs btn-outline-warning" title="Clear logs older than 30 days">
                            <i class="fas fa-broom mr-1"></i> Clear Old Logs
                        </button>
                    </form>
                    <form action="" method="GET" class="form-inline">
                        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent Only</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed Only</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Notification Content</th>
                            <th>Result / Error</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $log->user->name ?? 'Unknown' }}</div>
                                <small class="text-muted">{{ $log->user->username ?? '' }}</small>
                            </td>
                            <td>
                                @if($log->status == 'sent')
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Sent</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $log->title }}</div>
                                <div class="small">{{ Str::limit($log->body, 60) }}</div>
                            </td>
                            <td class="small">
                                @if($log->status == 'failed')
                                    <span class="text-danger">{{ $log->error_message ?? 'Unknown' }}</span>
                                @else
                                    <span class="text-success text-xs">Delivered to FCM</span>
                                @endif
                            </td>
                            <td>{{ $log->created_at->format('d M, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center p-4 text-muted">No logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="card-footer py-2">
                {{ $logs->appends(['tokens_page' => $tokens->currentPage()])->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateToken(select) {
        const option = select.options[select.selectedIndex];
        document.getElementById('test_user_id').value = option.getAttribute('data-uid');
        document.getElementById('test_token').value = select.value;
    }

    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({
                placeholder: "ব্যক্তি নির্বাচন করুন...",
                allowClear: true,
                theme: 'bootstrap4'
            });
        }
    });
</script>
@endsection
