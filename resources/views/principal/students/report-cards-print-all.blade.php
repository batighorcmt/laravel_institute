<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সকল রিপোর্ট কার্ড</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'BengaliNumbers';
            src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2');
            unicode-range: U+09E6-09EF;
        }
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --secondary: #6b7280;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'BengaliNumbers', 'Inter', 'Hind Siliguri', sans-serif;
            background: #fff;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 11pt;
        }

        .print-container {
            width: 210mm; /* A4 width */
            margin: auto;
            padding: 15mm;
            background: #fff;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
            padding-bottom: 20px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 1px solid #ddd;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-info h1 {
            font-size: 20pt;
            margin: 0;
            color: #111827;
        }

        .student-info h3 {
            font-size: 11pt;
            margin: 2px 0 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 10pt;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f9fafb;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .summary-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-box {
            flex: 1;
            border: 1px solid #e5e7eb;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .box-label { font-size: 9pt; color: #6b7280; margin-bottom: 2px; }
        .box-value { font-size: 14pt; font-weight: bold; color: #111827; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            text-align: left;
            font-size: 9.5pt;
        }

        table thead th {
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
        }

        .exam-item {
            display: block;
            border: 1px solid #e5e7eb;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            color: inherit;
        }

        .date-range {
            font-size: 9pt;
            color: #6b7280;
            font-weight: normal;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f3f4f6; color: #374151; }

        @media print {
            body { padding: 0; }
            .print-container { width: 100%; padding: 0; margin: 0; }
            .no-print { display: none; }
            .exam-item { page-break-inside: avoid; }
            .section-title { page-break-after: avoid; }
            .page-break { page-break-after: always; }
            .page-break:last-child { page-break-after: avoid; }
        }
    </style>
</head>
<body onload="window.print()">
    @foreach($studentsData as $data)
    <div class="page-break">
        @include('principal.students.report-card-print-content', $data)
    </div>
    @endforeach
</body>
</html>
