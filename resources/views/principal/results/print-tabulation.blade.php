@extends('layouts.print')

@section('title', 'ট্যাবুলেশন শীট - ' . (optional($exam)->name ?? ''))

@section('content')
<div class="container-fluid">
    <div class="text-center mb-3">
        <h2>{{ $school->name ?? 'Institution' }}</h2>
        <h4>{{ optional($exam)->name ?? '' }} @if(optional($class)->name) - {{ $class->name }}@endif</h4>
        @if(request('section_id'))
            <div><strong>Section:</strong> {{ optional($class)->sections()->find(request('section_id'))->name ?? 'N/A' }}</div>
        @endif
    </div>

    @if(isset($examSubjects) && $examSubjects->count()
        && isset($students) && $students->count())
        <table class="table table-bordered table-sm" style="width:100%; font-size:12px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding:6px;">ক্রমিক</th>
                    <th style="padding:6px;">রোল</th>
                    <th style="padding:6px;">নাম</th>
                    @foreach($examSubjects as $es)
                        <th style="padding:6px;">{{ $es->subject->name }}</th>
                    @endforeach
                    <th style="padding:6px;">মোট</th>
                    <th style="padding:6px;">GPA</th>
                    <th style="padding:6px;">গ্রেড</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                    @php $marks = $student->marks ?? collect(); @endphp
                    <tr>
                        <td style="padding:6px;">{{ $loop->iteration }}</td>
                        <td style="padding:6px;">{{ $student->student_id ?? 'N/A' }}</td>
                        <td style="padding:6px;">{{ $student->student_name_en ?? $student->name ?? 'N/A' }}</td>
                        @foreach($examSubjects as $es)
                            @php $m = $marks->get($es->subject_id); @endphp
                            <td style="padding:6px; text-align:center;">
                                @if($m)
                                    @if($m->is_absent)
                                        Ab
                                    @else
                                        {{ number_format($m->total_marks ?? ($m->mcq_marks + $m->creative_marks + ($m->practical_marks ?? 0)), 0) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td style="padding:6px; text-align:center;">{{ number_format($student->result->total_marks ?? 0, 0) }}</td>
                        <td style="padding:6px; text-align:center;">{{ number_format($student->result->gpa ?? 0, 2) }}</td>
                        <td style="padding:6px; text-align:center;">{{ $student->result->letter_grade ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center">কোনো তথ্য পাওয়া যায়নি।</div>
    @endif
</div>
@endsection
