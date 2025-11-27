@extends('layouts.admin')

@section('title', 'Students Directory')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Students Directory</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
          <li class="breadcrumb-item active">Students</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <form method="GET" class="mb-3">
          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Class</label>
              <select name="class_id" class="form-control" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Section</label>
              <select name="section_id" class="form-control" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach($sections as $s)
                  <option value="{{ $s->id }}" {{ request('section_id')==$s->id?'selected':'' }}>{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Group</label>
              <select name="group_id" class="form-control" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach($groups as $g)
                  <option value="{{ $g->id }}" {{ request('group_id')==$g->id?'selected':'' }}>{{ $g->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Gender</label>
              <select name="gender" class="form-control">
                <option value="">All</option>
                <option value="male" {{ request('gender')==='male'?'selected':'' }}>Male</option>
                <option value="female" {{ request('gender')==='female'?'selected':'' }}>Female</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-2">
              <label>Religion</label>
              <select name="religion" class="form-control">
                <option value="">All</option>
                <option value="islam" {{ request('religion')==='islam'?'selected':'' }}>Islam</option>
                <option value="hindu" {{ request('religion')==='hindu'?'selected':'' }}>Hindu</option>
                <option value="buddhist" {{ request('religion')==='buddhist'?'selected':'' }}>Buddhist</option>
                <option value="christian" {{ request('religion')==='christian'?'selected':'' }}>Christian</option>
                <option value="other" {{ request('religion')==='other'?'selected':'' }}>Other</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Name/Roll/ID</label>
              <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name, roll, ID">
            </div>
            <div class="form-group col-md-3 align-self-end">
              <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
              <a href="{{ route('teacher.institute.directory.students', $school) }}" class="btn btn-secondary">Reset</a>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <div id="students-error" class="alert alert-danger d-none small py-1 px-2 mb-2"></div>
          <div id="students-loading" class="text-center d-none mb-2">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
          <table class="table table-striped table-bordered table-sm mb-0 align-middle">
            <thead>
              <tr>
                <th style="width:50px">#</th>
                <th style="width:90px">আইডি</th>
                <th style="width:150px">বাংলা নাম</th>
                <th style="width:150px">English Name</th>
                <th style="width:80px">শ্রেণি</th>
                <th style="width:80px">শাখা</th>
                <th style="width:80px">গ্রুপ</th>
                <th style="width:65px">রোল</th>
                <th style="width:120px">অভিভাবকের মোবাইল</th>
                <th style="width:70px">ছবি</th>
                <th style="width:80px">প্রোফাইল</th>
              </tr>
            </thead>
            <tbody id="students-table-body">
              @include('teacher.directory.partials.student-rows',['students'=>$students,'school'=>$school])
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer" id="students-pagination">
        @include('teacher.directory.partials.student-pagination',['students'=>$students])
      </div>
    </div>
  </div>
</section>
@push('scripts')
<script>
  (function(){
    const form = document.querySelector('form');
    const tableBody = document.getElementById('students-table-body');
    const paginationDiv = document.getElementById('students-pagination');
    const loading = document.getElementById('students-loading');
    const errorBox = document.getElementById('students-error');
    let debounceTimer;

    function buildParams(url){
      const params = new URLSearchParams(new FormData(form));
      return url.split('?')[0] + '?' + params.toString();
    }

    function loadData(pageUrl){
      const url = pageUrl ? pageUrl : buildParams(form.action || window.location.href);
      errorBox.classList.add('d-none');
      loading.classList.remove('d-none');
      fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(data=>{
          tableBody.innerHTML = data.rows;
          paginationDiv.innerHTML = data.pagination;
          attachPaginationLinks();
          loading.classList.add('d-none');
        })
        .catch(()=>{
          loading.classList.add('d-none');
          errorBox.textContent = 'Failed to load data';
          errorBox.classList.remove('d-none');
        });
    }

    function attachPaginationLinks(){
      paginationDiv.querySelectorAll('a[href]')?.forEach(a=>{
        a.addEventListener('click', function(e){
          e.preventDefault();
          const href = this.getAttribute('href');
          if(!href) return;
          loadData(href);
        });
      });
    }

    form.querySelectorAll('select[name], input[name]')?.forEach(el=>{
      if(el.tagName === 'SELECT'){
        el.addEventListener('change', ()=> loadData());
      } else {
        el.addEventListener('keyup', ()=>{
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(()=> loadData(), 300);
        });
      }
    });

    attachPaginationLinks();

    // Photo modal logic
    function ensureModal(){
      if(document.getElementById('photoPreviewModal')) return;
      const modalHtml = `\n<div class="modal fade" id="photoPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">\n    <div class="modal-content">\n      <div class="modal-body p-1 text-center">\n        <img src="" id="photoPreviewImg" style="max-width:100%;height:auto;border-radius:4px;" alt="Student Photo">\n      </div>\n      <div class="modal-footer py-1">\n        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">বন্ধ করুন</button>\n      </div>\n    </div>\n  </div>\n</div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    ensureModal();
    document.addEventListener('click', function(e){
      const a = e.target.closest('a.student-photo-view');
      if(!a) return;
      e.preventDefault();
      const src = a.getAttribute('data-src');
      ensureModal();
      document.getElementById('photoPreviewImg').setAttribute('src', src);
      $('#photoPreviewModal').modal('show');
    });
  })();
</script>
@endpush
@endsection
