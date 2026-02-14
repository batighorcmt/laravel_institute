@extends('layouts.print')

@section('title', 'Academic Transcript')

@push('print_head')
<style>
    .print-header, .fixed-footer, .logo-overlay { display: none !important; }
    @page { size: A4 portrait; margin: 10mm; }
    .print-content { font-family: 'Times New Roman', serif; color: #000; position: relative; }
    
    .bg-pattern {
        position: absolute; top: 15%; left: 10%; width: 80%; height: 60%;
        background-image: url('{{ $school->logo ? asset("storage/".$school->logo) : "" }}');
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        opacity: 0.08; z-index: -1; pointer-events: none;
    }

    .result-status-green { color: #28a745; font-size: 14pt; font-weight: bold; }
    .result-status-red { color: #dc3545; font-size: 14pt; font-weight: bold; }

    /* Header */
    .header-section { margin-bottom: 10px; position: relative; }
    .header-main { display: flex !important; align-items: center; justify-content: center; gap: 20px; text-align: center; }
    .header-logo { width: 70px; height: 70px; margin: 0; flex-shrink: 0; }
    .header-text { text-align: center; }
    .header-section h1 { font-size: 20pt; font-weight: bold; margin: 0; text-transform: uppercase; color: #1a4d2e; line-height: 1.2; } 
    .header-section h2 { font-size: 11pt; margin: 2px 0; font-weight: normal; }
    
    .transcript-title { 
        text-align: center; 
        font-size: 15pt; 
        font-weight: bold; 
        color: #800000; /* Dark Red */
        margin: 5px 0; 
        text-transform: uppercase;
        border: 1px solid #000;
        display: inline-block;
        padding: 2px 20px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
    }

    .exam-name-header { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 5px; }

    /* Student Info & Grading */
    .info-grading-container { display: flex; justify-content: space-between; margin-bottom: 5px; align-items: flex-start; gap: 10px; }
    
    .student-info { flex: 1; font-size: 9.5pt; line-height: 1.3; }
    .student-info table { width: 100%; border: none !important; }
    .student-info td { vertical-align: top; padding: 1px 0; border: none !important; }
    .student-info .label { width: 120px; font-weight: normal; }
    .student-info .colon { width: 10px; }
    .student-info .value { font-weight: bold; font-style: italic; }

    .grading-table { width: 200px; border-collapse: collapse; font-size: 7.5pt; }
    .grading-table th, .grading-table td { border: 1px solid #000; padding: 1px; text-align: center; }
    .grading-table th { background-color: #f0f0f0; }

    /* Main Result Table */
    .result-table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 9.5pt; }
    .result-table th, .result-table td { border: 1px solid #000; padding: 1px; text-align: center; vertical-align: middle; }
    .result-table th { background-color: #f4f4f4; font-weight: bold; padding: 2px; font-size: 9pt; }
    .result-table .text-left { text-align: left; padding-left: 5px; }
    .result-table .sub-name { font-weight: bold; }
    
    /* Cards */
    .summary-cards { display: flex; justify-content: space-around; gap: 10px; margin: 10px 0; }
    .card-item { flex: 1; border: 1px dashed #444; padding: 5px 10px; text-align: center; background: #fff; }
    .card-highlight { font-size: 14pt; font-weight: bold; color: #000; background-color: #ffff00; padding: 2px 15px; display: inline-block; margin-top: 3px; -webkit-print-color-adjust: exact; }
    .card-label { font-size: 8.5pt; font-weight: bold; color: #333; text-transform: uppercase; margin-bottom: 2px; }

    /* Footer */
    .footer-section { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 9.5pt; }
    .signature-box { text-align: center; width: 180px; }
    .signature-line { border-top: 1px solid #000; margin-top: 5px; padding-top: 2px; font-weight: bold; }
    
    .date-publication { margin-top: 10px; font-size: 8.5pt; font-weight: bold; }

    /* Extra activities */
    .extra-activities-table table td { border: 1px solid #000 !important; }
    .extra-activities-table th { font-weight: bold; text-transform: uppercase; border: 1px solid #000 !important; }
    
    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
    @include('principal.results.partials._marksheet_content', ['student' => $student, 'result' => $result, 'school' => $school, 'exam' => $exam, 'finalSubjects' => $finalSubjects])
@endsection
