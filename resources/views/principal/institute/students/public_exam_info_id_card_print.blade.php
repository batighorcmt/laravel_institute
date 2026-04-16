@extends('layouts.print')

@section('title', 'ID Cards — ' . ($publicExamName ?? 'Exam'))

@section('content')
@php
    $s = $settings;
    if(!$s) {
        $s = (object)[
            'orientation' => 'portrait',
            'card_width' => 54, 'card_height' => 86,
            'photo_width' => 22, 'photo_height' => 26,
            'margin_top' => 5, 'margin_bottom' => 3, 'margin_left' => 5, 'margin_right' => 5,
            'content_padding_top' => 32,
            'name_font_size' => 12, 'name_color' => '#d32f2f',
            'details_font_size' => 10, 'details_color' => '#000000',
            'row_spacing' => 1.5,
            'show_principal_signature' => true,
            'background_image' => null
        ];
    }
@endphp
<style>
    @page {
        size: A4;
        margin: 5mm;
    }
    body {
        margin: 0;
        padding: 0;
        background: none !important;
    }
    .print-header { display: none !important; }
    
    .id-card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 4mm;
        justify-content: flex-start;
    }
    
    .id-card {
        width: {{ $s->card_width }}mm;
        height: {{ $s->card_height }}mm;
        border: 0.1px solid #eee;
        position: relative;
        overflow: hidden;
        background-color: #fff;
        @if($s->background_image)
        background-image: url('{{ $s->background_image }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        @endif
        box-sizing: border-box;
        page-break-inside: avoid;
        font-family: 'Arial', sans-serif;
        color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .card-content {
        padding-top: {{ $s->content_padding_top }}mm;
        padding-left: {{ $s->margin_left }}mm;
        padding-right: {{ $s->margin_right }}mm;
        padding-bottom: {{ $s->margin_bottom }}mm;
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .photo-box {
        margin-bottom: {{ $s->row_spacing * 2 }}mm;
        flex-shrink: 0;
        padding: 2px;
        background: linear-gradient(45deg, #fbc02d, #f57c00, #d32f2f);
        line-height: 0;
        display: inline-block;
    }
    
    .photo {
        width: {{ $s->photo_width }}mm;
        height: {{ $s->photo_height }}mm;
        object-fit: cover;
        background: #fff;
        display: block;
    }
    
    .details {
        width: 100%;
        text-align: left;
    }
    .details .name { 
        font-size: {{ $s->name_font_size }}px; 
        font-weight: 900; 
        margin-bottom: {{ $s->row_spacing }}mm; 
        text-transform: uppercase;
        color: {{ $s->name_color }};
        text-align: center;
    }
    
    .id-table {
        width: 100%;
        border-collapse: collapse;
        font-size: {{ $s->details_font_size }}px;
        color: {{ $s->details_color }};
        margin-left: 1mm;
    }
    .id-table td {
        padding: {{ $s->row_spacing / 2 }}px 0;
        vertical-align: middle;
    }
    .id-table .label { font-weight: 500; width: 62px; }
    .id-table .val { font-weight: 500; }
    
    .id-footer-row {
        position: absolute;
        bottom: 8mm;
        left: {{ $s->margin_left }}mm;
        right: {{ $s->margin_right }}mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    
    .id-no-wrap {
        font-weight: 900;
        font-size: {{ $s->details_font_size + 1 }}px;
    }
    .id-no-wrap .id-label { color: #000; }
    .id-no-wrap .id-val { color: #d32f2f; margin-left: 5px; }

    .signature-wrap {
        text-align: center;
        width: 25mm;
    }
</style>

<div class="id-card-container">
    @foreach($students as $student)
    @php
        $pe = $student->publicExams->where('exam_name', $publicExamName)->first();
    @endphp
    <div class="id-card">
        <div class="card-content">
            <div class="photo-box">
                <img src="{{ $student->photo_url }}" class="photo" alt="photo">
            </div>
            
            <div class="details">
                <div class="name">{{ $student->student_name_en ?: $student->student_name_bn ?: 'STUDENT NAME' }}</div>
                
                <table class="id-table">
                    <tr>
                        <td class="label">Class:</td>
                        <td class="val">{{ $publicExamName }} - {{ $pe->exam_year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Roll:</td>
                        <td class="val">{{ $pe->roll_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Reg. No:</td>
                        <td class="val">{{ $pe->reg_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Center:</td>
                        <td class="val">{{ $pe->center_name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="id-footer-row">
            <div class="id-no-wrap">
                <span class="id-label">ID No.</span>
                <span class="id-label">:</span>
                <span class="id-val">{{ $student->student_id }}</span>
            </div>
            
            @if($s->show_principal_signature)
            <div class="signature-wrap">
                <div style="font-size: 8px; font-weight: bold; color: #444;">Principal</div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
