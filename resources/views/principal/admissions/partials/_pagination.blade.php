@php($totalPages = $apps->lastPage())
@php($currentPage = $apps->currentPage())
<nav aria-label="Apps pages">
  <ul class="pagination pagination-sm mb-0">
    @php($baseUrl = route('principal.institute.admissions.applications', $school->id))
    @php($qs = request()->query())
    @php($makeLink = function($page) use($baseUrl,$qs){ $qs['page']=$page; return $baseUrl.'?'.http_build_query($qs); })
    @for($p=1; $p<=min(3,$totalPages); $p++)
      <li class="page-item {{ $currentPage==$p ? 'active' : '' }}"><a class="page-link" href="{{ $makeLink($p) }}">{{ $p }}</a></li>
    @endfor
    @if($currentPage>5)
      <li class="page-item disabled"><span class="page-link">…</span></li>
    @endif
    @for($p=max(4,$currentPage-1); $p<=min($totalPages-3,$currentPage+1); $p++)
      <li class="page-item {{ $currentPage==$p ? 'active' : '' }}"><a class="page-link" href="{{ $makeLink($p) }}">{{ $p }}</a></li>
    @endfor
    @if($currentPage<$totalPages-4)
      <li class="page-item disabled"><span class="page-link">…</span></li>
    @endif
    @for($p=max($totalPages-2,4); $p<=$totalPages; $p++)
      @if($p>=1)
      <li class="page-item {{ $currentPage==$p ? 'active' : '' }}"><a class="page-link" href="{{ $makeLink($p) }}">{{ $p }}</a></li>
      @endif
    @endfor
  </ul>
</nav>
