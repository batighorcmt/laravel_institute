@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Discounts</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('billing.settings.discounts.store') }}" class="mb-4">
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
          <select name="student_id" class="form-control">
            <option value="">Student (optional)</option>
            @foreach($students as $st)
              <option value="{{ $st->id }}">{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select name="class_id" class="form-control">
            <option value="">Class (optional)</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="type" class="form-control" required>
            <option value="fixed">Fixed</option>
            <option value="percent">Percent</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="scope" class="form-control">
            <option value="fee">Fee</option>
            <option value="fine">Fine</option>
          </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" name="value" class="form-control" placeholder="Value" required></div>
        <div class="col-md-2"><input type="month" name="start_month" class="form-control" required></div>
        <div class="col-md-2 mt-2"><input type="month" name="end_month" class="form-control"></div>
      </div>
      <div class="mt-2"><button class="btn btn-primary">Save</button></div>
    </form>

    <table class="table table-striped">
      <thead><tr><th>ID</th><th>School</th><th>Student</th><th>Class</th><th>Type</th><th>Value</th><th>Start</th><th>End</th></tr></thead>
      <tbody>
        @foreach($discounts as $d)
          <tr>
            <td>{{ $d->id }}</td>
            <td>{{ $d->school_id }}</td>
            <td>{{ $d->student_id }}</td>
            <td>{{ $d->class_id }}</td>
            <td>{{ $d->type }}</td>
            <td>{{ $d->value }}</td>
            <td>{{ $d->start_month }}</td>
            <td>{{ $d->end_month }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $discounts->links() }}
  </div>
</div>
@endsection
