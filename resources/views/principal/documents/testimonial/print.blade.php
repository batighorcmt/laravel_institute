@extends('layouts.print')
@section('title','Testimonial')

@section('content')
<style>
  html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .document-bg { background: @if($setting && $setting->background_path) url('{{ asset('storage/'.$setting->background_path) }}') no-repeat center center / cover @else none @endif; padding: 60px 50px; min-height: 100vh; position: relative; }
  .qr { position: absolute; right: 30px; bottom: 30px; }
  .memo { font-weight: 600; }
</style>
<div class="document-bg">
  <h3 class="text-center mb-4" style="color: {{ $setting->colors['title'] ?? '#111' }}">{{ $document->data['exam_name'] }} Testimonial</h3>
  <p class="memo">স্মারক নং: {{ $document->memo_no }} | তারিখ: {{ $document->issued_at->format('d-m-Y') }}</p>
  <p style="color: {{ $setting->colors['body'] ?? '#333' }}">এতদ্বারা প্রত্যয়ন করা যাচ্ছে যে {{ $student->full_name }} {{ $document->data['session_year'] }} সেশনে {{ $document->data['exam_name'] }} পরীক্ষায় অংশগ্রহণের যোগ্য। রোল: {{ $document->data['roll'] ?? '-' }}, রেজিঃ {{ $document->data['registration'] ?? '-' }}, কেন্দ্র: {{ $document->data['center'] ?? '-' }}।</p>

  <div class="qr">
    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate(route('documents.verify', $document->code)) !!}
    <div class="small text-muted">স্ক্যান করে যাচাই করুন</div>
  </div>
</div>
@endsection
