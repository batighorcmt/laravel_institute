@extends('layouts.admin')
@section('title','শিক্ষক ব্যবস্থাপনা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-tie mr-1"></i> শিক্ষক ব্যবস্থাপনা</h1>
</div>


<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>সকল শিক্ষক</div>
    <div>
      <a href="{{ route('principal.institute.teachers.create', $school) }}" class="btn btn-sm btn-primary">নতুন শিক্ষক যুক্ত করুন</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width:80px">সিরিয়াল</th>
                <th>নাম</th>
                <th>মোবাইল</th>
                <th>ইউজারনেম</th>
                <th>পাসওয়ার্ড</th>
                <th>পদবী</th>
                <th style="width:140px">কার্য</th>
              </tr>
            </thead>
            <tbody>
              @forelse($teachers as $t)
                <tr>
                  <td>{{ $t->serial_number }}</td>
                  <td>{{ $t->first_name }} {{ $t->last_name }}</td>
                  <td>{{ $t->phone }}</td>
                  <td>
                    @if($t->user && $t->user->username)
                      <code>{{ $t->user->username }}</code>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($t->plain_password)
                      <code class="text-danger">{{ $t->plain_password }}</code>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>{{ $t->designation }}</td>
                  <td>
                    @php($isPrincipalUser = isset($principalUserIds) && in_array($t->user_id, $principalUserIds))
                    @if($isPrincipalUser)
                      @php($currentUser = Auth::user())
                      @if($currentUser && $currentUser->id === $t->user_id)
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('principal.institute.teachers.edit', [$school, $t->id]) }}">সম্পাদনা</a>
                      @endif
                      <span class="badge badge-info">Principal</span>
                    @else
                      <a class="btn btn-sm btn-outline-secondary" href="{{ route('principal.institute.teachers.edit', [$school, $t->id]) }}">সম্পাদনা</a>
                      <form action="{{ route('principal.institute.teachers.destroy', [$school, $t->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('মুছতে নিশ্চিত?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">মুছুন</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted">কোনো শিক্ষক পাওয়া যায়নি</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
