<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Details - {{ $exam->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { 
            background: #f8f9fa; 
            color: #212529; 
            font-family: 'Segoe UI', Roboto, "Helvetica Neue", Arial, sans-serif; 
            font-size: 13px;
        }
        
        .print-container {
            max-width: 1000px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        /* Header Styles */
        .school-header {
            text-align: center;
            border-bottom: 2px solid #343a40;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .school-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: #343a40;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .school-header h4 {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            margin: 0;
            background: #e9ecef;
            display: inline-block;
            padding: 4px 15px;
            border-radius: 20px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .info-item {
            display: flex;
            align-items: center;
            font-size: 13px;
        }
        .info-label {
            font-weight: 600;
            width: 140px;
            color: #495057;
        }
        .info-value {
            color: #212529;
            font-weight: 500;
        }

        /* Status Badge */
        .status-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid currentColor;
        }
        .status-active { color: #28a745; background: #e8f5e9; }
        .status-completed { color: #17a2b8; background: #e0f7fa; }
        .status-cancelled { color: #dc3545; background: #ffebee; }
        .status-draft { color: #6c757d; background: #f8f9fa; }

        /* Table Styles */
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
            padding-left: 8px;
        }
        
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .custom-table th, .custom-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .custom-table thead th {
            background-color: #343a40;
            color: #fff;
            font-weight: 500;
            text-align: center;
            border-color: #454d55;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .custom-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .custom-table tbody tr:hover {
            background-color: #e9ecef;
        }
        .marks-cell {
            text-align: center;
            font-family: monospace;
            font-size: 13px;
        }
        .pass-mark { color: #6c757d; font-size: 11px; }

        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.9);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            gap: 8px;
        }

        @page { size: auto; margin: 12mm; }
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .print-container { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
            .no-print, .print-controls { display: none !important; }
            .info-grid { background: #fff !important; border: 1px solid #ccc; padding: 10px; }
            .school-header h4 { background: #fff !important; border: 1px solid #343a40; }
            .custom-table th { background-color: #f0f0f0 !important; color: #000 !important; border: 1px solid #666 !important; }
            .custom-table td { border: 1px solid #666 !important; }
            .custom-table tbody tr:nth-child(even) { background-color: #fafafa !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Floating Action Buttons -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-sm btn-dark shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="mr-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg> Portrait
        </button>
        <button onclick="printLandscape()" class="btn btn-sm btn-outline-dark shadow-sm">
            Landscape
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-light shadow-sm border">Close</button>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="school-header">
            <h3>{{ $school->name }}</h3>
            <h4>{{ $exam->name }} - Exam Details</h4>
        </div>
        
        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Exam Name:</span>
                <span class="info-value">{{ $exam->name }} {{ $exam->name_bn ? '('.$exam->name_bn.')' : '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Start Date:</span>
                <span class="info-value">{{ $exam->start_date ? $exam->start_date->format('d M, Y') : 'N/A' }}</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Class & Session:</span>
                <span class="info-value">{{ $exam->class->name ?? 'N/A' }} ({{ $exam->academicYear->name ?? 'N/A' }})</span>
            </div>
            <div class="info-item">
                <span class="info-label">End Date:</span>
                <span class="info-value">{{ $exam->end_date ? $exam->end_date->format('d M, Y') : 'N/A' }}</span>
            </div>

            <div class="info-item">
                <span class="info-label">Exam Type:</span>
                <span class="info-value">{{ $exam->exam_type ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    @php
                        $statusClass = 'status-draft';
                        if($exam->status == 'active') $statusClass = 'status-active';
                        elseif($exam->status == 'completed') $statusClass = 'status-completed';
                        elseif($exam->status == 'cancelled') $statusClass = 'status-cancelled';
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ ucfirst($exam->status) }}
                    </span>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Total Subjects (No 4th):</span>
                <span class="info-value">{{ $exam->total_subjects_without_fourth ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Public Format:</span>
                <span class="info-value">{{ $exam->publicExam->short_name ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Subjects Table -->
        <div class="section-title">Assigned Subjects ({{ $exam->examSubjects->count() }})</div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%" class="text-left">Subject</th>
                    <th width="10%">Group</th>
                    <th width="15%" class="text-left">Teacher</th>
                    <th width="10%">CQ<br><span style="font-size:10px;font-weight:normal">(Full / Pass)</span></th>
                    <th width="10%">MCQ<br><span style="font-size:10px;font-weight:normal">(Full / Pass)</span></th>
                    <th width="10%">Prac<br><span style="font-size:10px;font-weight:normal">(Full / Pass)</span></th>
                    <th width="10%">Total<br><span style="font-size:10px;font-weight:normal">(Full / Pass)</span></th>
                    <th width="15%">Schedule & Deadline</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exam->examSubjects->sortBy('display_order') as $es)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <strong style="color: #0056b3;">{{ $es->subject->name ?? 'N/A' }}</strong>
                            @if($es->subject && $es->subject->code)
                                <div style="font-size: 10px; color: #6c757d;">Code: {{ $es->subject->code }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $es->combine_group ?: '-' }}</td>
                        <td>{{ $es->teacher->name ?? 'N/A' }}</td>
                        
                        <td class="marks-cell">
                            {{ $es->creative_full_mark }}<br><span class="pass-mark">({{ $es->creative_pass_mark }})</span>
                        </td>
                        <td class="marks-cell">
                            {{ $es->mcq_full_mark }}<br><span class="pass-mark">({{ $es->mcq_pass_mark }})</span>
                        </td>
                        <td class="marks-cell">
                            {{ $es->practical_full_mark }}<br><span class="pass-mark">({{ $es->practical_pass_mark }})</span>
                        </td>
                        <td class="marks-cell" style="background:#f1f3f5; font-weight:bold;">
                            {{ $es->total_full_mark }}<br><span class="pass-mark" style="font-weight:normal">({{ $es->total_pass_mark }})</span>
                        </td>
                        
                        <td style="font-size:11px; line-height:1.4;">
                            @if($es->exam_date)
                                <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($es->exam_date)->format('d M y') }}</div>
                            @endif
                            @if($es->exam_start_time)
                                <div><strong>Time:</strong> {{ date('h:i A', strtotime($es->exam_start_time)) }}</div>
                            @endif
                            @if($es->mark_entry_deadline)
                                <div style="color:#d32f2f;"><strong>Deadline:</strong> {{ \Carbon\Carbon::parse($es->mark_entry_deadline)->format('d M y') }}</div>
                            @endif
                            @if(!$es->exam_date && !$es->exam_start_time && !$es->mark_entry_deadline)
                                <div class="text-center text-muted">-</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <em>No subjects have been added to this exam yet.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function printLandscape() {
            var st = document.createElement('style');
            st.id = 'landscapeStyle';
            st.media = 'print';
            st.appendChild(document.createTextNode('@page { size: landscape; }'));
            document.head.appendChild(st);
            window.print();
            setTimeout(function(){ var s=document.getElementById('landscapeStyle'); if (s) s.remove(); }, 1000);
        }
    </script>
</body>
</html>
