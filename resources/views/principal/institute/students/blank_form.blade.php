<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শিক্ষার্থী ভর্তির আবেদন ফরম</title>
    <!-- Bootstrap CSS for layout support if needed, though custom CSS is preferred for print -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Google Fonts for Bengali -->
    <link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #333;
            --border-color: #555;
            --bg-color: #fff;
        }

        body {
            font-family: 'Tiro Bangla', serif;
            background-color: #f4f6f9;
            color: var(--primary-color);
            margin: 0;
            padding: 20px;
        }

        /* Screen controls */
        .no-print {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            background: var(--bg-color);
            margin: 0 auto;
            padding: 15mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Header Section */
        .header {
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .school-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .photo-box {
            position: absolute;
            right: 0;
            top: 0;
            width: 35mm;
            height: 45mm;
            border: 1px dashed var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            text-align: center;
            color: #777;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
            padding: 0 100px; /* Space for logo and photo */
        }
        
        .school-address {
            font-size: 14px;
            margin: 0 0 10px 0;
        }
        
        .form-title {
            display: inline-block;
            background-color: var(--primary-color);
            color: #fff !important;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Form Sections */
        .section-title {
            font-size: 16px;
            font-weight: bold;
            background-color: #eee;
            padding: 5px 10px;
            margin: 15px 0 10px 0;
            border-left: 4px solid var(--primary-color);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
            gap: 15px;
        }

        .form-field {
            flex: 1;
            min-width: 150px;
            display: flex;
            flex-direction: column;
        }

        .form-field.full {
            flex: 100%;
        }
        
        .form-field.half {
            flex: 0 0 calc(50% - 7.5px);
        }
        
        .form-field.third {
            flex: 0 0 calc(33.333% - 10px);
        }

        .label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .value-box {
            border: 1px solid var(--border-color);
            height: 30px;
            padding: 5px;
            border-radius: 3px;
            background-color: #fafafa;
        }
        
        .value-line {
            border-bottom: 1px dotted var(--border-color);
            height: 25px;
            margin-top: 5px;
        }

        /* Checkbox groups */
        .checkbox-group {
            display: flex;
            gap: 15px;
            align-items: center;
            height: 30px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .box {
            width: 14px;
            height: 14px;
            border: 1px solid var(--primary-color);
            display: inline-block;
        }

        /* Table */
        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.custom-table th, table.custom-table td {
            border: 1px solid var(--border-color);
            padding: 8px;
            text-align: left;
        }
        table.custom-table th {
            background-color: #f5f5f5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Signatures */
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px dashed var(--primary-color);
            padding-top: 5px;
            margin-top: 40px;
        }

        /* Address Box */
        .address-box {
            border: 1px solid var(--border-color); 
            padding: 10px; 
            border-radius: 3px; 
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .addr-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-end;
        }
        .addr-row:last-child {
            margin-bottom: 0;
        }
        .addr-row span {
            white-space: nowrap;
            margin-right: 5px;
            font-size: 14px;
        }
        .dotted-line {
            flex-grow: 1;
            border-bottom: 1px dotted var(--border-color);
            margin-right: 10px;
            position: relative;
            top: -4px;
        }
        .dotted-line:last-child {
            margin-right: 0;
        }

        /* Print Media Queries */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                height: 100%;
                padding: 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Controls -->
    <div class="no-print">
        <div>
            <h4 class="m-0">শিক্ষার্থী ভর্তির আবেদন ফরম</h4>
            <small class="text-muted">এই ফরমটি প্রিন্ট করে শিক্ষার্থীদের মাঝে বিতরণ করা যাবে।</small>
        </div>
        <div>
            <a href="{{ route('principal.institute.students.index', $school) }}" class="btn btn-secondary">ফিরে যান</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট করুন</button>
        </div>
    </div>

    <div class="page-container">
        <!-- Header -->
        <div class="header">
            @if($school->logo)
                <img src="{{ Storage::url($school->logo) }}" alt="Logo" class="school-logo">
            @else
                <div class="school-logo" style="border: 1px solid #ccc; display:flex; align-items:center; justify-content:center; font-size:10px;">Logo</div>
            @endif
            
            <h1 class="school-name">{{ $school->name_bn ?: $school->name }}</h1>
            <p class="school-address">{{ $school->address_bn ?? $school->address }}</p>
            
            <div class="form-title">ভর্তির আবেদন ফরম</div>
            
            <div class="photo-box">
                পাসপোর্ট সাইজ<br>ছবি<br>(১ কপি)
            </div>
        </div>

        <!-- Official Info -->
        <div class="section-title">অফিসিয়াল তথ্য (অফিস ব্যবহারের জন্য)</div>
        <div class="form-row">
            <div class="form-field">
                <div class="label">শিক্ষাবর্ষ:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field">
                <div class="label">শ্রেণি:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field">
                <div class="label">শাখা:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field">
                <div class="label">গ্রুপ:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field">
                <div class="label">ভর্তি রোল:</div>
                <div class="value-line"></div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="section-title">ব্যক্তিগত তথ্য</div>
        <div class="form-row">
            <div class="form-field full">
                <div class="label">শিক্ষার্থীর নাম (বাংলায়):</div>
                <div class="value-box"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-field full">
                <div class="label">শিক্ষার্থীর নাম (ইংরেজিতে, বড় অক্ষরে):</div>
                <div class="value-box"></div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-field third">
                <div class="label">জন্ম তারিখ:</div>
                <div class="value-box"></div>
            </div>
            <div class="form-field third">
                <div class="label">লিঙ্গ:</div>
                <div class="checkbox-group">
                    <div class="checkbox-item"><div class="box"></div> ছেলে</div>
                    <div class="checkbox-item"><div class="box"></div> মেয়ে</div>
                </div>
            </div>
            <div class="form-field third">
                <div class="label">ধর্ম:</div>
                <div class="checkbox-group">
                    <div class="checkbox-item"><div class="box"></div> ইসলাম</div>
                    <div class="checkbox-item"><div class="box"></div> হিন্দু</div>
                    <div class="checkbox-item"><div class="box"></div> অন্যান্য</div>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-field half">
                <div class="label">রক্তের গ্রুপ:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field half">
                <div class="label">বোর্ড রেজিস্ট্রেশন নং (যদি থাকে):</div>
                <div class="value-line"></div>
            </div>
        </div>

        <!-- Parent Info -->
        <div class="section-title">পিতা-মাতা ও অভিভাবকের তথ্য</div>
        <div class="form-row">
            <div class="form-field half">
                <div class="label">পিতার নাম (বাংলায়):</div>
                <div class="value-box"></div>
            </div>
            <div class="form-field half">
                <div class="label">পিতার নাম (ইংরেজিতে):</div>
                <div class="value-box"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-field half">
                <div class="label">মাতার নাম (বাংলায়):</div>
                <div class="value-box"></div>
            </div>
            <div class="form-field half">
                <div class="label">মাতার নাম (ইংরেজিতে):</div>
                <div class="value-box"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-field third">
                <div class="label">অভিভাবকের নাম (পিতা/মাতা অবর্তমানে):</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field third">
                <div class="label">অভিভাবকের সাথে সম্পর্ক:</div>
                <div class="value-line"></div>
            </div>
            <div class="form-field third">
                <div class="label">মোবাইল নম্বর:</div>
                <div class="value-line"></div>
            </div>
        </div>

        <!-- Address -->
        <div class="section-title">ঠিকানা</div>
        <div class="form-row">
            <div class="form-field half">
                <div class="label">বর্তমান ঠিকানা:</div>
                <div class="address-box">
                    <div class="addr-row"><span>গ্রাম/মহল্লা:</span><span class="dotted-line"></span></div>
                    <div class="addr-row">
                        <span>ডাকঘর:</span><span class="dotted-line"></span>
                        <span>উপজেলা:</span><span class="dotted-line"></span>
                    </div>
                    <div class="addr-row"><span>জেলা:</span><span class="dotted-line"></span></div>
                </div>
            </div>
            <div class="form-field half">
                <div class="label">স্থায়ী ঠিকানা:</div>
                <div class="address-box">
                    <div class="addr-row"><span>গ্রাম/মহল্লা:</span><span class="dotted-line"></span></div>
                    <div class="addr-row">
                        <span>ডাকঘর:</span><span class="dotted-line"></span>
                        <span>উপজেলা:</span><span class="dotted-line"></span>
                    </div>
                    <div class="addr-row"><span>জেলা:</span><span class="dotted-line"></span></div>
                </div>
            </div>
        </div>

        <!-- Previous Education -->
        <div class="section-title">পূর্ববর্তী শিক্ষা প্রতিষ্ঠানের বিবরণ (যদি থাকে)</div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>প্রতিষ্ঠানের নাম</th>
                    <th>শ্রেণি/পরীক্ষা</th>
                    <th>পাসের বছর</th>
                    <th>ফলাফল/জিপিএ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="height: 35px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">আবেদনকারীর স্বাক্ষর ও তারিখ</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">অভিভাবকের স্বাক্ষর ও তারিখ</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">প্রধান শিক্ষকের স্বাক্ষর</div>
            </div>
        </div>

    </div>

    <!-- FontAwesome for Print Icon -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
