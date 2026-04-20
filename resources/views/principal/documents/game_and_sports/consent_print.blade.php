<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <title>অভিভাবকের সম্মতিপত্র - {{ $student->student_name_bn }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
            font-size: 18px;
            color: #222;
            line-height: 1.8;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 25mm 20mm;
            margin: 0 auto;
            position: relative;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .title {
            font-size: 32px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 40px;
            display: inline-block;
        }
        .content-body {
            text-align: justify;
            margin-top: 50px;
            text-indent: 50px;
        }
        .footer {
            margin-top: 150px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 40px;
        }
        .signature-box {
            text-align: center;
            width: 250px;
        }
        .signature-line {
            border-top: 1px dashed #444;
            margin-bottom: 5px;
        }
        .data-value {
            font-weight: 600;
            border-bottom: 1px dotted #ccc;
            padding: 0 5px;
            color: #000;
        }
        
        @media print {
            body { margin: 0; }
            .page { 
                margin: 0; 
                border: none;
                width: 100%;
                height: 100%;
            }
            .no-print { display: none; }
        }

        /* Styling for the print button container */
        .print-btn-strip {
            background: #343a40;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
        }
        .btn-print {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-print:hover { background: #218838; }
        
        /* Adjust page for top strip */
        @media screen {
            body { background: #e0e0e0; padding-top: 60px; }
            .page { background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        }
    </style>
</head>
<body>

<div class="print-btn-strip no-print">
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Consent Letter</button>
</div>

<div class="page">
    <div class="header">
        <div class="title">অভিভাবকের সম্মতিপত্র</div>
    </div>

    <div class="content-body">
        এই মর্মে সম্মতি প্রদান করছি যে, আমি 
        <span class="data-value">{{ $student->father_name_bn ?: $student->guardian_name_bn ?: '..........................................' }}</span> 
        (অভিভাবকের নাম), 
        গ্রামঃ <span class="data-value">{{ $student->present_village ?: '........................' }}</span>, 
        ডাকঘরঃ <span class="data-value">{{ $student->present_post_office ?: '........................' }}</span>, 
        উপজেলাঃ <span class="data-value">{{ $student->present_upazilla ?: 'গাংনী' }}</span>, 
        জেলাঃ <span class="data-value">{{ $student->present_district ?: 'মেহেরপুর' }}</span>। 
        আমার কন্যা/পুত্র 
        <span class="data-value">{{ $student->student_name_bn ?: $student->student_name_en }}</span>-কে 
        <span class="data-value">{{ $school->name_bn ?: $school->name }}</span>-এর 
        <span class="data-value">{{ $game_name }}</span> খেলায় অংশগ্রহন করিবে। 
        প্রতিযোগিতার প্রয়োজনে সে জেলা, উপ-অঞ্চল, অঞ্চল ও জাতীয় পর্যায়ে অংশ গ্রহণ করিতে পারিবে। 
        আমি অভিভাবক হিসেবে সম্মতি জ্ঞাপন করিলাম।
    </div>
    
    <div style="margin-top: 40px; text-indent: 50px;">
        আমি তার সার্বিক মঙ্গল কামনা করি।
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            প্রতিস্বাক্ষর
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            অভিভাবকের স্বাক্ষর ও তারিখ
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
