@foreach($students as $i => $en)
<tr>
  <td>{{ $students->firstItem() + $i }}</td>
  <td>{{ $en->student_code }}</td>
  <td>{{ $en->student_name_bn ?: '-' }}</td>
  <td>{{ $en->student_name_en ?: '-' }}</td>
  <td>{{ $en->class_name }}</td>
  <td>{{ $en->section_name ?: '-' }}</td>
  <td>{{ $en->group_name ?: '-' }}</td>
  <td>{{ $en->roll_no }}</td>
  <td>{{ $en->guardian_phone ?: '-' }}</td>
  <td>
    @php
      $photoFile = $en->photo ?? null;
      $photoUrl = asset('images/default-avatar.svg');
      if($photoFile){
        $paths = [
          'storage/students/'.$photoFile,
          'storage/'.$photoFile,
          $photoFile,
        ];
        foreach($paths as $p){
          if(file_exists(public_path($p))){
            $photoUrl = asset($p);
            break;
          }
        }
      }
    @endphp
    <a href="#" class="student-photo-view" data-src="{{ $photoUrl }}" title="View larger" style="display:inline-block">
      <img src="{{ $photoUrl }}" alt="Photo" class="img-circle" style="width:40px;height:40px;object-fit:cover;">
    </a>
  </td>
  <td>
    <a href="{{ route('teacher.institute.directory.students.show', [$school, $en->student_pk]) }}" class="btn btn-xs btn-info">View</a>
  </td>
</tr>
@endforeach
@if($students->isEmpty())
<tr><td colspan="11" class="text-center text-muted">No results</td></tr>
@endif