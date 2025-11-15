@extends('layouts.admin')
@section('content')
<div class="p-6">
    <h1 class="text-xl font-semibold mb-4">আবেদন বিস্তারিত</h1>
    @if(session('success'))<div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">{{ session('success') }}</div>@endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-white p-4 border rounded">
        <div><strong>ID:</strong> {{ $application->app_id }}</div>
        <div><strong>নাম (EN):</strong> {{ $application->name_en }}</div>
        <div><strong>নাম (BN):</strong> {{ $application->name_bn }}</div>
        <div><strong>পিতা:</strong> {{ $application->father_name_en }}</div>
        <div><strong>মাতা:</strong> {{ $application->mother_name_en }}</div>
        <div><strong>অভিভাবক:</strong> {{ $application->guardian_name_en ?? '—' }}</div>
        <div><strong>লিঙ্গ:</strong> {{ $application->gender }}</div>
        <div><strong>ধর্ম:</strong> {{ $application->religion ?? '—' }}</div>
        <div><strong>জন্ম তারিখ:</strong> {{ $application->dob }}</div>
        <div><strong>মোবাইল:</strong> {{ $application->mobile }}</div>
        <div><strong>ক্লাস:</strong> {{ $application->class_name ?? '—' }}</div>
        <div><strong>পূর্ববর্তী স্কুল:</strong> {{ $application->last_school ?? '—' }}</div>
        <div><strong>ফলাফল:</strong> {{ $application->result ?? '—' }}</div>
        <div><strong>পাসের বছর:</strong> {{ $application->pass_year ?? '—' }}</div>
        <div><strong>পেমেন্ট:</strong> <span class="font-semibold {{ $application->payment_status==='Paid'?'text-green-600':'text-red-600' }}">{{ $application->payment_status }}</span></div>
        <div><strong>স্ট্যাটাস:</strong> {{ $application->status }} @if($application->accepted_at)<span class="text-green-700">(গৃহীত)</span>@endif</div>
        @if($application->status==='cancelled')
            <div class="md:col-span-2"><strong>বাতিলের কারণ:</strong> <span class="text-red-700">{{ $application->cancellation_reason }}</span></div>
        @endif
    </div>
    <div class="mt-5 flex space-x-4">
        @if(!$application->accepted_at)
            @if($application->payment_status==='Paid' && $application->status!=='cancelled')
            <form action="{{ route('principal.institute.admissions.applications.accept', [$school->id, $application->id]) }}" method="post">
                @csrf
                <button class="bg-blue-600 text-white px-5 py-2 rounded" onclick="return confirm('গ্রহণ নিশ্চিত?')">গ্রহণ করুন</button>
            </form>
            @else
                <div class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded">পেমেন্ট সম্পন্ন না হওয়ায় গ্রহণ সম্ভব নয়</div>
            @endif
        @else
            <a href="{{ route('principal.institute.admissions.applications.admit_card', [$school->id, $application->id]) }}" class="bg-green-600 text-white px-5 py-2 rounded">অ্যাডমিট কার্ড</a>
        @endif
        <a href="{{ route('principal.institute.admissions.applications', $school->id) }}" class="text-gray-700">পেছনে যান</a>
    </div>
</div>
@endsection
