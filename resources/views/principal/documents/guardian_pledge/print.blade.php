<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অভিভাবকের অঙ্গীকার নামা প্রিন্ট</title>
    
    <!-- Noto Serif Bengali for Elegant Traditional Print Look -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f1f3f5;
            font-family: 'Noto Serif Bengali', Nikosh, SolaimanLipi, SiyamRupali, serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Preview Container representing the sheet of paper */
        .pledge-page {
            background-color: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: all 0.3s ease;
            page-break-after: always;
            page-break-inside: avoid;
        }

        .pledge-page:last-child {
            page-break-after: avoid;
        }

        /* Content Layout */
        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .school-name {
            font-size: 28px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .school-address {
            font-size: 16px;
            color: #333333;
            margin-bottom: 25px;
        }

        .document-title {
            font-size: 22px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 6px;
            color: #000000;
            display: inline-block;
        }

        .body-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .pledge-text {
            font-size: 19px;
            line-height: 2.2;
            text-align: justify;
            color: #000000;
            margin-bottom: 25px;
        }

        .text-indent {
            text-indent: 50px;
        }

        .text-fill {
            font-weight: 700;
            border-bottom: 2.5px solid #1a6fc4;
            padding: 2px 8px 1px 8px;
            white-space: nowrap;
            color: #0d4f94;
            border-radius: 0 0 3px 3px;
        }

        /* Signatures Grid Layout (2x2) */
        .signatures-grid {
            margin-top: auto;
            padding-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-row-gap: 70px;
            grid-column-gap: 50px;
            width: 100%;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000000;
            width: 220px;
            margin: 0 auto 10px auto;
        }

        .signature-title {
            font-size: 16px;
            font-weight: 700;
            color: #000000;
        }

        /* Floating Settings Drawer UI (Glassmorphism & premium design) */
        .settings-drawer {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 320px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            overflow: hidden;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform-origin: top right;
        }

        .settings-drawer.collapsed {
            transform: scale(0.8);
            opacity: 0;
            pointer-events: none;
        }

        .drawer-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .drawer-header h5 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
        }

        .drawer-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #495057;
            margin-bottom: 6px;
        }

        .form-control-custom {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            outline: none;
            background-color: white;
            transition: border-color 0.2s;
        }

        .form-control-custom:focus {
            border-color: #007bff;
        }

        .row-custom {
            display: flex;
            gap: 10px;
        }

        .width-50 {
            width: 50%;
        }

        .btn-print-action {
            display: block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
            transition: all 0.2s;
            margin-top: 20px;
            text-align: center;
        }

        .btn-print-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        /* Toggle Button (when drawer is closed) */
        .settings-toggle-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 9998;
            transition: transform 0.2s;
        }

        .settings-toggle-btn:hover {
            transform: scale(1.05);
        }

        .settings-toggle-btn.hidden {
            display: none;
        }

        /* Print Media Styles */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
            }

            .pledge-page {
                box-shadow: none;
                margin: 0;
                border: none;
                background-color: #ffffff;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

@php
    if (!function_exists('toBanglaNum')) {
        function toBanglaNum($num) {
            $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
            return str_replace($eng, $bn, $num);
        }
    }
@endphp

<!-- Pledge Sheets Loop -->
@foreach($enrollments as $enrollment)
    @php
        $student = $enrollment->student;
        $guardianName = $student->guardian_name_bn ?: ($student->father_name_bn ?: ($student->guardian_name_en ?: ($student->father_name ?: '..............................................................')));
        $className = $class->bangla_name ?: $class->name;
        $yearBn = toBanglaNum($academicYear->name);
        $schoolName = $school->name_bn ?: $school->name;
    @endphp

    <div class="pledge-page">
        <!-- HEADER SECTION -->
        <div class="header">
            <h1 class="school-name">{{ $schoolName }}</h1>
            <p class="school-address">{{ $school->address_bn ?: $school->address }}</p>
            <h3 class="document-title">অভিভাবকের অঙ্গীকার নামা</h3>
        </div>

        <!-- BODY CONTENT SECTION -->
        <div class="body-content">
            <p class="pledge-text text-indent">
                আমি এই মর্মে অঙ্গীকার করছি যে, আমি <span class="text-fill">{{ $student->father_name_bn ?: $student->father_name }}</span> /  <span class="text-fill">{{ $student->mother_name_bn ?: $student->mother_name }}</span>, শ্রেণি: <span class="text-fill">{{ $class->bangla_name ?: $class->name }}</span>, রোল নম্বর: <span class="text-fill">{{ toBanglaNum($enrollment->roll_no) }}</span>। আমার সন্তান <span class="text-fill">{{ $student->student_name_bn ?: $student->student_name_en }}</span> {{ $schoolName }}-এর {{ $yearBn }} সালের {{ $className }} শ্রেণির অর্ধ-বার্ষিক ও বার্ষিক পরীক্ষায় অংশগ্রহণ করবে। আমি তার উক্ত পরীক্ষাসমূহে অংশগ্রহণকল্পে আমার সন্তান ও বিদ্যালয় কর্তৃপক্ষকে সর্বোচ্চ সহযোগিতা করব। এর পরেও যদি আমার সন্তান অর্ধ-বার্ষিক ও বার্ষিক পরীক্ষায় কৃতকার্য না হয় তাহলে তাকে পরবর্তী শ্রেণিতে উন্নীত করার জন্য আমি কোনক্রমেই অত্র প্রতিষ্ঠান কর্তৃপক্ষকে অনুরোধ বা সুপারিশ করব না এবং আমি স্বেচ্ছায় আমার সন্তানকে একই শ্রেণিতে পুনরায় পাঠদানের ব্যবস্থা করাবো।
            </p>
            <p class="pledge-text">
                আমি স্বেচ্ছায় ও স্বজ্ঞানে কারও প্ররোচনা ছাড়া অত্র অঙ্গীকার নামায় স্বাক্ষর করলাম।
            </p>

            <!-- SIGNATURES SECTION -->
            <div class="signatures-grid">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">ছাত্র/ছাত্রীর স্বাক্ষর</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">শ্রেণী শিক্ষকের স্বাক্ষর</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">অভিভাবকের স্বাক্ষর</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">প্রধান শিক্ষকের স্বাক্ষর</div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Floating Settings Overlay Drawer -->
<div class="settings-drawer no-print" id="settingsDrawer">
    <div class="drawer-header">
        <h5><i class="fas fa-sliders-h mr-2"></i>পৃষ্ঠা ও মার্জিন সেটিংস</h5>
        <button type="button" class="close-btn" onclick="toggleDrawer()">&times;</button>
    </div>
    <div class="drawer-body">
        <div class="form-group">
            <label>পৃষ্ঠার পরিমাপ (Page Size)</label>
            <select class="form-control-custom" id="pageSize" onchange="updatePageLayout()">
                <option value="A4">A4 (Portrait)</option>
                <option value="Legal">Legal (Portrait)</option>
            </select>
        </div>
        <div class="form-group">
            <label>মার্জিন ইউনিট</label>
            <select class="form-control-custom" id="marginUnit" onchange="updatePageLayout()">
                <option value="in">Inch (in)</option>
                <option value="mm">Millimeter (mm)</option>
                <option value="cm">Centimeter (cm)</option>
            </select>
        </div>
        <div class="row-custom">
            <div class="form-group width-50">
                <label>উপরের মার্জিন</label>
                <input type="number" class="form-control-custom" id="marginTop" value="0.75" step="0.05" oninput="updatePageLayout()">
            </div>
            <div class="form-group width-50">
                <label>নিচের মার্জিন</label>
                <input type="number" class="form-control-custom" id="marginBottom" value="0.75" step="0.05" oninput="updatePageLayout()">
            </div>
        </div>
        <div class="row-custom">
            <div class="form-group width-50">
                <label>বাম মার্জিন</label>
                <input type="number" class="form-control-custom" id="marginLeft" value="0.75" step="0.05" oninput="updatePageLayout()">
            </div>
            <div class="form-group width-50">
                <label>ডান মার্জিন</label>
                <input type="number" class="form-control-custom" id="marginRight" value="0.75" step="0.05" oninput="updatePageLayout()">
            </div>
        </div>
        
        <button type="button" class="btn-print-action" onclick="window.print()">
            <i class="fas fa-print mr-2"></i>প্রিন্ট / ডাউনলোড করুন
        </button>
    </div>
</div>

<!-- Drawer Toggle Floating Trigger -->
<button class="settings-toggle-btn no-print hidden" id="settingsToggleBtn" onclick="toggleDrawer()">
    <i class="fas fa-cog"></i>
</button>

<script>
    // Handle drawer show/hide
    function toggleDrawer() {
        const drawer = document.getElementById('settingsDrawer');
        const toggleBtn = document.getElementById('settingsToggleBtn');
        
        if (drawer.classList.contains('collapsed')) {
            drawer.classList.remove('collapsed');
            toggleBtn.classList.add('hidden');
        } else {
            drawer.classList.add('collapsed');
            toggleBtn.classList.remove('hidden');
        }
    }

    // Apply Margins and Sizes on fly to Preview and Print output
    function updatePageLayout() {
        const size = document.getElementById('pageSize').value;
        const topMargin = document.getElementById('marginTop').value;
        const bottomMargin = document.getElementById('marginBottom').value;
        const leftMargin = document.getElementById('marginLeft').value;
        const rightMargin = document.getElementById('marginRight').value;
        const unit = document.getElementById('marginUnit').value;

        // Update CSS layout variables for Preview
        const pages = document.querySelectorAll('.pledge-page');
        pages.forEach(page => {
            if (size === 'A4') {
                page.style.width = '210mm';
                page.style.minHeight = '297mm';
            } else {
                page.style.width = '215.9mm'; // Legal 8.5in
                page.style.minHeight = '355.6mm'; // Legal 14in
            }

            page.style.paddingTop = topMargin + unit;
            page.style.paddingBottom = bottomMargin + unit;
            page.style.paddingLeft = leftMargin + unit;
            page.style.paddingRight = rightMargin + unit;
        });

        // Inject dynamic print styling to override page layout when sending to system print
        let styleEl = document.getElementById('dynamic-print-style');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'dynamic-print-style';
            document.head.appendChild(styleEl);
        }

        styleEl.innerHTML = `
            @page {
                size: ${size === 'A4' ? 'A4' : '8.5in 14in'} portrait;
                margin: ${topMargin}${unit} ${rightMargin}${unit} ${bottomMargin}${unit} ${marginLeft}${unit};
            }
            @media print {
                .pledge-page {
                    width: 100% !important;
                    height: auto !important;
                    min-height: 0 !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                    margin-bottom: 0 !important;
                    page-break-after: always !important;
                }
                .pledge-page:last-child {
                    page-break-after: avoid !important;
                }
            }
        `;
    }

    // Initial setup on page load
    document.addEventListener('DOMContentLoaded', function() {
        updatePageLayout();
    });
</script>

</body>
</html>
