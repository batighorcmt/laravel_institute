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
            'margin_top' => 5, 'margin_bottom' => 5, 'margin_left' => 5, 'margin_right' => 5,
            'content_padding_top' => 32,
            'name_font_size' => 11, 'name_color' => '#000000',
            'details_font_size' => 9, 'details_color' => '#333333',
            'row_spacing' => 2,
            'show_principal_signature' => false,
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
        gap: 3mm;
        justify-content: flex-start;
    }
    
    .id-card {
        width: {{ $s->card_width }}mm;
        height: {{ $s->card_height }}mm;
        border: 0.5px solid #ccc;
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
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Content wrapper to skip the background header */
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
    }
    
    .photo {
        width: {{ $s->photo_width }}mm;
        height: {{ $s->photo_height }}mm;
        border: 1px solid #444;
        object-fit: cover;
        background: #eee;
    }
    
    .details {
        width: 100%;
        text-align: center;
    }
    .details .name { 
        font-size: {{ $s->name_font_size }}px; 
        font-weight: 800; 
        margin-bottom: {{ $s->row_spacing }}mm; 
        text-transform: uppercase;
        color: {{ $s->name_color }};
    }
    
    .id-table {
        width: 100%;
        border-collapse: collapse;
        font-size: {{ $s->details_font_size }}px;
        color: {{ $s->details_color }};
    }
    .id-table td {
        padding: {{ $s->row_spacing / 2 }}mm 0;
        vertical-align: top;
    }
    .id-table .label { font-weight: 700; width: 40%; text-align: right; padding-right: 8px; }
    .id-table .val { text-align: left; }
    
    @if($s->show_principal_signature)
    .footer-sign {
        position: absolute;
        bottom: 5mm;
        left: 0;
        right: 0;
        text-align: center;
    }
    .footer-sign span {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        border-top: 0.5px solid #333;
        padding-top: 2px;
    }
    @endif
</style>

<div class="id-card-container">
    @foreach($students as $student)
    @php
        $pe = $student->publicExams->first();
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
                        <td class="label">ID:</td>
                        <td class="val">{{ $student->student_id }}</td>
                    </tr>
                    <tr>
                        <td class="label">Roll:</td>
                        <td class="val">{{ $pe->roll_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Reg:</td>
                        <td class="val">{{ $pe->reg_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Session:</td>
                        <td class="val">{{ $pe->session ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Exam:</td>
                        <td class="val">{{ $publicExamName }} - {{ $pe->exam_year ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($s->show_principal_signature)
        <div class="footer-sign">
            <span>Signature of Principal</span>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
