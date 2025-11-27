<div class="d-flex justify-content-between align-items-center small">
  <div>মোট: {{ $students->total() }}</div>
  <div>{!! $students->links() !!}</div>
</div>