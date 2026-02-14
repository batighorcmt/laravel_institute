@extends('layouts.print')

@section('title', 'Academic Transcript')

@push('print_head')
<style>
    .print-header, .fixed-footer, .logo-overlay { display: none !important; }
    @page { size: A4 portrait; margin: 10mm; }
    .print-content { font-family: 'Times New Roman', serif; color: #000; position: relative; }
    
    /* Watermark / Background Pattern */
    .bg-pattern {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-image: url('{{ asset("images/transcript-bg.png") }}'); /* Placeholder if any */
        opacity: 0.1; z-index: -1; pointer-events: none;
    }

    /* Header */
    .header-section { margin-bottom: 10px; position: relative; }
    .header-main { display: flex; align-items: center; justify-content: center; gap: 20px; }
    .header-logo { width: 70px; height: 70px; margin: 0; }
    .header-text { text-align: center; }
    .header-section h1 { font-size: 20pt; font-weight: bold; margin: 0; text-transform: uppercase; color: #1a4d2e; line-height: 1.2; } 
    .header-section h2 { font-size: 12pt; margin: 2px 0; font-weight: normal; }
    .header-section .serial-no { position: absolute; top: 0; left: 0; font-size: 9pt; font-weight: bold; }
    
    .transcript-title { 
        text-align: center; 
        font-size: 16pt; 
        font-weight: bold; 
        color: #800000; /* Dark Red */
        margin: 5px 0; 
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .exam-name-header { text-align: center; font-size: 12pt; font-weight: bold; margin-bottom: 10px; }

    /* Student Info & Grading Table Container */
    .info-grading-container { display: flex; justify-content: space-between; margin-bottom: 5px; align-items: flex-start; }
    
    .student-info { flex: 1; font-size: 10pt; line-height: 1.4; }
    .student-info table { width: 100%; border: none; }
    .student-info td { vertical-align: top; padding: 1px 0; }
    .student-info .label { width: 130px; font-weight: normal; }
    .student-info .colon { width: 15px; }
    .student-info .value { font-weight: bold; font-style: italic; }

    .grading-table { width: 220px; border-collapse: collapse; font-size: 8pt; margin-left: 20px; }
    .grading-table th, .grading-table td { border: 1px solid #000; padding: 1px; text-align: center; }
    .grading-table th { background-color: #f0f0f0; }

    /* Main Result Table */
    .result-table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10pt; }
    .result-table th, .result-table td { border: 1px solid #000; padding: 3px; text-align: center; vertical-align: middle; }
    .result-table th { background-color: #f9f9f9; font-weight: bold; padding: 4px; }
    .result-table .text-left { text-align: left; padding-left: 8px; }
    .result-table .sub-name { font-weight: bold; }
    
    /* Footer */
    .footer-section { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 10pt; }
    .signature-box { text-align: center; width: 180px; }
    .signature-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 3px; font-weight: bold; }
    
    .date-publication { margin-top: 15px; font-size: 9pt; font-weight: bold; }
    
    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
    @include('principal.results.partials._marksheet_content', ['student' => $student, 'result' => $result, 'school' => $school, 'exam' => $exam, 'finalSubjects' => $finalSubjects])
@endsection
