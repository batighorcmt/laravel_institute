@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Global Fees</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('billing.settings.global_fees.store') }}" class="mb-4">
      @csrf
      <div class="row">
        <div class="col-md-3">
          <select name="school_id" class="form-control" required>
            <option value="">Select School</option>
            @foreach($schools as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select name="category_id" class="form-control" required>
            <option value="">Select Category</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
        <div class="col-md-2"><input type="date" name="effective_from" class="form-control" required></div>
        <div class="col-md-2"><input type="date" name="effective_to" class="form-control"></div>
      </div>
      <div class="mt-2"><button class="btn btn-primary">Save</button></div>
    </form>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>School</th><th>Category</th><th>Amount</th><th>From</th><th>To</th></tr></thead>
      <tbody>
        @foreach($globals as $g)
          <tr>
            <td>{{ $g->id }}</td>
            <td>{{ $g->school_id }}</td>
            <td>{{ $g->category_id }}</td>
            <td>{{ number_format($g->amount,2) }}</td>
            <td>{{ $g->effective_from }}</td>
            <td>{{ $g->effective_to }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $globals->links() }}
  </div>
</div>
@endsection
