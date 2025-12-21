@extends('layouts.admin')
@section('title','Certificate তালিকা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Certificate তালিকা</h4>
  <a href="{{ route('principal.institute.documents.certificate.index', $school) }}" class="btn btn-primary">নতুন তৈরি</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>স্মারক নং</th>
          <th>তারিখ</th>
          <th>শিক্ষার্থী</th>
          <th>শিরোনাম</th>
          <th>কর্ম</th>
        </tr>
      </thead>
      <tbody>
        @forelse($records as $rec)
          <tr>
            <td>{{ $rec->memo_no }}</td>
            <td>{{ $rec->issued_at?->format('d-m-Y') }}</td>
            <td>{{ $rec->student?->full_name }}</td>
            <td>{{ $rec->data['certificate_title'] ?? '-' }}</td>
            <td>
              <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('principal.institute.documents.certificate.print', [$school, $rec->id]) }}">Print</a>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('principal.institute.documents.certificate.edit', [$school, $rec->id]) }}">Edit</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">কোনো রেকর্ড নেই</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $records->links() }}</div>
</div>
@endsection
