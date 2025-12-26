@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Fee Categories</h3>
  </div>
  <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    <form method="POST" action="{{ route('billing.settings.categories.store') }}" class="mb-4">
      @csrf
      <div class="row">
        <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-md-3">
          <select name="type" class="form-control" required>
            <option value="monthly">Monthly</option>
            <option value="one_time">One-time</option>
          </select>
        </div>
          <div class="col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_global" id="is_global"><label class="form-check-label" for="is_global">Global</label></div></div>
          <div class="col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="active"><label class="form-check-label" for="active">Active</label></div></div>
      </div>
      <div class="mt-2"><button class="btn btn-primary">Save</button></div>
    </form>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Global</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($categories as $c)
          <tr>
            <td>{{ $c->id }}</td>
            <td>{{ $c->name }}</td>
            <td>{{ $c->frequency }}</td>
            <td>{{ $c->is_common ? 'Yes' : 'No' }}</td>
            <td>{{ $c->active ? 'Yes' : 'No' }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary" type="button" onclick="document.getElementById('edit-row-{{ $c->id }}').classList.toggle('d-none')">Edit</button>
              <form method="POST" action="{{ route('billing.settings.categories.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete this category?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
          <tr id="edit-row-{{ $c->id }}" class="d-none">
            <td colspan="6">
              <form method="POST" action="{{ route('billing.settings.categories.update', $c) }}">
                @csrf
                @method('PATCH')
                <div class="row align-items-center">
                  <div class="col-md-4"><input type="text" name="name" value="{{ $c->name }}" class="form-control" required></div>
                  <div class="col-md-3">
                    <select name="type" class="form-control" required>
                      <option value="monthly" {{ $c->frequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                      <option value="one_time" {{ $c->frequency === 'one_time' ? 'selected' : '' }}>One-time</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="is_global" id="is_global_{{ $c->id }}" {{ $c->is_common ? 'checked' : '' }}>
                      <label class="form-check-label" for="is_global_{{ $c->id }}">Global</label>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="active" id="active_{{ $c->id }}" {{ $c->active ? 'checked' : '' }}>
                      <label class="form-check-label" for="active_{{ $c->id }}">Active</label>
                    </div>
                  </div>
                  <div class="col-md-1"><button class="btn btn-sm btn-primary">Update</button></div>
                </div>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $categories->links() }}
  </div>
</div>
@endsection
