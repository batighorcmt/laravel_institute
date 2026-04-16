@extends('layouts.print')

@section('title', 'ID Cards — ' . ($publicExamName ?? 'Exam'))

@section('content')
<style>
    @page {
        size: A4;
        margin: 8mm;
    }
    body {
        margin: 0;
        padding: 0;
        background: none !important;
    }
    .print-header { display: none !important; } /* Hide layout default header if any */
    
    .id-card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 4mm;
        justify-content: flex-start;
    }
    
    .id-card {
        width: {{ $orientation == 'landscape' ? '86mm' : '54mm' }};
        height: {{ $orientation == 'landscape' ? '54mm' : '86mm' }};
        border: 1.5px solid #222;
        position: relative;
        overflow: hidden;
        background-color: #fff;
        @if($backgroundData)
        background-image: url('{{ $backgroundData }}');
        background-size: cover;
        background-position: center;
        @endif
        box-sizing: border-box;
        border-radius: 6px;
        page-break-inside: avoid;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .id-card .header {
        text-align: center;
        padding: 4px;
        background: rgba(255, 255, 255, 0.7);
        border-bottom: 1px solid #ddd;
    }
    .school-name { 
        font-size: {{ $orientation == 'landscape' ? '13px' : '11px' }}; 
        font-weight: 800; 
        margin: 0; 
        line-height: 1.1;
        color: #000;
    }
    .exam-label { 
        font-size: 9px; 
        font-weight: 600; 
        color: #c00; 
        text-transform: uppercase;
        margin-top: 1px;
    }
    
    .id-card .body {
        display: flex;
        flex-direction: {{ $orientation == 'landscape' ? 'row' : 'column' }};
        padding: 8px;
        align-items: center;
        @if($orientation == 'landscape')
        height: calc(100% - 65px);
        @endif
    }
    
    .id-card .photo-box {
        margin-bottom: {{ $orientation == 'landscape' ? '0' : '6px' }};
        margin-right: {{ $orientation == 'landscape' ? '12px' : '0' }};
        flex-shrink: 0;
    }
    
    .id-card .photo {
        width: {{ $orientation == 'landscape' ? '20mm' : '22mm' }};
        height: {{ $orientation == 'landscape' ? '24mm' : '26mm' }};
        border: 1.5px solid #444;
        object-fit: cover;
        background: #eee;
    }
    
    .details {
        flex: 1;
        text-align: {{ $orientation == 'landscape' ? 'left' : 'center' }};
        overflow: hidden;
    }
    .details .name { 
        font-size: {{ $orientation == 'landscape' ? '12px' : '11px' }}; 
        font-weight: 800; 
        margin-bottom: 2px; 
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .details .student-id { 
        font-size: 10px; 
        font-weight: 700; 
        color: #333; 
        background: #eee;
        padding: 1px 6px;
        border-radius: 10px;
        display: inline-block;
        margin-bottom: 5px;
    }
    
    .id-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .id-table td {
        padding: 1px 0;
        vertical-align: top;
    }
    .id-table .label { font-weight: 700; width: 35px; }
    
    .footer-sign {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.05);
        border-top: 0.5px solid #aaa;
    }
    .footer-sign span {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
    }

    @media print {
        .id-card {
            -webkit-filter: none;
            filter: none;
        }
    }
</style>

<div class="id-card-container">
    @foreach($students as $student)
    @php
        $pe = $student->publicExams->first(); // Eager loaded for current public_exam_name
        $enrollment = $student->enrollments->first();
    @endphp
    <div class="id-card">
        <div class="header">
            <h1 class="school-name">{{ $school->name }}</h1>
            <div class="exam-label">{{ $publicExamName }} Examinee</div>
        </div>
        
        <div class="body">
            <div class="photo-box">
                <img src="{{ $student->photo_url }}" class="photo" alt="photo">
            </div>
            
            <div class="details">
                <div class="name">{{ $student->student_name_en ?: $student->student_name_bn ?: 'STUDENT NAME' }}</div>
                <div class="student-id">ID: {{ $student->student_id }}</div>
                
                <table class="id-table">
                    <tr>
                        <td class="label">Roll:</td>
                        <td>{{ $pe->roll_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Reg:</td>
                        <td>{{ $pe->reg_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Session:</td>
                        <td>{{ $pe->session ?? '-' }}</td>
                    </tr>
                    @if($enrollment && $enrollment->class)
                    <tr>
                        <td class="label">Class:</td>
                        <td>{{ $enrollment->class->name }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        
        <div class="footer-sign">
            <span>Signature of Principal</span>
        </div>
    </div>
    @endforeach
</div>
@endsection
