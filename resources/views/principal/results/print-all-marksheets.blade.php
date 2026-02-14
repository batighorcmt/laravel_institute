@extends('layouts.print')

@section('title', 'Academic Transcripts - Bulk Print')

@push('print_head')
<style>
    .print-header, .fixed-footer, .logo-overlay { display: none !important; } /* Hide default layout elements */
    @page { size: A4 portrait; margin: 10mm; }
    .print-content { font-family: 'Times New Roman', serif; color: #000; position: relative; page-break-after: always; }
    .print-content:last-child { page-break-after: auto; }
    
    /* Watermark / Background Pattern */
    .bg-pattern {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-image: url('{{ asset("images/transcript-bg.png") }}'); /* Placeholder if any */
        opacity: 0.1; z-index: -1; pointer-events: none;
    }

    /* Header */
    .header-section { text-align: center; margin-bottom: 20px; position: relative; }
    .header-section h1 { font-size: 22pt; font-weight: bold; margin: 0; text-transform: uppercase; color: #1a4d2e; } 
    .header-section h2 { font-size: 14pt; margin: 5px 0; font-weight: normal; }
    .header-section .serial-no { position: absolute; top: 0; left: 0; font-size: 10pt; font-weight: bold; }
    .header-logo { width: 80px; height: 80px; margin: 5px auto; display: block; }
    
    .transcript-title { 
        text-align: center; 
        font-size: 18pt; 
        font-weight: bold; 
        color: #800000; /* Dark Red */
        margin: 10px 0; 
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .exam-name-header { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 15px; }

    /* Student Info & Grading Table Container */
    .info-grading-container { display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-start; }
    
    .student-info { flex: 1; font-size: 11pt; line-height: 1.1; }
    .student-info table { width: 100%; border: none; }
    .student-info td { vertical-align: top; padding: 2px 0; }
    .student-info .label { width: 140px; font-weight: normal; }
    .student-info .colon { width: 15px; }
    .student-info .value { font-weight: bold; font-style: italic; }

    .grading-table { width: 220px; border-collapse: collapse; font-size: 9pt; margin-left: 20px; }
    .grading-table th, .grading-table td { border: 1px solid #000; padding: 2px; text-align: center; }
    .grading-table th { background-color: #f0f0f0; }

    /* Main Result Table */
    .result-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
    .result-table th, .result-table td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
    .result-table th { background-color: #f9f9f9; font-weight: bold; }
    .result-table .text-left { text-align: left; padding-left: 8px; }
    .result-table .sub-name { font-weight: bold; }
    
    /* Footer */
    .footer-section { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 11pt; }
    .signature-box { text-align: center; width: 200px; }
    .signature-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-weight: bold; }
    
    .date-publication { margin-top: 20px; font-size: 10pt; font-weight: bold; }
    
    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
    @foreach($results as $result)
        @include('principal.results.partials._marksheet_content', [
            'student' => $result->student, 
            'result' => $result, 
            'school' => $school, 
            'exam' => $exam, 
            'finalSubjects' => $finalSubjects
        ])
    @endforeach
@endsection
