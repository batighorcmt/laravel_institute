@extends('layouts.admin')
@section('content')
<div class="p-6 print:p-0">
    <div class="bg-white border rounded p-6 max-w-3xl mx-auto print:border-0 print:shadow-none">
        <h1 class="text-2xl font-bold text-center mb-2">অ্যাডমিট কার্ড</h1>
        <h2 class="text-lg text-center mb-4">{{ $school->name }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><strong>Application ID:</strong> {{ $application->app_id }}</div>
            <div><strong>Name (EN):</strong> {{ $application->name_en }}</div>
            <div><strong>Name (BN):</strong> {{ $application->name_bn }}</div>
            <div><strong>Father:</strong> {{ $application->father_name_en }}</div>
            <div><strong>Mother:</strong> {{ $application->mother_name_en }}</div>
            <div><strong>Gender:</strong> {{ $application->gender }}</div>
            <div><strong>DOB:</strong> {{ $application->dob }}</div>
            <div><strong>Class:</strong> {{ $application->class_name ?? '—' }}</div>
        </div>
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-600">এই অ্যাডমিট কার্ডটি পরীক্ষার দিনে সঙ্গে আনতে হবে।</p>
        </div>
        <div class="mt-6 flex justify-between text-sm">
            <div>স্বাক্ষর (প্রধান শিক্ষক)</div>
            <div>স্বাক্ষর (পরীক্ষার্থী)</div>
        </div>
        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" class="bg-gray-800 text-white px-5 py-2 rounded">Print</button>
            <a href="{{ route('principal.institute.admissions.applications.show', [$school->id, $application->id]) }}" class="ml-3 text-blue-600">Back</a>
        </div>
    </div>
</div>
@endsection
