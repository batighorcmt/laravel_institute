@extends('layouts.admin')

@section('title', 'FCM Diagnostics - ' . $school->name)

@section('content')
<div class="row mb-4">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">FCM Diagnostics & Logs</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="{{ route('principal.institute.manage', $school) }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

<div class="row">
    <!-- Health Check & Health Guidance -->
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title text-primary"><i class="fas fa-heartbeat mr-1"></i> Key Health / Status</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <b>Service Account File</b> 
                        @if($saExists)
                            <span class="badge badge-success text-xs">Found (OK)</span>
                        @else
                            <span class="badge badge-danger text-xs">Missing</span>
                        @endif
                    </li>
                    @if($saExists && isset($saDetails['project_id']))
                    <li class="list-group-item">
                        <b>Project ID</b> <span class="float-right text-muted small">{{ $saDetails['project_id'] }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Client Email</b> <span class="float-right text-muted small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="{{ $saDetails['client_email'] }}">{{ $saDetails['client_email'] }}</span>
                    </li>
                    @endif
                    <li class="list-group-item">
                        <b>Total Active Tokens</b> <span class="float-right font-weight-bold">{{ $tokens->total() }}</span>
                    </li>
                </ul>
                @if(!$saExists)
                <div class="alert alert-warning p-2 small">
                    <i class="fas fa-exclamation-triangle"></i> <code>storage/app/firebase-service-account.json</code> file is missing.
                </div>
                @elseif(!$saDetails)
                <div class="alert alert-danger p-2 small">
                    <i class="fas fa-times-circle"></i> JSON is <b>invalid</b>. Please check formatting (remove backslashes or spaces).
                </div>
                @endif
                <div class="text-xs text-muted mt-2 border-top pt-2">
                    <i class="fas fa-info-circle"></i> If notification errors persist with <code>invalid_grant</code>, regenerate the service account key in Firebase Console and replace the JSON file in storage.
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Test Push -->
    <div class="col-md-8">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title text-info"><i class="fas fa-paper-plane mr-1"></i> Send Quick Test Notification</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('principal.institute.fcm.test-send', $school) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="text-xs">Target User Device</label>
                            <select name="token_full" class="form-control select2" required onchange="updateFormData(this)">
                                <option value="">ব্যক্তি নির্বাচন করুন (টোকেনসহ)...</option>
                                @foreach($tokens as $t)
                                <option value="{{ $t->token }}" data-uid="{{ $t->user_id }}" data-uname="{{ $t->user->name }}">
                                    {{ $t->user->name }} ({{ $t->user->username }}) - {{ ucfirst($t->platform ?? 'Unknown') }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="user_id" id="test_user_id">
                            <input type="hidden" name="token" id="test_token">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs">Title</label>
                            <input type="text" name="title" class="form-control" value="Test Notification" required>
                        </div>
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs">Message Body</label>
                        <textarea name="body" class="form-control" rows="2" required>এটি একটি পরীক্ষামূলক নোটিফিকেশন। This is a test push.</textarea>
                    </div>
                    <button type="submit" class="btn btn-info px-4">
                        <i class="fas fa-paper-plane mr-1"></i> Send Test Push
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Registered Device Tokens -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-mobile-alt mr-1"></i> Registered Device Tokens</h3>
                <div class="card-tools">
                    <form action="{{ route('principal.institute.fcm.purge-stale', $school) }}" method="POST" class="form-inline" onsubmit="return confirm('Purge stale tokens?')">
                        @csrf
                        <input type="number" name="days" value="90" min="1" class="form-control form-control-sm mr-1" style="width:60px">
                        <button type="submit" class="btn btn-xs btn-outline-danger">Purge Stale (> Days)</button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3">User</th>
                            <th>Info</th>
                            <th>Token Snippet</th>
                            <th>Updated At</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $t)
                        <tr>
                            <td class="px-3">
                                <strong>{{ $t->user->name ?? 'Deleted User' }}</strong><br>
                                <small class="text-muted">{{ $t->user->username ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $t->platform == 'android' ? 'badge-success' : ($t->platform == 'ios' ? 'badge-primary' : 'badge-dark') }}">
                                    {{ ucfirst($t->platform ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="small text-muted" title="{{ $t->token }}">
                                <code style="font-size: 10px;">{{ Str::limit($t->token, 60) }}</code>
                            </td>
                            <td class="small">{{ $t->updated_at->format('d M, h:i A') }}</td>
                            <td class="text-center">
                                <form action="{{ route('principal.institute.fcm.token.destroy', [$school, $t]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this token?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger" title="Delete Device"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">এই প্রতিষ্ঠানের জন্য কোনো টোকেন পাওয়া যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tokens->hasPages())
            <div class="card-footer py-2 bg-white">{{ $tokens->appends(['logs_page'=>$logs->currentPage()])->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Notification History -->
    <div class="col-md-12">
        <div class="card shadow-sm mt-3 border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-history mr-1"></i> Recent Notification History</h3>
                <div class="card-tools d-flex align-items-center">
                    <form action="{{ route('principal.institute.fcm.logs.clear', $school) }}" method="POST" class="form-inline mr-3" onsubmit="return confirm('পুরানো লগ ডিলেট করতে চান?')">
                        @csrf @method('DELETE')
                        <input type="number" name="days" value="30" class="form-control form-control-sm mr-1" style="width:60px">
                        <button type="submit" class="btn btn-xs btn-outline-warning">Clear Older Logs</button>
                    </form>
                    <form action="" method="GET" class="form-inline">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Status Filter</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-striped hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3">Recipient</th>
                            <th>Status</th>
                            <th>Notification Details</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr class="{{ $log->status == 'failed' ? 'text-danger' : '' }}" style="{{ $log->status == 'failed' ? 'background: #fff5f5;' : '' }}">
                            <td class="px-3">
                                <strong>{{ $log->user->name ?? 'Unknown' }}</strong><br>
                                <small class="text-muted">{{ $log->user->username ?? '' }}</small>
                            </td>
                            <td>
                                @if($log->status == 'sent')
                                    <span class="badge badge-success px-2">✔ Sent</span>
                                @else
                                    <span class="badge badge-danger px-2">✖ Failed</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-bold" style="font-size: 13px;">{{ $log->title }}</div>
                                <div class="text-xs text-muted mb-1">{{ Str::limit($log->body, 80) }}</div>
                                @if($log->status == 'failed')
                                    <div class="text-xs text-danger border-top pt-1 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i> {{ $log->error_message ?? 'Unknown' }}
                                    </div>
                                @endif
                            </td>
                            <td class="small">{{ $log->created_at->format('d M, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">কোনো লগ পাওয়া যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="card-footer py-2 bg-white">{{ $logs->appends(['tokens_page'=>$tokens->currentPage()])->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateFormData(select) {
        if (!select.value) {
            document.getElementById('test_user_id').value = '';
            document.getElementById('test_token').value = '';
            return;
        }
        const option = select.options[select.selectedIndex];
        document.getElementById('test_user_id').value = option.getAttribute('data-uid');
        document.getElementById('test_token').value = select.value;
    }

    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({
                placeholder: "ব্যক্তি নির্বাচন করুন (টোকেনসহ)...",
                allowClear: true,
                theme: 'bootstrap4'
            });
        }
    });
</script>
@endsection
