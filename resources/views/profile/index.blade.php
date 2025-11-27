@extends('layouts.admin')

@section('title','My Profile')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">My Profile</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-id-card mr-2"></i> Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">{{ $user->name }}</dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8">{{ $user->username ?? '-' }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $user->email }}</dd>

                            <dt class="col-sm-4">Last Password Change</dt>
                            <dd class="col-sm-8">{{ optional($user->password_changed_at)->format('d/m/Y h:i A') ?? 'Never' }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i> Roles & Schools</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40%">Role</th>
                                    <th>School</th>
                                    <th style="width:140px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $r)
                                    <tr>
                                        <td>{{ ucfirst($r->role->name ?? '-') }}</td>
                                        <td>{{ $r->school->name ?? '-' }}</td>
                                        <td><span class="badge badge-{{ ($r->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">{{ $r->status ?? 'active' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No roles assigned</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <h3 class="card-title"><i class="fas fa-image mr-2"></i> Profile Photo</h3>
                    </div>
                    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="text-center mb-3">
                                @if($user->avatar)
                                    <img src="{{ asset('storage/'.$user->avatar) }}" class="img-thumbnail" style="max-width: 160px;">
                                @else
                                    <div class="text-muted">No photo</div>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="avatar">Upload New Photo</label>
                                <input type="file" class="form-control-file" id="avatar" name="avatar" accept="image/*" required>
                                <small class="form-text text-muted">Max 2MB. JPG/PNG.</small>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-upload mr-1"></i> Update Photo</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title"><i class="fas fa-key mr-2"></i> Change Password</h3>
                    </div>
                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="#current_password"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="#password"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Minimum 8 characters, include upper & lower case, and a number.</small>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="#password_confirmation"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.toggle-pass').forEach(function(btn){
    btn.addEventListener('click', function(){
      var input = document.querySelector(this.getAttribute('data-target'));
      if (!input) return;
      var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      var icon = this.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      }
    });
  });
});
</script>
@endpush
