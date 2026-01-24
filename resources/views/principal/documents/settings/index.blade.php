@extends('layouts.admin')
@section('title','Documents: Settings')

@section('content')
<div class="row">
  @foreach($pages as $page)

    @php
      // 🔐 Ensure $page is always a STRING
      $pageKey = is_array($page)
        ? ($page['key'] ?? array_key_first($page))
        : (string) $page;

      // Existing setting for this page
      $s = $settings[$pageKey] ?? null;
    @endphp

    <div class="col-md-4">
      <div class="card mb-3">

        <div class="card-header">
          <strong>{{ ucfirst($pageKey) }} Settings</strong>
        </div>

        <div class="card-body">
          <form method="POST"
                action="{{ route('principal.institute.documents.settings.store', $school) }}"
                enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="page" value="{{ $pageKey }}">

            {{-- Background Image --}}
            <div class="form-group">
              <label>Background Image</label>
              <input type="file"
                     class="form-control-file"
                     name="background"
                     accept="image/*">

              @if($s && is_string($s->background_path ?? null) && $s->background_path)
                <div class="mt-2">
                  <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s->background_path) }}"
                       alt="bg"
                       class="img-fluid rounded">
                </div>
              @endif
            </div>

            {{-- Colors --}}
            <div class="form-row">
              <div class="form-group col">
                <label>Title Color</label>
                <input type="color"
                       class="form-control"
                       name="colors[title]"
                       value="{{ ($s && is_array($s->colors ?? null) && isset($s->colors['title']))
                                  ? $s->colors['title']
                                  : '#111111' }}">
              </div>

              <div class="form-group col">
                <label>Body Color</label>
                <input type="color"
                       class="form-control"
                       name="colors[body]"
                       value="{{ ($s && is_array($s->colors ?? null) && isset($s->colors['body']))
                                  ? $s->colors['body']
                                  : '#333333' }}">
              </div>
            </div>

            {{-- Memo Number Format --}}
            <div class="form-group">
              <label>Memo Number Format</label>
              <p class="text-muted small">
                Select and arrange the keywords to form the memo number. Drag to reorder.
              </p>

              <div id="memo-format-{{ $pageKey }}"
                   class="sortable-list border p-2"
                   style="min-height: 100px;">

                @php
                  $selectedFormats = is_array($selected[$pageKey] ?? null)
                    ? $selected[$pageKey]
                    : [];
                @endphp

                {{-- Selected --}}
                @foreach($selectedFormats as $keyword)
                  <div class="form-check d-flex align-items-center bg-light p-2 mb-1"
                       data-keyword="{{ $keyword }}">
                    <input class="form-check-input"
                           type="checkbox"
                           name="memo_format[]"
                           value="{{ $keyword }}"
                           id="memo-{{ $pageKey }}-{{ $keyword }}"
                           checked>
                    <label class="form-check-label ml-2 flex-grow-1"
                           for="memo-{{ $pageKey }}-{{ $keyword }}">
                      {{ ucwords(str_replace('_', ' ', $keyword)) }}
                    </label>
                    <span class="handle" style="cursor: move;">&#9776;</span>
                  </div>
                @endforeach

                @php
                  $available = array_diff(
                    ['institution_code','custom_text','academic_year','serial_no','class','type'],
                    $selectedFormats
                  );
                @endphp

                {{-- Available --}}
                @foreach($available as $keyword)
                  <div class="form-check d-flex align-items-center bg-light p-2 mb-1"
                       data-keyword="{{ $keyword }}">
                    <input class="form-check-input"
                           type="checkbox"
                           name="memo_format[]"
                           value="{{ $keyword }}"
                           id="memo-{{ $pageKey }}-{{ $keyword }}">
                    <label class="form-check-label ml-2 flex-grow-1"
                           for="memo-{{ $pageKey }}-{{ $keyword }}">
                      {{ ucwords(str_replace('_', ' ', $keyword)) }}
                    </label>
                    <span class="handle" style="cursor: move;">&#9776;</span>
                  </div>
                @endforeach
              </div>
            </div>

            {{-- Custom Text --}}
            <div class="form-group">
              <label>Custom Text</label>
              <p class="text-muted small">Enter multiple values separated by commas</p>
              <textarea class="form-control"
                        name="custom_text"
                        rows="2"
                        placeholder="Enter comma-separated values">{{
                $s && $s->custom_text
                  ? (is_array($s->custom_text)
                      ? implode(',', $s->custom_text)
                      : (is_string($s->custom_text) ? $s->custom_text : ''))
                  : ''
              }}</textarea>
            </div>

            <button class="btn btn-primary">Save</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
$(document).ready(function () {
  $('.sortable-list').sortable({
    handle: '.handle'
  });
});
</script>
@endsection
