<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionApplication;
use App\Models\AdmissionClassSetting;
use Illuminate\Support\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function settings(School $school)
    {
        $academicYears = \App\Models\AcademicYear::where('school_id',$school->id)->orderByDesc('start_date')->get();
        $classSettings = collect();
        if ($school->admission_academic_year_id) {
            $classSettings = AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->orderBy('class_code')
                ->get();
        }
        // Admission exam settings (datetime + venues)
        $raw = Setting::forSchool($school->id)
            ->whereIn('key', [
                'admission_exam_datetime','admission_exam_venues'
            ])
            ->pluck('value','key');
        $examDatetime = $raw->get('admission_exam_datetime');
        $venues = [];
        if ($raw->get('admission_exam_venues')) {
            $v = json_decode($raw->get('admission_exam_venues'), true);
            if (is_array($v)) { $venues = $v; }
        }
        return view('principal.admissions.settings', compact('school','academicYears','classSettings','examDatetime','venues'));
    }

    public function updateSettings(Request $request, School $school)
    {
        $data = $request->validate([
            'admissions_enabled' => 'nullable|boolean',
            'admission_academic_year_id' => 'required|exists:academic_years,id',
            'exam_datetime' => 'nullable|date',
            'venues_name' => 'array',
            'venues_name.*' => 'nullable|string|max:191',
            'venues_address' => 'array',
            'venues_address.*' => 'nullable|string|max:500',
        ]);
        $school->update([
            'admissions_enabled' => (bool)($data['admissions_enabled'] ?? false),
            'admission_academic_year_id' => $data['admission_academic_year_id']
        ]);
        // Save exam datetime
        $examDt = $data['exam_datetime'] ?? null;
        if ($examDt) {
            Setting::updateOrCreate(
                ['school_id'=>$school->id,'key'=>'admission_exam_datetime'],
                ['value'=> (string)Carbon::parse($examDt)->toDateTimeString()]
            );
        } else {
            // allow clearing
            Setting::where('school_id',$school->id)->where('key','admission_exam_datetime')->delete();
        }
        // Save venues as JSON array
        $names = $request->input('venues_name', []);
        $addresses = $request->input('venues_address', []);
        $out = [];
        $count = max(count($names), count($addresses));
        for ($i=0; $i<$count; $i++) {
            $n = trim((string)($names[$i] ?? ''));
            $a = trim((string)($addresses[$i] ?? ''));
            if ($n || $a) { $out[] = ['name'=>$n, 'address'=>$a]; }
        }
        if (!empty($out)) {
            Setting::updateOrCreate(
                ['school_id'=>$school->id,'key'=>'admission_exam_venues'],
                ['value'=> json_encode($out, JSON_UNESCAPED_UNICODE)]
            );
        } else {
            Setting::where('school_id',$school->id)->where('key','admission_exam_venues')->delete();
        }
        return redirect()->back()->with('success','Admission সেটিংস আপডেট হয়েছে');
    }

    public function applications(Request $request, School $school)
    {
        // Base query
        $baseQuery = AdmissionApplication::query()->where('school_id', $school->id);

        // Filters
        $class = trim((string) $request->query('class'));
        $gender = trim((string) $request->query('gender'));
        $religion = trim((string) $request->query('religion'));
        $village = trim((string) $request->query('village'));
        $upazila = trim((string) $request->query('upazila'));
        $district = trim((string) $request->query('district'));
        $prevSchool = trim((string) $request->query('prev_school'));
        $payStatus = trim((string) $request->query('pay_status'));
        $from = $request->date('from');
        $to = $request->date('to');
        $q = trim((string) $request->query('q'));

        $listQuery = AdmissionApplication::query()->where('school_id', $school->id);
        if ($class !== '') { $listQuery->where('class_name', $class); }
        if ($gender !== '') { $listQuery->where('gender', $gender); }
        if ($religion !== '') { $listQuery->where('religion', $religion); }
        if ($village !== '') { $listQuery->where('present_village', $village); }
        if ($upazila !== '') { $listQuery->where('present_upazilla', $upazila); }
        if ($district !== '') { $listQuery->where('present_district', $district); }
        if ($prevSchool !== '') { $listQuery->where('last_school', $prevSchool); }
        if ($payStatus !== '') {
            if (strtolower($payStatus) === 'paid') {
                $listQuery->where('payment_status', 'Paid');
            } elseif (strtolower($payStatus) === 'unpaid') {
                $listQuery->where(function($q){ $q->whereNull('payment_status')->orWhere('payment_status','!=','Paid'); })
                         ->where('status','!=','cancelled');
            } else {
                $listQuery->where('payment_status', $payStatus);
            }
        }
        if ($from) { $listQuery->whereDate('created_at','>=',$from); }
        if ($to) { $listQuery->whereDate('created_at','<=',$to); }
        if ($q !== '') {
            $listQuery->where(function($qq) use ($q) {
                $qq->where('app_id','like',"%$q%")
                   ->orWhere('name_en','like',"%$q%")
                   ->orWhere('name_bn','like',"%$q%")
                   ->orWhere('father_name_en','like',"%$q%")
                   ->orWhere('father_name_bn','like',"%$q%")
                   ->orWhere('mother_name_en','like',"%$q%")
                   ->orWhere('mother_name_bn','like',"%$q%")
                   ->orWhere('mobile','like',"%$q%")
                   ->orWhere('class_name','like',"%$q%")
                   ->orWhere('last_school','like',"%$q%")
                   ->orWhere('present_village','like',"%$q%")
                   ->orWhere('present_para_moholla','like',"%$q%")
                   ->orWhere('present_post_office','like',"%$q%")
                   ->orWhere('present_upazilla','like',"%$q%")
                   ->orWhere('present_district','like',"%$q%")
                   ->orWhere('admission_roll_no','like',"%$q%");
            });
        }

        $apps = $listQuery->orderByDesc('id')->paginate(20)->appends($request->query());

        // Statistics
        $totalApps = (clone $baseQuery)->count();
        $acceptedApps = (clone $baseQuery)->whereNotNull('accepted_at')->count();
        $cancelledApps = (clone $baseQuery)->where('status','cancelled')->count();
        $paidApps = (clone $baseQuery)->where('payment_status','Paid')->count();
        $unpaidApps = (clone $baseQuery)
            ->where('status','!=','cancelled')
            ->where(function($q){
                $q->whereNull('payment_status')->orWhere('payment_status','!=','Paid');
            })->count();

        // Total paid amount (sum of successful payments for applications of this school)
        $totalPaidAmount = \App\Models\AdmissionPayment::whereHas('application', function($q) use ($school){
            $q->where('school_id',$school->id);
        })
        ->where('status','Completed')
        ->where(function($q){
            $q->where('fee_type','application')->orWhereNull('fee_type');
        })
        ->sum('amount');

        // Expected total fees based on class settings (match by class_code == application->class_name)
        $expectedTotalFees = 0;
        $settings = [];
        if ($school->admission_academic_year_id) {
            $settings = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->get()->keyBy('class_code');
        }
        foreach ((clone $baseQuery)->get(['class_name']) as $appRow) {
            if ($appRow->class_name && isset($settings[$appRow->class_name])) {
                $expectedTotalFees += (float) $settings[$appRow->class_name]->fee_amount;
            }
        }
        $unpaidAmount = max($expectedTotalFees - (float)$totalPaidAmount, 0);

        // Distinct lists for filters
        $distinct = fn($col) => AdmissionApplication::where('school_id',$school->id)
            ->whereNotNull($col)
            ->where($col,'!=','')
            ->distinct()->orderBy($col)->pluck($col)->values();
        $classes = $distinct('class_name');
        $genders = $distinct('gender');
        $religions = $distinct('religion');
        $villages = $distinct('present_village');
        $upazilas = $distinct('present_upazilla');
        $districts = $distinct('present_district');
        $prevSchools = $distinct('last_school');
        $payStatuses = AdmissionApplication::where('school_id',$school->id)
            ->select('payment_status')->distinct()->pluck('payment_status')->filter()->values();

        // AJAX partial response for real-time search/filter
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('principal.admissions.partials._rows', [
                    'apps' => $apps,
                    'school' => $school,
                ])->render(),
                'pagination' => view('principal.admissions.partials._pagination', [
                    'apps' => $apps,
                    'school' => $school,
                ])->render(),
            ]);
        }

        return view('principal.admissions.index', [
            'school' => $school,
            'apps' => $apps,
            'totalApps' => $totalApps,
            'acceptedApps' => $acceptedApps,
            'cancelledApps' => $cancelledApps,
            'paidApps' => $paidApps,
            'unpaidApps' => $unpaidApps,
            'totalPaidAmount' => $totalPaidAmount,
            'expectedTotalFees' => $expectedTotalFees,
            'unpaidAmount' => $unpaidAmount,
            'classes' => $classes,
            'genders' => $genders,
            'religions' => $religions,
            'villages' => $villages,
            'upazilas' => $upazilas,
            'districts' => $districts,
            'prevSchools' => $prevSchools,
            'payStatuses' => $payStatuses,
            'filters' => [
                'class' => $class,
                'gender' => $gender,
                'religion' => $religion,
                'village' => $village,
                'upazila' => $upazila,
                'district' => $district,
                'prev_school' => $prevSchool,
                'pay_status' => $payStatus,
                'from' => $from ? $from->format('Y-m-d') : '',
                'to' => $to ? $to->format('Y-m-d') : '',
                'q' => $q,
            ],
        ]);
    }

    public function applicationsPrint(Request $request, School $school)
    {
        // Filters from query
        $status = array_filter((array) $request->query('status')); // accepted|pending|cancelled
        $pay = array_filter((array) $request->query('pay')); // paid|unpaid
        $class = trim((string) $request->query('class', ''));

        $query = AdmissionApplication::query()->where('school_id', $school->id);
        // Status filter
        if (!empty($status)) {
            $query->where(function($q) use ($status) {
                if (in_array('accepted', $status, true)) {
                    $q->orWhereNotNull('accepted_at');
                }
                if (in_array('pending', $status, true)) {
                    $q->orWhere(function($qp){ $qp->whereNull('accepted_at')->where('status','!=','cancelled'); });
                }
                if (in_array('cancelled', $status, true)) {
                    $q->orWhere('status','cancelled');
                }
            });
        }
        // Payment filter
        if (!empty($pay)) {
            $query->where(function($q) use ($pay) {
                if (in_array('paid', $pay, true)) { $q->orWhere('payment_status','Paid'); }
                if (in_array('unpaid', $pay, true)) { $q->orWhere(function($qp){ $qp->whereNull('payment_status')->orWhere('payment_status','!=','Paid'); }); }
            });
        }
        // Class filter (exact or partial)
        if ($class !== '') {
            $query->where('class_name','like',"%$class%");
        }

        $apps = $query->orderByDesc('id')->get();

        // Eager-load payments to avoid N+1 queries
        if (method_exists(AdmissionApplication::class, 'payments')) {
            try { $apps->load(['payments' => function($q){ $q->select('id','admission_application_id','amount','payment_method','status','created_at'); }]); } catch (\Throwable $e) {}
        }

        // Class settings for fee amounts
        $settingsByClass = [];
        if ($school->admission_academic_year_id) {
            $settingsByClass = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->get()
                ->keyBy('class_code');
        }

        // Precompute view-friendly array to avoid complex Blade expressions
        $appsJson = $apps->map(function(AdmissionApplication $a){
            // Latest payment method (if any)
            $paymentMethod = null;
            $amountPaid = null;
            if (isset($a->payments) && $a->payments instanceof \Illuminate\Support\Collection && $a->payments->count() > 0) {
                $latest = $a->payments->sortByDesc('id')->first();
                $paymentMethod = $latest?->payment_method;
                $amountPaid = $latest?->amount;
            }
            return [
                'id' => $a->id,
                'class_name' => $a->class_name,
                'app_id' => $a->app_id,
                'admission_roll_no' => $a->admission_roll_no,
                'name_en' => $a->name_en ?? $a->applicant_name,
                'name_bn' => $a->name_bn ?? $a->applicant_name,
                'father_name_en' => $a->father_name_en,
                'father_name_bn' => $a->father_name_bn,
                'mother_name_en' => $a->mother_name_en,
                'mother_name_bn' => $a->mother_name_bn,
                'mobile' => $a->mobile,
                'gender' => $a->gender,
                'religion' => $a->religion,
                'dob' => optional($a->dob)->format('Y-m-d'),
                'payment_status' => $a->payment_status,
                'status' => $a->status,
                'accepted_at' => $a->accepted_at,
                'present_village' => $a->present_village,
                'present_para_moholla' => $a->present_para_moholla,
                'present_post_office' => $a->present_post_office,
                'present_upazilla' => $a->present_upazilla,
                'present_district' => $a->present_district,
                'last_school' => $a->last_school,
                'result' => $a->result,
                'photo' => $a->photo,
                'fee_amount' => null, // filled below
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'created_at' => optional($a->created_at)->format('Y-m-d H:i'),
            ];
        })->values();

        // Fill fee_amount from class settings mapping
        if (!empty($settingsByClass)) {
            $appsJson = $appsJson->map(function(array $x) use ($settingsByClass) {
                $code = (string)($x['class_name'] ?? '');
                if ($code && isset($settingsByClass[$code])) {
                    $x['fee_amount'] = (float)$settingsByClass[$code]->fee_amount;
                }
                return $x;
            });
        }

        return view('principal.admissions.applications_print', [
            'school' => $school,
            'appsJson' => $appsJson,
            'statusFilter' => $status,
            'payFilter' => $pay,
            'classFilter' => $class,
        ]);
    }

    public function applicationsPrintCsv(Request $request, School $school)
    {
        // Reuse same filters as print view
        $status = array_filter((array) $request->query('status'));
        $pay = array_filter((array) $request->query('pay'));
        $class = trim((string) $request->query('class', ''));
        $lang = $request->query('lang','bn');

        $query = AdmissionApplication::query()->where('school_id', $school->id);
        if (!empty($status)) {
            $query->where(function($q) use ($status) {
                if (in_array('accepted', $status, true)) { $q->orWhereNotNull('accepted_at'); }
                if (in_array('pending', $status, true)) { $q->orWhere(function($qp){ $qp->whereNull('accepted_at')->where('status','!=','cancelled'); }); }
                if (in_array('cancelled', $status, true)) { $q->orWhere('status','cancelled'); }
            });
        }
        if (!empty($pay)) {
            $query->where(function($q) use ($pay) {
                if (in_array('paid', $pay, true)) { $q->orWhere('payment_status','Paid'); }
                if (in_array('unpaid', $pay, true)) { $q->orWhere(function($qp){ $qp->whereNull('payment_status')->orWhere('payment_status','!=','Paid'); }); }
            });
        }
        if ($class !== '') { $query->where('class_name','like',"%$class%"); }

        $apps = $query->orderByDesc('id')->get();
        // Eager-load payments for method
        try { $apps->load(['payments' => function($q){ $q->select('id','admission_application_id','amount','payment_method','status','created_at'); }]); } catch (\Throwable $e) {}

        // Settings for fee amount mapping
        $settingsByClass = [];
        if ($school->admission_academic_year_id) {
            $settingsByClass = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->get()->keyBy('class_code');
        }

        $toBn = function($s){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$s); };
        $mapGender = function($v) use ($lang){ $m=['male'=>'ছেলে','female'=>'মেয়ে','other'=>'অন্যান্য']; return $lang==='bn' ? ($m[strtolower((string)$v)] ?? $v) : $v; };
        $mapReligion = function($v) use ($lang){ $m=['islam'=>'ইসলাম','hindu'=>'হিন্দু','buddhist'=>'বৌদ্ধ','christian'=>'খ্রিস্টান']; $key=strtolower((string)$v); return $lang==='bn' ? ($m[$key] ?? $v) : $v; };
        $mapMethod = function($v) use ($lang){ $en=['sslcommerz'=>'SSLCommerz','bkash'=>'bKash','nagad'=>'Nagad','cash'=>'Cash','bank'=>'Bank']; $bn=['sslcommerz'=>'অনলাইন','bkash'=>'বিকাশ','nagad'=>'নগদ','cash'=>'নগদে','bank'=>'ব্যাংক']; $key=strtolower((string)$v); return $lang==='bn' ? ($bn[$key] ?? $v) : ($en[$key] ?? $v); };

        $headers = [
            $lang==='bn' ? 'ক্রমিক নং' : '#',
            $lang==='bn' ? 'আবেদন আইডি' : 'Application ID',
            $lang==='bn' ? 'রোল নং' : 'Roll No',
            $lang==='bn' ? 'শ্রেণি' : 'Class',
            $lang==='bn' ? 'শিক্ষার্থীর নাম' : 'Student Name',
            $lang==='bn' ? 'পিতার নাম' : "Father's Name",
            $lang==='bn' ? 'মাতার নাম' : "Mother's Name",
            $lang==='bn' ? 'মোবাইল নং' : 'Mobile No',
            $lang==='bn' ? 'জন্ম তারিখ' : 'Date of Birth',
            $lang==='bn' ? 'লিঙ্গ' : 'Gender',
            $lang==='bn' ? 'ধর্ম' : 'Religion',
            $lang==='bn' ? 'বর্তমান ঠিকানা' : 'Present Address',
            $lang==='bn' ? 'পূর্ববর্তী বিদ্যালয়ের নাম' : 'Previous School',
            $lang==='bn' ? 'ফলাফল' : 'Result',
            $lang==='bn' ? 'ফিসের পরিমান' : 'Fee Amount',
            $lang==='bn' ? 'পরিশোধের মাধ্যম' : 'Payment Method',
            $lang==='bn' ? 'আবেদন স্ট্যাটাস' : 'Application Status',
            $lang==='bn' ? 'আবেদনের তারিখ' : 'Application Date',
        ];

        $filename = 'admission_applications_'.$school->id.'_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function() use ($apps,$settingsByClass,$headers,$lang,$toBn,$mapGender,$mapReligion,$mapMethod) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            $i = 0;
            foreach ($apps as $a) {
                $i++;
                $latest = null; if (isset($a->payments) && $a->payments instanceof \Illuminate\Support\Collection) { $latest = $a->payments->sortByDesc('id')->first(); }
                $fee = null; $code = (string)($a->class_name ?? ''); if ($code && isset($settingsByClass[$code])) { $fee = (float)$settingsByClass[$code]->fee_amount; }
                $presentAddressParts = [];
                if ($a->present_village) { $v=$a->present_village; if ($a->present_para_moholla) { $v .= ' ('.$a->present_para_moholla.')'; } $presentAddressParts[] = $v; }
                if ($a->present_post_office) { $presentAddressParts[] = $a->present_post_office; }
                if ($a->present_upazilla) { $presentAddressParts[] = $a->present_upazilla; }
                if ($a->present_district) { $presentAddressParts[] = $a->present_district; }
                $addr = implode(', ', $presentAddressParts);

                $serial = $i;
                $roll = $a->admission_roll_no ? str_pad((string)$a->admission_roll_no,3,'0',STR_PAD_LEFT) : '—';
                $dob = $a->dob ? $a->dob->format('Y-m-d') : '';
                $created = $a->created_at ? $a->created_at->format('Y-m-d H:i') : '';
                $statusTxt = $a->accepted_at ? ($lang==='bn' ? 'গৃহীত' : 'Accepted') : ($a->status==='cancelled' ? ($lang==='bn' ? 'বাতিল' : 'Cancelled') : ($lang==='bn' ? 'অপেক্ষমান' : 'Pending'));
                $payMethod = $mapMethod($latest?->payment_method);
                $feeTxt = $fee === null ? '—' : ('৳ '.number_format($fee,2));
                if ($lang==='bn') {
                    $serial = $toBn($serial);
                    $roll = $roll !== '—' ? $toBn($roll) : $roll;
                    $dob = $dob ? $toBn($dob) : '';
                    $created = $created ? $toBn($created) : '';
                    $feeTxt = $fee === null ? '—' : ('৳ '.$toBn(number_format($fee,2)));
                }
                fputcsv($out, [
                    $serial,
                    $lang==='bn' ? $toBn((string)($a->app_id ?? '')) : ($a->app_id ?? ''),
                    $roll,
                    $a->class_name,
                    $lang==='bn' ? ($a->name_bn ?: ($a->applicant_name ?: '')) : ($a->name_en ?: ($a->applicant_name ?: '')),
                    $lang==='bn' ? ($a->father_name_bn ?: '') : ($a->father_name_en ?: ''),
                    $lang==='bn' ? ($a->mother_name_bn ?: '') : ($a->mother_name_en ?: ''),
                    $lang==='bn' ? $toBn((string)($a->mobile ?: '')) : ($a->mobile ?: ''),
                    $dob,
                    $mapGender($a->gender),
                    $mapReligion($a->religion),
                    $addr,
                    $a->last_school ?: '',
                    $a->result ?: '',
                    $feeTxt,
                    $payMethod,
                    $statusTxt,
                    $created,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function summary(Request $request, School $school)
    {
        $base = AdmissionApplication::query()->where('school_id', $school->id);
        // Optional: filter by academic year if provided, otherwise use current admission year
        $yearId = (int) $request->query('year_id', $school->admission_academic_year_id ?: 0);
        if ($yearId) {
            $base->where('academic_year_id', $yearId);
        }

        $totalApps = (clone $base)->count();
        $acceptedApps = (clone $base)->whereNotNull('accepted_at')->count();
        $cancelledApps = (clone $base)->where('status','cancelled')->count();
        $paidApps = (clone $base)->where('payment_status','Paid')->count();
        $unpaidApps = (clone $base)
            ->where('status','!=','cancelled')
            ->where(function($q){ $q->whereNull('payment_status')->orWhere('payment_status','!=','Paid'); })
            ->count();

        // By class
        $byClass = (clone $base)
            ->select('class_name', DB::raw('COUNT(*) as total'))
            ->groupBy('class_name')
            ->orderByDesc('total')
            ->get();
        // By gender
        $byGender = (clone $base)
            ->select('gender', DB::raw('COUNT(*) as total'))
            ->groupBy('gender')
            ->orderByDesc('total')
            ->get();
        // By village (present_village)
        $byVillage = (clone $base)
            ->select('present_village', DB::raw('COUNT(*) as total'))
            ->groupBy('present_village')
            ->orderByDesc('total')
            ->get();
        // By previous school (last_school)
        $byPrevSchool = (clone $base)
            ->select('last_school', DB::raw('COUNT(*) as total'))
            ->groupBy('last_school')
            ->orderByDesc('total')
            ->get();

        // Payments summary
        $totalPaidAmount = \App\Models\AdmissionPayment::whereHas('application', function($q) use ($school, $yearId){
                $q->where('school_id', $school->id);
                if ($yearId) { $q->where('academic_year_id', $yearId); }
            })
            ->where('status','Completed')
            ->where(function($q){
                $q->where('fee_type','application')->orWhereNull('fee_type');
            })
            ->sum('amount');

        return view('principal.admissions.summary', compact(
            'school','yearId','totalApps','acceptedApps','cancelledApps','paidApps','unpaidApps','totalPaidAmount','byClass','byGender','byVillage','byPrevSchool'
        ));
    }

    public function payments(Request $request, School $school)
    {
        // Filters: status (Completed/Failed/Pending), date range (from/to), search (app_id/name)
        $status = $request->string('status')->trim()->toString();
        $from = $request->date('from');
        $to = $request->date('to');
        $search = $request->string('q')->trim()->toString();

        $query = \App\Models\AdmissionPayment::with(['application' => function($q){
                $q->select('id','school_id','app_id','name_en','name_bn','class_name','payment_status');
            }])
            ->whereHas('application', function($q) use ($school){
                $q->where('school_id', $school->id);
            });

        if ($status) {
            $query->where('status', $status);
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('transaction_id','like',"%$search%")
                  ->orWhereHas('application', function($qa) use ($search){
                      $qa->where('app_id','like',"%$search%")
                         ->orWhere('name_en','like',"%$search%")
                         ->orWhere('name_bn','like',"%$search%");
                  });
            });
        }

        $payments = $query->orderByDesc('id')->paginate(30)->appends($request->query());
        return view('principal.admissions.payments', compact('school','payments','status','from','to','search'));
    }

    public function paymentInvoice(School $school, \App\Models\AdmissionPayment $payment)
    {
        abort_if(optional($payment->application)->school_id !== $school->id, 404);
        $payment->load(['application']);
        return view('principal.admissions.payment_invoice', [
            'school' => $school,
            'payment' => $payment,
            'application' => $payment->application,
        ]);
    }
    public function accept(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->payment_status !== 'Paid') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','পেমেন্ট সম্পন্ন হয়নি, আবেদন গ্রহণ করা যাবে না');
        }
        if ($application->status === 'cancelled') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','বাতিলকৃত আবেদন গ্রহণযোগ্য নয়');
        }
        if (!$application->accepted_at) {
            DB::transaction(function () use ($school, $application) {
                // Compute class-prefixed roll: classNumber*1000 + sequence (within class)
                if (!$application->admission_roll_no) {
                    $classRaw = (string)($application->class_name ?? '');
                    $classNum = (int)preg_replace('/[^0-9]/','', $classRaw);
                    // Fallback to simple sequence if class number not found
                    if ($classNum <= 0) {
                        $q = AdmissionApplication::where('school_id', $school->id);
                        if ($application->academic_year_id) {
                            $q->where('academic_year_id', $application->academic_year_id);
                        }
                        $max = (int) ($q->lockForUpdate()->max('admission_roll_no') ?? 0);
                        $application->admission_roll_no = $max + 1;
                    } else {
                        // Count existing accepted/applications with same class to determine next sequence
                        $q = AdmissionApplication::where('school_id', $school->id)
                            ->where(function($qc) use ($classRaw) {
                                $qc->where('class_name', $classRaw);
                            });
                        if ($application->academic_year_id) {
                            $q->where('academic_year_id', $application->academic_year_id);
                        }
                        // lock for update to avoid race conditions
                        $acceptedCount = (int) ($q->lockForUpdate()->whereNotNull('accepted_at')->count());
                        $seq = max(min($acceptedCount + 1, 999), 1);
                        $prefix = $classNum * 1000;
                        $roll = (int) ($prefix + $seq);
                        // ensure uniqueness just in case
                        while (AdmissionApplication::where('school_id',$school->id)
                                ->when($application->academic_year_id, function($qa) use($application){ $qa->where('academic_year_id',$application->academic_year_id); })
                                ->where('admission_roll_no',$roll)->exists() && $seq < 999) {
                            $seq++;
                            $roll = (int) ($prefix + $seq);
                        }
                        $application->admission_roll_no = $roll;
                    }
                }
                $application->accepted_at = now();
                $application->status = 'accepted';
                $application->save();
            });
            // Send acceptance SMS
            $rollDisplay = str_pad((string)$application->admission_roll_no, 4, '0', STR_PAD_LEFT);
            $smsService = new \App\Services\SmsService($school);
            $message = "আপনার ভর্তি আবেদন গ্রহণ করা হয়েছে। ভর্তি রোল নং-{$rollDisplay}.-JSS";
            $smsService->sendSms($application->mobile, $message, 'admission_accept', [
                'recipient_type' => 'applicant',
                'recipient_id' => $application->id,
                'recipient_name' => $application->name_en,
            ]);
        }
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','আবেদন গ্রহণ করা হয়েছে');
    }

    public function show(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        $academicYear = null;
        if ($application->academic_year_id) {
            $academicYear = \App\Models\AcademicYear::find($application->academic_year_id);
        }
        return view('principal.admissions.show', compact('school','application','academicYear'));
    }

    public function copy(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if (strtolower($application->payment_status) !== 'paid') {
            return response()->view('admission.blocked', [
                'schoolCode' => $school->code,
                'title' => 'দেখার অনুমতি নেই',
                'message' => 'ফিস পরিশোধ হয় নাই। তাই দেখানো সম্ভব নয়।',
                'showLogout' => false,
            ], 403);
        }
        $payment = $application->payments()->latest()->first();
        return view('admission.application_copy', [
            'school' => $school,
            'application' => $application,
            'payment' => $payment,
        ]);
    }

    public function admitCard(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        // Show admit card only for accepted applications
        abort_unless((bool)$application->accepted_at, 404);
        $settings = Setting::forSchool($school->id)
            ->whereIn('key', ['admission_exam_datetime','admission_exam_venues'])
            ->pluck('value','key');
        $examDatetime = $settings->get('admission_exam_datetime');
        $venues = [];
        if ($settings->get('admission_exam_venues')) {
            $v = json_decode($settings->get('admission_exam_venues'), true);
            if (is_array($v)) { $venues = $v; }
        }
        return view('principal.admissions.admit_card', compact('school','application','examDatetime','venues'));
    }

    public function cancel(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        // Prevent cancelling if already enrolled
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','শিক্ষার্থী ভর্তি সম্পন্ন হয়েছে, আবেদন বাতিল করা যাবে না');
        }
        if ($application->status === 'cancelled') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ইতোমধ্যে আবেদন বাতিল করা হয়েছে');
        }
        $data = request()->validate([
            'cancellation_reason' => 'required|string|max:1000'
        ],[
            'cancellation_reason.required' => 'বাতিলের কারণ লিখতে হবে'
        ]);
        $application->accepted_at = null;
        $application->status = 'cancelled';
        $application->cancellation_reason = $data['cancellation_reason'];
        $application->save();
        // Send rejection SMS
        $smsService = new \App\Services\SmsService($school);
        $message = "আপনার ভর্তি আবেদন বাতিল করা হয়েছে। সঠিক তথ্য দিয়ে পুনারায় আবেদন করুন-JSS";
        $smsService->sendSms($application->mobile, $message, 'admission_reject', [
            'recipient_type' => 'applicant',
            'recipient_id' => $application->id,
            'recipient_name' => $application->name_en,
        ]);
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
            ->with('success','আবেদন বাতিল করা হয়েছে');
    }


    public function edit(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ভর্তি সম্পন্ন হওয়ায় আবেদন সম্পাদনা সম্ভব নয়');
        }
        return view('principal.admissions.edit', compact('school','application'));
    }

    public function update(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ভর্তি সম্পন্ন হওয়ায় আবেদন তথ্য পরিবর্তন করা যাবে না');
        }
        $data = request()->validate([
            'name_en' => 'required|string|max:191',
            'name_bn' => 'required|string|max:191',
            'father_name_en' => 'required|string|max:191',
            'father_name_bn' => 'nullable|string|max:191',
            'mother_name_en' => 'required|string|max:191',
            'mother_name_bn' => 'nullable|string|max:191',
            'guardian_name_en' => 'nullable|string|max:191',
            'guardian_name_bn' => 'nullable|string|max:191',
            'guardian_relation' => 'nullable|string|max:64',
            'gender' => 'required|string|max:16',
            'religion' => 'nullable|string|max:32',
            'blood_group' => 'nullable|string|max:8',
            'birth_reg_no' => 'nullable|string|max:64',
            'dob' => 'nullable|date|before:today',
            'mobile' => 'required|string|max:32',
            'class_name' => 'nullable|string|max:64',
            'last_school' => 'nullable|string|max:191',
            'result' => 'nullable|string|max:64',
            'pass_year' => 'nullable|string|max:8',
            'achievement' => 'nullable|string|max:500',
            // Present address
            'present_village' => 'nullable|string|max:191',
            'present_para_moholla' => 'nullable|string|max:191',
            'present_post_office' => 'nullable|string|max:191',
            'present_upazilla' => 'nullable|string|max:191',
            'present_district' => 'nullable|string|max:191',
            // Permanent address
            'permanent_village' => 'nullable|string|max:191',
            'permanent_para_moholla' => 'nullable|string|max:191',
            'permanent_post_office' => 'nullable|string|max:191',
            'permanent_upazilla' => 'nullable|string|max:191',
            'permanent_district' => 'nullable|string|max:191',
            // Photo
            'photo' => 'nullable|image|max:2048',
        ]);

        // Handle photo upload
        if (request()->hasFile('photo')) {
            $file = request()->file('photo');
            $name = 'app_'.$application->id.'_'.time().'.'.$file->getClientOriginalExtension();
            // Ensure directory exists and store on the public disk
            try { \Storage::disk('public')->makeDirectory('admission'); } catch (\Throwable $e) {}
            \Storage::disk('public')->putFileAs('admission', $file, $name);
            // Optionally remove old photo if exists
            if ($application->photo && \Storage::disk('public')->exists('admission/'.$application->photo)) {
                // Silent try-catch to avoid breaking if deletion fails
                try { \Storage::disk('public')->delete('admission/'.$application->photo); } catch (\Throwable $e) {}
            }
            $data['photo'] = $name;
        }

        $application->fill($data)->save();
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','আবেদন তথ্য আপডেট হয়েছে');
    }

    public function applicationPayments(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        $payments = $application->payments()->latest()->get();
        return view('principal.admissions.payment_details', compact('school','application','payments'));
    }

    public function resetPassword(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        // Find associated user by username (stored as APP ID during creation)
        $user = \App\Models\User::where('username', $application->app_id)->first();
        if (!$user) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','ইউজার পাওয়া যায়নি (username mismatch)');
        }
        // Generate new password (8 char mixed) for robustness
        $newPlain = \Illuminate\Support\Str::random(8);
        $user->password = bcrypt($newPlain);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users','password_changed_at')) {
            $user->password_changed_at = now();
        }
        $user->save();
        // Update application->data password & hashed variant
        $dataArr = is_array($application->data) ? $application->data : [];
        $dataArr['password'] = $newPlain;
        $dataArr['password_hashed'] = $user->password;
        $application->data = $dataArr;
        $application->save();
        // Send SMS via service and log
        $smsService = new \App\Services\SmsService($school);
        $message = "আপনার ভর্তি আবেদন পাসওয়ার্ড রিসেট করা হয়েছে। Username: {$application->app_id}, New Password: {$newPlain}.";
        $smsService->sendSms($application->mobile, $message, 'admission_password_reset', [
            'recipient_type' => 'applicant',
            'recipient_id' => $application->id,
            'recipient_name' => $application->name_en,
        ]);
        \Illuminate\Support\Facades\Log::info('sms_dispatch', [
            'type' => 'admission_password_reset',
            'school_code' => $school->code,
            'recipient' => $application->mobile,
            'app_id' => $application->app_id,
            'status' => 'sent'
        ]);
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','পাসওয়ার্ড রিসেট হয়েছে এবং এসএমএস পাঠানো হয়েছে');
    }
}
