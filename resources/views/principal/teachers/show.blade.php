@extends(request('print') ? 'layouts.print' : 'layouts.admin')

@section('title', 'শিক্ষক প্রোফাইল - ' . ($teacher->full_name_bn ?: $teacher->full_name))
@section('suppress_header', true)
@section('suppress_watermark', true)

@section('content')
    <style>
        /* Screen Styles */
        body {
            background: #f4f6f9;
        }

        .profile-container {
            max-width: 210mm;
            /* A4 Width */
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            position: relative;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .school-info-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .school-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .school-info {
            flex: 1;
        }

        .school-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0 0 5px 0;
        }

        .school-address {
            font-size: 13px;
            color: #4b5563;
            margin: 0;
        }

        .profile-title {
            text-align: center;
            background: #eef2ff;
            color: #3730a3;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin-top: 15px;
        }

        .teacher-photo-box {
            width: 100px;
            height: 120px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            margin-left: 20px;
            flex-shrink: 0;
        }

        .teacher-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .teacher-photo-placeholder {
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-section-title {
            font-size: 15px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;
            color: #111827;
            font-weight: 500;
            border-bottom: 1px dashed #e5e7eb;
            padding-bottom: 4px;
        }

        .info-value.full-width {
            grid-column: span 2;
        }

        .address-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .address-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
        }

        .address-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .address-text {
            font-size: 13px;
            line-height: 1.6;
            color: #334155;
        }

        .signature-box {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .signature-item {
            text-align: center;
            width: 200px;
        }

        .signature-image {
            height: 50px;
            margin-bottom: 5px;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 13px;
            font-weight: bold;
        }

        .print-btn-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-btn-container {
                display: none !important;
            }

            .profile-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
            }

            .info-value {
                border-bottom: 1px dotted #ccc !important;
            }
        }
    </style>

    @if(!request('print'))
        <div class="content-header back-btn-container">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <a href="{{ route('principal.institute.teachers.index', $school) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> ফিরে যান
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="content {{ request('print') ? 'pt-4' : '' }}">
        <div class="container-fluid pb-5">
            <div class="profile-container" id="printableArea">

                @if(!request('print'))
                    <div class="print-btn-container">
                        <a href="{{ route('principal.institute.teachers.show', [$school, $teacher->id]) }}?print=true"
                            target="_blank" class="btn btn-primary rounded-pill shadow-sm px-4">
                            <i class="fas fa-print mr-1"></i> প্রিন্ট করুন
                        </a>
                    </div>
                @endif

                <div class="profile-header">
                    <div class="school-info-wrapper">
                        @if($school->logo)
                            <img src="{{ asset('storage/' . $school->logo) }}" alt="School Logo" class="school-logo">
                        @endif
                        <div class="school-info">
                            <h1 class="school-name">{{ $school->name_bn ?: $school->name }}</h1>
                            <p class="school-address">{{ $school->address_bn ?: $school->address }}</p>
                            <div class="profile-title">শিক্ষক প্রোফাইল</div>
                        </div>
                    </div>
                    <div class="teacher-photo-box">
                        @if($teacher->photo_url)
                            <img src="{{ $teacher->photo_url }}" alt="Teacher Photo">
                        @else
                            <span class="teacher-photo-placeholder">ছবি নেই</span>
                        @endif
                    </div>
                </div>

                @php
                    $nameBn = $teacher->full_name_bn ?: '—';
                    $nameEn = $teacher->full_name ?: '—';
                    $fatherBn = $teacher->father_name_bn ?: '—';
                    $fatherEn = $teacher->father_name_en ?: '—';
                    $motherBn = $teacher->mother_name_bn ?: '—';
                    $motherEn = $teacher->mother_name_en ?: '—';

                    function bnNumFormat($num)
                    {
                        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
                        return str_replace($en, $bn, $num);
                    }
                @endphp

                <div class="info-section">
                    <div class="info-section-title"><i class="fas fa-user-circle"></i> ব্যক্তিগত তথ্য (Personal Information)
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">নাম (বাংলা)</span>
                            <span class="info-value">{{ $nameBn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">নাম (English)</span>
                            <span class="info-value">{{ $nameEn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">পিতার নাম (বাংলা)</span>
                            <span class="info-value">{{ $fatherBn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Father's Name (English)</span>
                            <span class="info-value">{{ $fatherEn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">মাতার নাম (বাংলা)</span>
                            <span class="info-value">{{ $motherBn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mother's Name (English)</span>
                            <span class="info-value">{{ $motherEn }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">জন্ম তারিখ</span>
                            <span
                                class="info-value">{{ $teacher->date_of_birth ? bnNumFormat($teacher->date_of_birth->format('d/m/Y')) : '—' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ক্রমিক নং (Serial No)</span>
                            <span
                                class="info-value">{{ $teacher->serial_number ? bnNumFormat($teacher->serial_number) : '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-section-title"><i class="fas fa-briefcase"></i> পেশাগত ও যোগাযোগ তথ্য (Professional &
                        Contact)</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">পদবী (Designation)</span>
                            <span class="info-value">{{ $teacher->designation ?: '—' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">চাকুরী টাইপ (Job Type)</span>
                            <span class="info-value">{{ $teacher->job_type ?: '—' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">যোগদান তারিখ (Join Date)</span>
                            <span
                                class="info-value">{{ $teacher->joining_date ? bnNumFormat($teacher->joining_date->format('d/m/Y')) : '—' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">স্ট্যাটাস (Status)</span>
                            <span
                                class="info-value">{{ $teacher->status == 'active' ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Inactive)' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">মোবাইল (Mobile)</span>
                            <span class="info-value">{{ $teacher->phone ? bnNumFormat($teacher->phone) : '—' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ইমেইল (Email)</span>
                            <span class="info-value">{{ $teacher->user?->email ?: '—' }}</span>
                        </div>
                    </div>
                </div>

                @php
                    $preArr = [];
                    if ($teacher->present_village)
                        $preArr[] = "গ্রাম: " . $teacher->present_village;
                    if ($teacher->present_post_office)
                        $preArr[] = "ডাকঘর: " . $teacher->present_post_office;
                    if ($teacher->presentThana)
                        $preArr[] = "উপজেলা: " . ($teacher->presentThana->bn_name ?? $teacher->presentThana->name);
                    if ($teacher->presentDistrict)
                        $preArr[] = "জেলা: " . ($teacher->presentDistrict->bn_name ?? $teacher->presentDistrict->name);

                    $perArr = [];
                    if ($teacher->permanent_village)
                        $perArr[] = "গ্রাম: " . $teacher->permanent_village;
                    if ($teacher->permanent_post_office)
                        $perArr[] = "ডাকঘর: " . $teacher->permanent_post_office;
                    if ($teacher->permanentThana)
                        $perArr[] = "উপজেলা: " . ($teacher->permanentThana->bn_name ?? $teacher->permanentThana->name);
                    if ($teacher->permanentDistrict)
                        $perArr[] = "জেলা: " . ($teacher->permanentDistrict->bn_name ?? $teacher->permanentDistrict->name);
                @endphp

                <div class="info-section">
                    <div class="info-section-title"><i class="fas fa-map-marker-alt"></i> ঠিকানা (Address)</div>
                    <div class="address-grid">
                        <div class="address-box">
                            <div class="address-title"><i class="fas fa-home"></i> বর্তমান ঠিকানা</div>
                            <div class="address-text">
                                {!! !empty($preArr) ? implode('<br>', $preArr) : '—' !!}
                            </div>
                        </div>
                        <div class="address-box">
                            <div class="address-title"><i class="fas fa-building"></i> স্থায়ী ঠিকানা</div>
                            <div class="address-text">
                                {!! !empty($perArr) ? implode('<br>', $perArr) : '—' !!}
                            </div>
                        </div>
                    </div>
                </div>

                @if($teacher->academic_info || $teacher->qualification)
                    <div class="info-section">
                        <div class="info-section-title"><i class="fas fa-graduation-cap"></i> শিক্ষাগত যোগ্যতা (Academic
                            Qualifications)</div>
                        <div class="info-grid">
                            @if($teacher->academic_info)
                                <div class="info-item" style="grid-column: span 2;">
                                    <span class="info-label">Academic Info</span>
                                    <span class="info-value" style="white-space: pre-wrap;">{{ $teacher->academic_info }}</span>
                                </div>
                            @endif
                            @if($teacher->qualification)
                                <div class="info-item" style="grid-column: span 2;">
                                    <span class="info-label">Other Qualification</span>
                                    <span class="info-value" style="white-space: pre-wrap;">{{ $teacher->qualification }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="signature-box">
                    <div class="signature-item">
                        @if($teacher->signature)
                            <img src="{{ asset('storage/' . $teacher->signature) }}" alt="Signature" class="signature-image">
                        @else
                            <div style="height: 50px;"></div>
                        @endif
                        <div class="signature-line">শিক্ষকের স্বাক্ষর</div>
                    </div>
                    <div class="signature-item">
                        @if(isset($headTeacher) && $headTeacher->signature)
                            <img src="{{ asset('storage/' . $headTeacher->signature) }}" alt="Principal Signature"
                                class="signature-image">
                        @else
                            <div style="height: 50px;"></div>
                        @endif
                        <div class="signature-line">অধ্যক্ষ / প্রধান শিক্ষকের স্বাক্ষর</div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    @if(request('print'))
        <script>
            window.onload = function () {
                window.print();
            }
        </script>
    @endif

@endsection