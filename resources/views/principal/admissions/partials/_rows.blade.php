@forelse($apps as $app)
<tr>
  <td>{{ ($loop->iteration + ($apps->currentPage()-1)*$apps->perPage()) }}</td>
  <td>{{ $app->class_name }}</td>
  <td>{{ $app->app_id ?: '—' }}</td>
  <td>{{ $app->admission_roll_no ? str_pad($app->admission_roll_no,3,'0',STR_PAD_LEFT) : '—' }}</td>
  <td>{{ $app->name_en ?? $app->applicant_name }}</td>
  <td>{{ $app->father_name_en }}</td>
  <td>{{ $app->mobile }}</td>
  <td>
    <img src="{{ $app->photo ? asset('storage/admission/'.$app->photo) : asset('images/default-avatar.png') }}"
         alt="Photo" style="width:55px;height:70px;object-fit:cover;cursor:pointer" class="rounded border shadow-sm app-photo-thumb"
         data-photo-url="{{ $app->photo ? asset('storage/admission/'.$app->photo) : asset('images/default-avatar.png') }}"
         data-app-name="{{ $app->name_en ?? $app->applicant_name }}">
  </td>
  <td>
    @php
      $parts = [];
      if($app->present_village){
          $v = $app->present_village;
          if($app->present_para_moholla){ $v .= ' ('.$app->present_para_moholla.')'; }
          $parts[] = $v;
      }
      if($app->present_post_office){ $parts[] = $app->present_post_office; }
      if($app->present_upazilla){ $parts[] = $app->present_upazilla; }
      if($app->present_district){ $parts[] = $app->present_district; }
      echo e(implode(', ', $parts) ?: '—');
    @endphp
  </td>
  <td>
      @if($app->accepted_at)
          <span class="badge badge-success">Accepted</span>
          @if($app->student_id)
            <span class="badge badge-info ml-1" title="Enrolled"><i class="fas fa-user-check"></i> Enrolled</span>
          @endif
      @elseif($app->status === 'cancelled')
          <span class="badge badge-danger">Cancelled</span>
      @else
          <span class="badge badge-secondary">Pending</span>
      @endif
  </td>
  <td>
      @if($app->payment_status === 'Paid')
          <span class="badge badge-success">Paid</span>
      @else
          <span class="badge badge-warning text-dark">Unpaid</span>
      @endif
  </td>
  <td>
    <div class="btn-group btn-group-sm" role="group">
      <a href="{{ route('principal.institute.admissions.applications.show', [$school->id, $app->id]) }}" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
      <form action="{{ route('principal.institute.admissions.applications.reset_password', [$school->id, $app->id]) }}" method="post" onsubmit="return confirm('পাসওয়ার্ড রিসেট নিশ্চিত?');" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-warning" title="Reset Password"><i class="fas fa-key"></i></button>
      </form>
      @if(!$app->student_id)
        <a href="{{ route('principal.institute.admissions.applications.edit', [$school->id, $app->id]) }}" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
      @else
        <button class="btn btn-outline-secondary" title="Already Enrolled" disabled><i class="fas fa-edit"></i></button>
      @endif
      @if($app->app_id && $app->payment_status === 'Paid')
        <a href="{{ route('principal.institute.admissions.applications.copy', [$school->id, $app->id]) }}" target="_blank" class="btn btn-outline-info" title="Print Copy"><i class="fas fa-print"></i></a>
      @else
        <button class="btn btn-outline-info" title="{{ $app->payment_status === 'Paid' ? 'Missing App ID' : 'Unpaid – Copy Disabled' }}" disabled><i class="fas fa-print"></i></button>
      @endif
      <a href="{{ route('principal.institute.admissions.applications.payments.details', [$school->id, $app->id]) }}" class="btn btn-outline-dark" title="Payments"><i class="fas fa-receipt"></i></a>
      @if(!$app->accepted_at && $app->status !== 'cancelled' && $app->payment_status==='Paid')
        <form action="{{ route('principal.institute.admissions.applications.accept', [$school->id, $app->id]) }}" method="post" onsubmit="return confirm('Confirm accept?')">
          @csrf
          <button class="btn btn-outline-success" title="Accept"><i class="fas fa-check"></i></button>
        </form>
      @endif
      @if($app->status !== 'cancelled' && !$app->student_id)
        <button type="button" class="btn btn-outline-danger" title="Cancel" data-toggle="modal" data-target="#cancelModal" data-app-id="{{ $app->id }}" data-app-name="{{ $app->name_en ?? $app->applicant_name }}" data-cancel-url="{{ route('principal.institute.admissions.applications.cancel', [$school->id, $app->id]) }}">
          <i class="fas fa-times"></i>
        </button>
      @endif
      @if($app->accepted_at)
        <a href="{{ route('principal.institute.admissions.applications.admit_card', [$school->id, $app->id]) }}" class="btn btn-outline-success" title="Admit Card"><i class="fas fa-id-card"></i></a>
      @endif
    </div>
  </td>
  <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
</tr>
@empty
<tr><td colspan="13" class="text-center text-muted">No applications yet.</td></tr>
@endforelse
