@extends('layouts.admin')

@section('title', 'Print Marksheet (Placeholder)')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Print Marksheet — Placeholder</h3>
        </div>
        <div class="card-body">
            <p>This is a placeholder view for <strong>principal.results.print-marksheet</strong>.</p>
            <p>Replace this file with the actual marksheet template at:</p>
            <pre>resources/views/principal/results/print-marksheet.blade.php</pre>

            @if(isset($exam))
                <p><strong>Exam:</strong> {{ $exam->name ?? $exam }}</p>
            @endif
            @if(isset($student))
                <p><strong>Student:</strong> {{ $student->student_name_en ?? $student }}</p>
            @endif

            <div class="mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
