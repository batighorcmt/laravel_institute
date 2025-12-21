@extends('layouts.admin')
@section('title','Documents: Settings')

@section('content')
<div class="row">
  @foreach($pages as $page)
    @php($s = $settings[$page] ?? null)
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header"><strong>{{ ucfirst($page) }} Settings</strong></div>
        <div class="card-body">
          <form method="POST" action="{{ route('principal.institute.documents.settings.store', $school) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="page" value="{{ $page }}">
            <div class="form-group">
              <label>Background Image</label>
              <input type="file" class="form-control-file" name="background" accept="image/*">
              @if($s && $s->background_path)
                <div class="mt-2"><img src="{{ asset('storage/'.$s->background_path) }}" alt="bg" class="img-fluid rounded"></div>
              @endif
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label>Title Color</label>
                <input type="color" class="form-control" name="colors[title]" value="{{ $s->colors['title'] ?? '#111111' }}">
              </div>
              <div class="form-group col">
                <label>Body Color</label>
                <input type="color" class="form-control" name="colors[body]" value="{{ $s->colors['body'] ?? '#333333' }}">
              </div>
            </div>
            <button class="btn btn-primary">Save</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection
