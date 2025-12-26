@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Fee Structures</h3>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
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
    <form method="POST" action="{{ route('billing.settings.fee_structures.store') }}" class="mb-4">
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
          <select name="class_id" class="form-control" required>
            <option value="">Select Class</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
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
        <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
        <div class="col-md-2"><input type="date" name="effective_from" class="form-control" required></div>
        <div class="col-md-2"><input type="date" name="effective_to" class="form-control"></div>
        <div class="col-md-2"><input type="number" min="1" max="31" name="due_day_of_month" class="form-control" placeholder="Due Day (1-31)"></div>
        <div class="col-md-3"><input type="date" name="due_date" class="form-control" placeholder="Due Date (one-time)"></div>
      </div>
      <div class="mt-2"><button class="btn btn-primary">Save</button></div>
    </form>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>School</th><th>Class</th><th>Category</th><th>Amount</th><th>From</th><th>To</th><th>Due Day</th><th>Due Date</th></tr></thead>
      <tbody>
        @foreach($structures as $s)
          <tr>
            <td>{{ $s->id }}</td>
            <td>{{ $s->school_id }}</td>
            <td>{{ $s->class_id }}</td>
            <td>{{ $s->category_id }}</td>
            <td>{{ number_format($s->amount,2) }}</td>
            <td>{{ $s->effective_from }}</td>
            <td>{{ $s->effective_to }}</td>
            <td>{{ $s->due_day_of_month }}</td>
            <td>{{ $s->due_date }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $structures->links() }}
  </div>
</div>
@endsection
