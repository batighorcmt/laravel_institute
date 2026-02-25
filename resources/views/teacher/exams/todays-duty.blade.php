@extends('layouts.admin')

@section('title', "Today's Exam Duty")

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i>আজকের পরীক্ষার দায়িত্ব</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ now()->format('d M Y') }}</span>
                </div>
            </div>
            <div class="card-body">
                @if($duties->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> আজকের জন্য কোনো পরীক্ষার দায়িত্ব নির্ধারিত নেই।
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>পরীক্ষা</th>
                                    <th>কক্ষ</th>
                                    <th>তারিখ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($duties as $i => $duty)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $duty->exam->name ?? '—' }}</td>
                                    <td>{{ $duty->room->name ?? '—' }}</td>
                                    <td>{{ optional($duty->exam)->start_date }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
