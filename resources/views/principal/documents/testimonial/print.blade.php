<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>S.S.C. Testimonial - {{ $school->name }}</title>
<style>
/* ===== PAGE SETUP ===== */
@page {
    size: A4 landscape;
    margin: 0.5in; /* 0.5 inch margin on all sides */
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Times New Roman", "Siyam Rupali", serif;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 10px;
}

/* ===== CERTIFICATE CONTAINER ===== */
.certificate-container {
    width: 29.7cm; /* A4 landscape width */
    height: 21cm; /* A4 landscape height */
    background:
        linear-gradient(rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.98));
    position: relative;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    page-break-inside: avoid;
}

/* ===== UNIQUE AND ATTRACTIVE BORDER ===== */
.certificate {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    flex-direction: column;
}

/* Ornate Border Design */
.certificate::before {
    content: "";
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    bottom: 15px;
    border: 12px double transparent;
    border-image: linear-gradient(45deg, #2a2a8f, #7b2cbf, #0072bc, #b100ff);
    border-image-slice: 1;
    border-radius: 8px;
    z-index: 1;
}

.certificate::after {
    content: "";
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    bottom: 10px;
    border: 2px solid rgba(42, 42, 143, 0.2);
    border-radius: 12px;
    z-index: 1;
}

/* ===== CORNER ORNAMENTS ===== */
.corner-ornament {
    position: absolute;
    width: 80px;
    height: 80px;
    z-index: 2;
}

.top-left-ornament {
    top: 25px;
    left: 25px;
    border-top: 3px solid #7b2cbf;
    border-left: 3px solid #7b2cbf;
    border-top-left-radius: 20px;
}

.top-right-ornament {
    top: 25px;
    right: 25px;
    border-top: 3px solid #0072bc;
    border-right: 3px solid #0072bc;
    border-top-right-radius: 20px;
}

.bottom-left-ornament {
    bottom: 25px;
    left: 25px;
    border-bottom: 3px solid #b100ff;
    border-left: 3px solid #b100ff;
    border-bottom-left-radius: 20px;
}

.bottom-right-ornament {
    bottom: 25px;
    right: 25px;
    border-bottom: 3px solid #2a2a8f;
    border-right: 3px solid #2a2a8f;
    border-bottom-right-radius: 20px;
}

/* Decorative corner elements */
.corner-ornament::before {
    content: "✦";
    position: absolute;
    font-size: 20px;
    color: #7b2cbf;
}

.top-left-ornament::before {
    top: -10px;
    left: -10px;
}

.top-right-ornament::before {
    top: -10px;
    right: -10px;
}

.bottom-left-ornament::before {
    bottom: -10px;
    left: -10px;
}

.bottom-right-ornament::before {
    bottom: -10px;
    right: -10px;
}

/* ===== MAIN CONTENT AREA ===== */
.certificate-content {
    padding: 40px 60px;
    height: 100%;
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
}

/* ===== HEADER SECTION ===== */
.header {
    text-align: center;
    position: relative;
    padding-top: 0px;
    margin-bottom: 5px;
}

.school-name {
    font-family: "Cinzel", serif;
    font-size: 32px;
    font-weight: 700;
    color: #2a2a8f;
    margin-bottom: 0px;
    letter-spacing: 1px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.school-address {
    font-size: 19px;
    color: #7b2cbf;
    font-weight: 600;
    margin-bottom: 1px;
}

.school-meta {
    font-size: 17px;
    color: #333;
    margin-bottom: 0px;
    font-weight: 500;

}

.school-contact {
    font-size: 17px;
    color: #333;
    margin-bottom: 0px;
    font-weight: 500;
    border-bottom: 1px solid rgba(0, 0, 0, 0.3);
}

/* ===== LOGO & QR ===== */
.logo {
    position: absolute;
    top: 10px;
    width: 80px;
    height: 80px;
    border: 0px solid #2a2a8f;
    border-radius: 0%;
    display: flex;
    align-items: center;
    justify-content: center;

    Left: 40px;
}


.qr {
    right: 40px;
    position: absolute;
    top: 10px;
    width: 80px;
    height: 80px;
    border: 0px solid #2a2a8f;
    border-radius: 0%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.logo-icon, .qr-icon {
    font-size: 36px;
    color: #2a2a8f;
}

/* ===== CERTIFICATE TITLE ===== */
.certificate-title-section {
    text-align: center;
    margin: 0px 0 0px 0;
    position: relative;
    padding-bottom: 2px;

}

.certificate-title {
    font-family: "Cinzel", serif;
    font-size: 36px;
    font-weight: 700;
    color: #b100ff;
    text-transform: uppercase;
    letter-spacing: 2px;
    display: inline-block;
    padding: 0px 10px 0px 10px;
    position: relative;
    border: #FF0EFE solid;
    border-radius: 10px 10px 10px 10px;
    box-shadow: #7b7b7b 3px 3px;
}

.certificate-title::before, .certificate-title::after {
    content: "";
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    color: #0072bc;
}

.certificate-title::before {
    left: -20px;
}

.certificate-title::after {
    right: -20px;
}



/* ===== CERTIFICATE BODY ===== */
.certificate-body {
    font-size: 19px;
    line-height: 1.6;
    text-align: justify;
    flex-grow: 1;
    padding: 0px 0;
    font-family: "times new Roman", serif;
}

/* ===== STUDENT INFORMATION SECTION ===== */
.student-certification {
    text-align: center;
    margin-bottom: 25px;
    padding: 0px 0;
}

.certify-text {
    font-size: 20px;
    color: #333;
    margin-bottom: 2px;
}

.student-name-highlight {
    font-family: "Great Vibes", cursive;
    font-size: 32px;
    font-weight: 400;
    color: #2a2a8f;
    display: block;
    margin: 0px 0 0px 0;
    position: relative;
    padding-bottom: 0px;
}

.student-name-highlight::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 200px;
    height: 2px;
    background: linear-gradient(to right, transparent, #0072bc, transparent);
}

/* ===== INFORMATION FIELDS ===== */
.info-field {
    display: inline;
    margin: 0 3px;
    position: relative;
}

.info-label {
    font-weight: 600;
    color: #333;
}

.info-value {
    display: inline-block;


    padding: 0 0px 0px 0px;
    margin: 0 3px;
    font-weight: 700;
    color: #2a2a8f;
    text-align: center;
    vertical-align: baseline;
    font-size: 19px;
}

/* ===== PARAGRAPH SPACING ===== */
.paragraph {
    margin-bottom: 5px;
    line-height: 1.6;
}

.paragraph-compact {
    margin-bottom: 8px;
    line-height: 1.5;
}

.paragraph-space {
    margin-top: 15px;
}

/* ===== FOOTER SECTION ===== */
.certificate-footer {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding-top: 20px;
}

.date-info {
    font-size: 17px;
    line-height: 1.4;
}

.date-info b {
    color: #2a2a8f;
}

.signature-section {
    text-align: center;
    position: relative;
    margin-right: 40px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.signature-line {
    width: 250px;
    border-top: 2px solid #000;
    margin-bottom: 6px;
    position: relative;
}

.signature-title {
    font-size: 20px;
    font-weight: 700;
    color: #2a2a8f;
    font-family: "Cinzel", serif;
}

.signature-subtitle {
    font-size: 16px;
    color: #666;
    margin-top: 3px;
}

/* ===== BACKGROUND PATTERN ===== */
.background-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image:
        radial-gradient(circle at 10% 20%, rgba(123, 44, 191, 0.03) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(0, 114, 188, 0.03) 0%, transparent 20%);
    z-index: 0;
}

/* ===== PRINT BUTTON ===== */
.print-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 24px;
    background: linear-gradient(to right, #2a2a8f, #7b2cbf);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 5px 15px rgba(42, 42, 143, 0.3);
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: "Playfair Display", serif;
    transition: all 0.3s ease;
}

.print-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 20px rgba(42, 42, 143, 0.4);
}

/* ===== PRINT STYLES ===== */
@media print {
    body {
        background: none;
        padding: 0;
        margin: 0;
    }

    .certificate-container {
        box-shadow: none;
        margin: 0;
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        @if($setting && $setting->background_path)
        background-image: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($setting->background_path) }}');
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        @else
        background: linear-gradient(rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.98));
        @endif
    }

    .print-btn {
        display: none;
    }

    /* Ensure it fits in one page */
    .certificate-content {
        page-break-inside: avoid;
    }


}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Playfair+Display:wght@400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<div class="certificate-container">
    <div class="certificate">
        <!-- Background Pattern -->
        <div class="background-pattern"></div>

        <!-- Corner Ornaments -->
        <div class="corner-ornament top-left-ornament"></div>
        <div class="corner-ornament top-right-ornament"></div>
        <div class="corner-ornament bottom-left-ornament"></div>
        <div class="corner-ornament bottom-right-ornament"></div>

        <div class="certificate-content">
            <!-- HEADER SECTION -->
            <div class="header">
                <!-- LOGO -->
                <div class="logo">
                    @if($school->logo)
                        <img src="{{ asset('storage/'.$school->logo) }}" alt="School Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    @else
                        <div class="logo-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    @endif
                </div>

                <!-- QR CODE -->
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate(route('documents.verify', $document->code)) !!}
                </div>

                <!-- SCHOOL INFO -->
                <div class="school-name">{{ $school->name }}</div>
                <div class="school-address">{{ $school->address }}</div>
                <div class="school-meta">EIIN: {{ $school->code ?? 'N/A' }}</div>
                <div class="school-contact">Web: {{ $school->website }} | Email: {{ $school->email }} | Phone: {{ $school->phone }}</div>
            </div>

            <!-- CERTIFICATE TITLE -->
            <div class="certificate-title-section">
                <div class="certificate-title"> Testimonial </div>
            </div>

            <!-- CERTIFICATE BODY -->
            <div class="certificate-body">
                <!-- Certification and Student Name -->
                <div class="student-certification">
                    <div class="certify-text">This is to certify that</div>
                    <div class="student-name-highlight">{{ $student->student_name_en ?: $student->student_name_bn }}</div>
                </div>

                <!-- Student Information -->
                <div class="paragraph">
                    <span class="info-label">@if($student->gender == 'male') Son of @else Daughter of @endif</span> <span class="info-value">{{ $student->father_name }}</span>
                    <span class="info-label">and</span> <span class="info-value">{{ $student->mother_name }}</span>, <span class="info-label">Village</span> <span class="info-value">{{ $student->present_village }}</span>, <span class="info-label">Post Office</span> <span class="info-value">{{ $student->present_post_office }}</span>, <span class="info-label">Upazila</span> <span class="info-value">{{ $student->present_upazilla }}</span>, <span class="info-label">District</span> <span class="info-value">{{ $student->present_district }}</span>, <span class="info-label">passed the @if($document->data['exam_name'] == 'SSC') Secondary School Certificate (S.S.C) @elseif($document->data['exam_name'] == 'HSC') Higher Secondary Certificate (H.S.C) @else {{ $document->data['exam_name'] }} @endif Examination in</span><span class="info-value">{{ $document->data['passing_year'] }}</span><span class="info-label">from this school under the</span><span class="info-label"> Board of Intermediate and Secondary Education, {{ $document->data['board'] }},</span> <span class="info-label">bearing Roll <span class="info-value">{{ $document->data['center'] ?? '-' }} </span> <span class="info-label"> No</span> <span class="info-value">{{ $document->data['roll'] ?? '-' }} </span>, <span class="info-label">Registration No</span> <span class="info-value">{{ $document->data['registration'] ?? '-' }}</span>, <span class="info-label">Session</span> <span class="info-value">{{ $document->data['session'] ?? '-' }}</span> <span class="info-label">and obtained GPA</span> <span class="info-value">{{ $document->data['result'] ?? '-' }}</span> <span class="info-label">out of scale 5.00 in</span> <span class="info-value">-</span><span class="info-label">  Group. @if($student->gender == 'male') His @else Her @endif Date of birth is</span> <span class="info-value">{{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '-' }}</span>.
                </div>

                <div class="paragraph paragraph-space">
                    To the best of my knowledge, @if($student->gender == 'male') he @else she @endif did not take part in any illegal activities of the state or discipline. @if($student->gender == 'male') His @else Her @endif conduct and character are good. I wish @if($student->gender == 'male') him @else her @endif every success in life.
                </div>
            </div>

            <!-- FOOTER -->
            <div class="certificate-footer">
                <div class="date-info">
                    <div><b>Ref. No:</b> {{ $document->memo_no }}</div>
                    <div><b>Issue Date:</b> {{ $document->issued_at->format('d/m/Y') }}</div>
                </div>

                <div class="signature-section">
                    <div class="signature-title">Head Teacher</div>
                    <div class="signature-subtitle">{{ $school->name }}</div>
                    <div class="signature-subtitle">{{ $school->address }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="print-btn" onclick="window.print()">
    <i class="fas fa-print"></i> Print Certificate
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adjust layout to ensure it fits on one page
    function adjustForSinglePage() {
        const container = document.querySelector('.certificate-container');
        const content = document.querySelector('.certificate-content');

        // Check if content overflows the container
        if (container.scrollHeight > container.clientHeight) {
            // Reduce font size slightly
            const body = document.querySelector('.certificate-body');
            const currentSize = parseFloat(window.getComputedStyle(body).fontSize);
            body.style.fontSize = (currentSize - 0.3) + 'px';

            // Reduce student name size if needed
            const studentName = document.querySelector('.student-name-highlight');
            const nameSize = parseFloat(window.getComputedStyle(studentName).fontSize);
            studentName.style.fontSize = (nameSize - 2) + 'px';
        }
    }

    // Run adjustment after page loads
    setTimeout(adjustForSinglePage, 100);

    // Also adjust when printing
    window.addEventListener('beforeprint', adjustForSinglePage);
});
</script>

</body>
</html>
