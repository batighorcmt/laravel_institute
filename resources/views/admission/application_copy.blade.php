<x-layout.public :school="$school" :title="'ভর্তি আবেদন কপি — ' . ($school->name ?? '')">
    @php
        // Check if payment is done - if not, redirect or show error
        if (!$application || strtolower($application->payment_status) !== 'paid') {
            abort(403, 'ফিস পরিশোধ না করা আবেদনের কপি পাওয়া যাবে না।');
        }
        
        // Simple English digit to Bangla digit converter
        function bnDigits($value){
            $map = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            return preg_replace_callback('/\d/', fn($m)=>$map[$m[0]] ?? $m[0], (string)$value);
        }
        $genderMap = ['male'=>'পুরুষ','female'=>'মহিলা','other'=>'অন্যান্য'];
        $religionMap = ['islam'=>'ইসলাম','hindu'=>'হিন্দু','christian'=>'খ্রিষ্টান','buddhist'=>'বৌদ্ধ'];
        $bnClassMap = [
            '6' => 'ষষ্ঠ শ্রেণি',
            '7' => 'সপ্তম শ্রেণি',
            '8' => 'অষ্টম শ্রেণি',
            '9' => 'নবম শ্রেণি',
            '10' => 'দশম শ্রেণি',
        ];
        $rawClass = trim((string)($application->class_name ?? ''));
        if ($rawClass !== '' && array_key_exists($rawClass, $bnClassMap)) {
            $classTitle = $bnClassMap[$rawClass];
        } else {
            $classTitle = $rawClass !== '' ? $rawClass : 'শ্রেণি';
            if (mb_strpos($classTitle, 'শ্রেণি') === false) { $classTitle .= ' শ্রেণি'; }
        }
        $yearText = optional(optional($application->academicYear)->start_date)->format('Y') ?? date('Y');
    @endphp
    @push('styles')
    <style>
        .sheet { width:210mm; min-height:297mm; margin:auto; background:#fff; padding:16mm; position:relative; font-size:17px; }
        .watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-25deg); opacity:.08; width:420px; max-width:70%; pointer-events:none; }
        .head-wrap { display:flex; align-items:center; border-bottom:2px solid #343a40; padding-bottom:8px; margin-bottom:10px; }
        .head-logo { height:85px; width:85px; object-fit:contain; margin-right:10px; }
        .head-text h2 { font-size:36px; margin:0 0 6px; font-weight:700; color:#2c3e50; }
        .head-text p { margin:0; line-height:1.45; font-size:16px; }
        .form-title { text-align:center; font-size:22px; font-weight:700; color:#0d6efd; margin:10px 0 16px; }
        table.form-table { width:100%; border-collapse:collapse; margin-bottom:10px; }
        .form-table th, .form-table td { border:1px solid #d0d7de; padding:8px 10px; vertical-align:top; }
        .section-head { background:#f1f5f9; font-weight:600; text-align:left; color:#0d6efd; }
        .photo-box { width:140px; height:185px; border:1px solid #ccc; display:flex; align-items:center; justify-content:center; background:#fff; }
        .photo-box img { width:100%; height:100%; object-fit:cover; }
        .sig-row td { height:80px; }
        .sig-label { font-size:15px; font-weight:600; }
        .attachments ol { margin:0 0 0 18px; padding:0; }
        .attachments li { margin-bottom:4px; }
        .meta-small { font-size:13px; color:#555; }
        .print-bar { margin-top:12px; }
        .badge-status { font-weight:600; }
        .cancel-note { color:#dc3545; font-weight:600; }
        @media print {
            @page { size:A4; margin:0; }
            body, html { background:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; font-size:17px; }
            .no-print, .print-bar { display:none !important; }
            .sheet { box-shadow:none !important; width:100% !important; margin:0 !important; padding:10mm; min-height:auto; font-size:17px; }
            .form-title { color:#0d6efd !important; }
        }
    </style>
    @endpush
    <div class="sheet">
        @php $logo = $school->logo ? asset('storage/'.$school->logo) : asset('images/default-logo.png'); @endphp
        <img src="{{ $logo }}" alt="Watermark" class="watermark" />
        <div class="head-wrap">
            <img src="{{ $logo }}" alt="Logo" class="head-logo" />
            <div class="head-text">
                <h2>{{ $school->name }}</h2>
                <p>{{ $school->address ?? 'ঠিকানা উপলব্ধ নয়' }}</p>
                <p>স্কুল কোড: {{ bnDigits($school->code) }} | মোবাইল: {{ bnDigits($school->phone ?? 'N/A') }}</p>
            </div>
        </div>
        <div class="form-title"> {{ $classTitle }} ভর্তি আবেদন - {{ bnDigits($yearText) }} </div>
        <table class="form-table">
            <tr class="section-head"><th style="width:25%">আবেদন আইডি</th><th style="width:25%">আবেদনের তারিখ</th><th style="width:25%">জমার তারিখ</th><th style="width:25%">ভর্তি রোল নং</th></tr>
            <tr>
                <td>{{ ($application->app_id) }}</td>
                <td>{{ bnDigits(optional($application->created_at)->format('d-m-Y')) }}</td>
                <td>
                    {{ $application->accepted_at ? bnDigits(optional($application->accepted_at)->format('d-m-Y')) : '' }}
                </td>
                <td>
                    @php $admissionRoll = data_get($application->data, 'admission_roll'); @endphp
                    {{ $application->accepted_at && $admissionRoll ? bnDigits($admissionRoll) : '' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr class="section-head"><th colspan="4">শিক্ষার্থীর তথ্য</th><th class="text-center" style="width:130px">ছবি</th></tr>
            <tr>
                <td colspan="4"><strong>নাম:</strong> {{ $application->name_en }} ({{ $application->name_bn }})</td>
                <td rowspan="5">
                    <div class="photo-box">
                        @if($application->photo)
                            <img src="{{ asset('storage/admission/'.$application->photo) }}" alt="Photo" />
                        @else
                            <span style="font-size:11px">ছবি নেই</span>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="4"><strong>পিতার নাম:</strong> {{ $application->father_name_en }} {{ $application->father_name_bn ? '(' . $application->father_name_bn . ')' : '' }}</td>
            </tr>
            <tr>
                <td colspan="4"><strong>মাতার নাম:</strong> {{ $application->mother_name_en }} {{ $application->mother_name_bn ? '(' . $application->mother_name_bn . ')' : '' }}</td>
            </tr>
            <tr>
                <td colspan="4"><strong>জন্ম নিবন্ধন নং:</strong> {{ bnDigits($application->birth_reg_no ?? '—') }}</td>
            </tr>
            <tr>
                <td><strong>জন্ম তারিখ:</strong> {{ bnDigits(optional($application->dob)->format('d-m-Y')) }}</td>
                <td><strong>লিঙ্গ:</strong> {{ $genderMap[$application->gender] ?? $application->gender }}</td>
                <td><strong>রক্তের গ্রুপ:</strong> {{ $application->blood_group ?? '—' }}</td>
                <td><strong>ধর্ম:</strong> {{ $religionMap[$application->religion] ?? '—' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr class="section-head"><th style="width:50%">বর্তমান ঠিকানা</th><th style="width:50%">স্থায়ী ঠিকানা</th></tr>
            <tr>
                <td>
                    গ্রাম: {{ $application->present_village ?? '—' }}
                    @if($application->present_para_moholla)
                        ({{ $application->present_para_moholla }}),
                    @endif
                    ডাকঘর: {{ $application->present_post_office ?? '—' }},
                    উপজেলা: {{ $application->present_upazilla ?? '—' }},
                    জেলা: {{ $application->present_district ?? '—' }}
                </td>
                <td>
                    গ্রাম: {{ $application->permanent_village ?? '—' }}
                    @if($application->permanent_para_moholla)
                        ({{ $application->permanent_para_moholla }}),
                    @endif
                    <br>
                    ডাকঘর: {{ $application->permanent_post_office ?? '—' }},
                    উপজেলা: {{ $application->permanent_upazilla ?? '—' }},
                    জেলা: {{ $application->permanent_district ?? '—' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr class="section-head"><th style="width:50%">যোগাযোগ</th><th style="width:50%">অভিভাবক</th></tr>
            <tr>
                <td><strong>মোবাইল:</strong> {{ bnDigits($application->mobile) }}</td>
                <td>
                    <strong>সম্পর্ক:</strong> 
                    @php
                        $relationMap = [
                            'father' => 'পিতা',
                            'mother' => 'মাতা',
                            'uncle' => 'চাচা/মামা',
                            'aunt' => 'চাচী/খালা',
                            'brother' => 'ভাই',
                            'sister' => 'বোন',
                            'other' => 'অন্যান্য'
                        ];
                    @endphp
                    {{ $relationMap[$application->guardian_relation ?? ''] ?? '—' }} 
                    <br>
                    <strong>নাম:</strong> {{ $application->guardian_name_bn ?? $application->guardian_name_en ?? '—' }} ({{ $application->guardian_name_en ?? '—' }})
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr class="section-head"><th colspan="3">পূর্ববর্তী শিক্ষা</th></tr>
            <tr>
                <td style="width:40%"><strong>সর্বশেষ বিদ্যালয়:</strong> {{ $application->last_school ?? '—' }}</td>
                <td style="width:30%"><strong>ফলাফল:</strong> {{ $application->result ?? '—' }}</td>
                <td style="width:30%"><strong>পাশের বছর:</strong> {{ bnDigits($application->pass_year ?? '—') }}</td>
            </tr>
            <tr>
                <td colspan="3" style="width:40%"><strong>সাফল্যসমূহ:</strong> {{ $application->achievement ?? '—' }}</td>
            </tr>
        </table>
        @if($application->status==='cancelled')
            <div class="cancel-note mb-2">বাতিলের কারণ: {{ $application->cancellation_reason }}</div>
        @endif
        <table class="form-table">
            <tr class="section-head"><th colspan="4">পেমেন্ট তথ্য</th></tr>
            <tr>
                <td style="width:25%"><strong>স্ট্যাটাস:</strong> {{ $application->payment_status==='Paid' ? 'পরিশোধিত' : 'অপরিশোধিত' }}
                @if ($application->payment_status==='Paid')
                    <span class="badge-status" style="color:green; font-weight:600;"><strong>পরিমান:</strong> {{bnDigits($payment->amount) }}</span>  
                @endif
                </td>
                <td style="width:25%"><strong>মেথড:</strong> {{ $payment->payment_method ?? '—' }}</td>
                <td style="width:25%"><strong>ট্রানজেকশন আইডি:</strong> {{ $payment->tran_id ?? '—' }}</td>
                <td style="width:25%"><strong>ইনভয়েস:</strong> {{ $payment ? ($payment->invoice_no) : '—' }}</td>
            </tr>
        </table>
        <table class="form-table sig-row">
            <tr class="section-head"><th style="width:50%">অভিভাবকের স্বাক্ষর</th><th style="width:50%">শিক্ষার্থীর স্বাক্ষর</th></tr>
            <tr><td></td><td></td></tr>
        </table>
        <div class="attachments">
            <div style="font-weight:600; border-bottom:1px solid #d0d7de; margin:10px 0 6px; padding-bottom:4px;">সংযুক্তি (প্রয়োজনে জমা দিবেন)</div>
            <ol>
                <li>1. জন্ম নিবন্ধন সনদের ফটোকপি</li>
                <li>2. পিতা-মাতা/অভিভাবকের জাতীয় পরিচয়পত্রের ফটোকপি</li>
                <li>3. পূর্ববর্তী পরীক্ষার মার্কশিট/প্রশংসাপত্র</li>
            </ol>
        </div>
        <div class="print-bar no-print text-center">
            <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="fa-solid fa-print me-1"></i> প্রিন্ট করুন</button>
            <a href="{{ route('admission.preview', [$school->code, $application->app_id]) }}" class="btn btn-link btn-sm">প্রিভিউতে ফিরে যান</a>
        </div>
    </div>
</x-layout.public>
