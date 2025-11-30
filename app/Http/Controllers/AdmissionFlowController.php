<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\AdmissionApplication;
use App\Models\AdmissionPayment;
use App\Models\Role;
use App\Models\SchoolPaymentSetting;
use App\Services\SSLCommerzClient;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\UserSchoolRole;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdmissionApplicationRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;

class AdmissionFlowController extends Controller
{
    protected function schoolByCode($code): School
    {
        return School::where('code',$code)->active()->firstOrFail();
    }

    public function instruction($code)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);
        return view('admission.instruction', compact('school'));
    }

    public function index($code)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);
        return view('admission.index', compact('school'));
    }

    public function apply($code)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);
        if (!$school->admission_academic_year_id) {
            return redirect()->back()->with('error','এই স্কুলে ভর্তি শিক্ষাবর্ষ সেট করা হয়নি');
        }
        if (!request()->session()->has('admission_consent_given')) {
            return redirect()->route('admission.instruction', ['schoolCode' => $code]);
        }
        // Fetch active & not expired class settings for this school & academic year (safe if migration not run)
        $classSettings = collect();
        if (Schema::hasTable('admission_class_settings')) {
            $classSettings = \App\Models\AdmissionClassSetting::forSchoolYear(
                    $school->id,
                    $school->admission_academic_year_id
                )
                ->active()
                ->notExpired()
                ->orderBy('class_code')
                ->get();
        }
        return view('admission.apply', compact('school','classSettings'));
    }

    public function submit($code, StoreAdmissionApplicationRequest $request)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);
        if (!$school->admission_academic_year_id) {
            return redirect()->back()->with('error','ভর্তি শিক্ষাবর্ষ সেট না থাকায় আবেদন গ্রহণ হয়নি');
        }
        $data = $request->validated();
        // Validate chosen class against active & not expired settings
        if (!empty($data['class_name'])) {
            $validSetting = \App\Models\AdmissionClassSetting::forSchoolYear(
                    $school->id,
                    $school->admission_academic_year_id
                )
                ->active()
                ->notExpired()
                ->where('class_code', $data['class_name'])
                ->first();
            if (!$validSetting) {
                return redirect()->back()->withInput()->with('error','নির্বাচিত শ্রেণির জন্য বর্তমানে আবেদন গ্রহণ করা হচ্ছে না বা সময়সীমা পেরিয়েছে');
            }
        } else {
            return redirect()->back()->withInput()->with('error','একটি শ্রেণি নির্বাচন করুন');
        }
        DB::beginTransaction();
        try {
            // Deterministic formatted APP ID: <SCHOOLCODE>_ADD<4-digit serial per academic year>
            $prefix = strtoupper($school->code).'_ADD';
            $serial = (int) (\App\Models\AdmissionApplication::where('school_id',$school->id)
                ->where('academic_year_id', $school->admission_academic_year_id)
                ->count()) + 1;
            $appId = $prefix . str_pad((string)$serial, 4, '0', STR_PAD_LEFT);
            // Ensure uniqueness defensively
            while (\App\Models\AdmissionApplication::where('app_id',$appId)->exists()) {
                $serial++;
                $appId = $prefix . str_pad((string)$serial, 4, '0', STR_PAD_LEFT);
            }
            $photoName = null;
            if ($request->hasFile('photo')) {
                $photoName = $appId.'_photo.jpg';
                $this->processAndStorePhoto($request->file('photo'), $photoName);
            }
            $application = AdmissionApplication::create([
                'school_id' => $school->id,
                'academic_year_id' => $school->admission_academic_year_id,
                'app_id' => $appId,
                'applicant_name' => $data['name_en'],
                'name_en' => $data['name_en'],
                'name_bn' => $data['name_bn'],
                'father_name_en' => $data['father_name_en'],
                'father_name_bn' => $data['father_name_bn'] ?? null,
                'mother_name_en' => $data['mother_name_en'],
                'mother_name_bn' => $data['mother_name_bn'] ?? null,
                'guardian_name_en' => $data['guardian_name_en'] ?? null,
                'guardian_name_bn' => $data['guardian_name_bn'] ?? null,
                'gender' => $data['gender'],
                'religion' => $data['religion'] ?? null,
                'dob' => $data['dob'],
                'mobile' => $data['mobile'],
                'birth_reg_no' => $data['birth_reg_no'],
                // Present address components
                'present_village' => $data['present_village'] ?? null,
                'present_para_moholla' => $data['present_para_moholla'] ?? null,
                'present_post_office' => $data['present_post_office'] ?? null,
                'present_upazilla' => $data['present_upazilla'] ?? null,
                'present_district' => $data['present_district'] ?? null,
                // Permanent address components
                'permanent_village' => $data['permanent_village'] ?? null,
                'permanent_para_moholla' => $data['permanent_para_moholla'] ?? null,
                'permanent_post_office' => $data['permanent_post_office'] ?? null,
                'permanent_upazilla' => $data['permanent_upazilla'] ?? null,
                'permanent_district' => $data['permanent_district'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'achievement' => $data['achievement'] ?? null,
                'guardian_relation' => $data['guardian_relation'] ?? null,
                'class_name' => $data['class_name'] ?? null,
                'last_school' => $data['last_school'] ?? null,
                'result' => $data['result'] ?? null,
                'pass_year' => $data['pass_year'] ?? null,
                'photo' => $photoName,
                'data' => [
                    'guardian_relation' => $data['guardian_relation'] ?? null,
                ],
                'payment_status' => 'Unpaid',
                'status' => 'pending',
            ]);
            // Create applicant user
            $applicantRole = Role::where('name', Role::APPLICANT)->first();
            $user = User::create([
                'name' => $application->name_en,
                'username' => $appId,
                'first_name' => $application->name_en,
                'email' => 'app_'.$appId.'@example.com',
                'password' => bcrypt(Str::random(12)),
                'phone' => $application->mobile,
            ]);
            UserSchoolRole::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'role_id' => $applicantRole->id,
                'status' => 'active'
            ]);
            // Generate applicant login password and persist to both user and application for portal login
            $password = Str::random(8);
            $user->password = bcrypt($password);
            $user->save();
            // Store for applicant portal login inside application data (no new DB column)
            $dataArr = is_array($application->data) ? $application->data : [];
            $dataArr['password'] = $password; // plaintext for convenience
            $dataArr['password_hashed'] = $user->password; // hashed variant
            $application->data = $dataArr;
            $application->save();

            $smsService = new \App\Services\SmsService($school);
            $message = "Your application submitted to {$school->name}. Username: {$appId}, Password: {$password}.";
            $smsService->sendSms($application->mobile, $message);
            // Log the SMS dispatch with type 'admission'
            Log::info('sms_dispatch', [
                'type' => 'admission',
                'school_code' => $school->code,
                'recipient' => $application->mobile,
                'app_id' => $application->app_id,
                'message' => $message,
                'status' => 'sent',
            ]);
            // Auto-login applicant session and redirect to preview (guarded)
            $request->session()->put('admission_applicant', [
                'app_id' => $application->app_id,
                'school_code' => $school->code,
                'name' => $application->name_bn ?? $application->name_en,
            ]);
            DB::commit();
            return redirect()->route('admission.preview', [$school->code, $application->app_id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error','ব্যর্থ: '.$e->getMessage());
        }
    }

    public function checkMobile($code, Request $request)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);
        $mobile = preg_replace('/\D+/', '', (string)$request->query('mobile'));
        if (!preg_match('/^01\d{9}$/', $mobile)) {
            return response()->json(['ok' => false, 'exists' => false, 'message' => 'অবৈধ মোবাইল ফরম্যাট']);
        }
        $exists = AdmissionApplication::where('school_id', $school->id)
            ->where('academic_year_id', $school->admission_academic_year_id)
            ->where('mobile', $mobile)
            ->exists();
        return response()->json([
            'ok' => true,
            'exists' => $exists,
            'message' => $exists ? 'এই মোবাইল নম্বর দিয়ে এই শিক্ষাবর্ষে একটি আবেদন আছে' : 'ব্যবহারযোগ্য',
        ]);
    }

    /**
     * Process uploaded photo to a square passport size (300x300),
     * compress to keep under ~1MB, and save as JPEG.
     */
    protected function processAndStorePhoto(UploadedFile $file, string $targetName): void
    {
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            // Fallback to raw store if not an image
            $file->storeAs('public/admission', $targetName);
            return;
        }
        $mime = $imageInfo['mime'] ?? '';
        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($file->getPathname());
                break;
            case 'image/png':
                $src = imagecreatefrompng($file->getPathname());
                break;
            case 'image/gif':
                $src = imagecreatefromgif($file->getPathname());
                break;
            default:
                $src = imagecreatefromstring(file_get_contents($file->getPathname()));
        }
        if (!$src) {
            $file->storeAs('public/admission', $targetName);
            return;
        }
        $w = imagesx($src); $h = imagesy($src);
        // Target passport ratio 35mm x 45mm => aspect = 35/45
        $targetW = 413; // ~35mm at 300 DPI
        $targetH = 531; // ~45mm at 300 DPI
        $targetAspect = $targetW / $targetH; // ~0.777...
        $srcAspect = $w / $h;
        // Compute crop box to match target aspect (center-crop)
        if ($srcAspect > $targetAspect) {
            // Source too wide: reduce width
            $cropH = $h;
            $cropW = (int) round($h * $targetAspect);
            $sx = (int) (($w - $cropW) / 2);
            $sy = 0;
        } else {
            // Source too tall: reduce height
            $cropW = $w;
            $cropH = (int) round($w / $targetAspect);
            $sx = 0;
            $sy = (int) (($h - $cropH) / 2);
        }
        $dst = imagecreatetruecolor($targetW, $targetH);
        // High-quality resampling to target size
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $targetW, $targetH, $cropW, $cropH);
        imagedestroy($src);

        // Compress to <= ~1MB by adjusting quality
        $quality = 85; // start
        $minQuality = 60;
        $data = null;
        do {
            ob_start();
            imagejpeg($dst, null, $quality);
            $data = ob_get_clean();
            if (strlen($data) <= 1024 * 1024 || $quality <= $minQuality) break;
            $quality -= 5;
        } while (true);
        imagedestroy($dst);

        // Store to disk
        $path = storage_path('app/public/admission');
        if (!is_dir($path)) @mkdir($path, 0775, true);
        file_put_contents($path . DIRECTORY_SEPARATOR . $targetName, $data);
    }

    public function preview($code, $appId)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$appId)->firstOrFail();
        // Enforce that only the logged-in applicant can view their own preview
        $sess = session('admission_applicant');
        if (!$sess || ($sess['school_code'] ?? null) !== $school->code || ($sess['app_id'] ?? null) !== $application->app_id) {
            return response()->view('admission.blocked', [
                'schoolCode' => $school->code,
                'title' => 'দেখার অনুমতি নেই',
                'message' => 'আবেদনকারী লগইন নেই বা প্রতিষ্ঠান মেলেনি।',
                'showLogout' => true,
            ], 403);
        }
        $fee = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('admission_class_settings') && $school->admission_academic_year_id) {
            $setting = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->where('class_code', $application->class_name)
                ->first();
            if ($setting) { $fee = (float) $setting->fee_amount; }
        }
        return view('admission.preview', compact('school','application','fee'));
    }

    public function paymentInitiate($code, Request $request)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$request->get('app_id'))->firstOrFail();
        $settings = SchoolPaymentSetting::where('school_id',$school->id)->first();
        if (!$settings || !$settings->active) {
            return redirect()->back()->with('error','পেমেন্ট সেটিংস সক্রিয় নেই');
        }
        // Dynamic fee based on class settings
        $classSetting = \App\Models\AdmissionClassSetting::forSchoolYear(
                $school->id,
                $school->admission_academic_year_id
            )
            ->where('class_code',$application->class_name)
            ->first();
        if (!$classSetting) {
            return redirect()->back()->with('error','এই শ্রেণির জন্য কোনো ফি সেট করা নেই');
        }
        $amount = (float) $classSetting->fee_amount;
        $tranId = 'TX'.strtoupper(Str::random(12));
        $invoice = 'INV'.date('Ymd').Str::upper(Str::random(6));
        $payment = AdmissionPayment::create([
            'admission_application_id' => $application->id,
            'amount' => $amount,
            'payment_method' => 'SSLCommerz',
            'tran_id' => $tranId,
            'invoice_no' => $invoice,
            'status' => 'Initiated'
        ]);
        $client = new SSLCommerzClient();
        $payload = [
            'store_id' => $settings->store_id,
            'store_passwd' => $settings->store_password,
            'total_amount' => $amount,
            'currency' => 'BDT',
            'tran_id' => $tranId,
            'success_url' => route('admission.payment.success', [$school->code, $application->app_id]),
            'fail_url' => route('admission.payment.fail', [$school->code, $application->app_id]),
            'cancel_url' => route('admission.payment.cancel', [$school->code, $application->app_id]),
            'ipn_url' => route('admission.payment.ipn'),
            'emi_option' => 0,
            'cus_name' => $application->name_en,
            'cus_phone' => $application->mobile,
            'cus_email' => 'noemail@example.com',
            'cus_add1' => $application->present_address ?? 'N/A',
            'cus_city' => 'City',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'product_name' => 'Admission Form',
            'product_category' => 'Admission',
            'product_profile' => 'general'
        ];
        $gatewayUrl = $client->initiate($payload, (bool)$settings->sandbox);
        if (!$gatewayUrl) {
            $payment->update(['status'=>'Failed']);
            return redirect()->back()->with('error','গেটওয়ে ত্রুটি');
        }
        return redirect()->away($gatewayUrl);
    }

    public function copy($code, $appId)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$appId)->firstOrFail();
        // Enforce that only the logged-in applicant can view their own copy
        $sess = session('admission_applicant');
        if (!$sess || ($sess['school_code'] ?? null) !== $school->code || ($sess['app_id'] ?? null) !== $application->app_id) {
            return response()->view('admission.blocked', [
                'schoolCode' => $school->code,
                'title' => 'দেখার অনুমতি নেই',
                'message' => 'কেবলমাত্র লগইনকৃত আবেদনকারীই তার আবেদনপত্র দেখতে পারবে।',
                'showLogout' => true,
            ], 403);
        }
        // Refresh latest payment info (avoid stale session issue)
        $payment = $application->payments()->latest()->first();
        if (strtolower($application->payment_status) !== 'paid') {
            // If any payment record marked Completed, update application and proceed
            $completed = $application->payments()->where('status','Completed')->latest()->first();
            if ($completed) {
                $application->payment_status = 'Paid';
                $application->save();
                $payment = $completed; // use the completed payment
            } else {
                // Not paid – redirect to preview with notice instead of blocked page
                return redirect()->route('admission.preview', [$school->code, $application->app_id])
                    ->with('error','পেমেন্ট সম্পন্ন হয়নি বা ব্যর্থ হয়েছে; আবেদন কপি দেখতে প্রথমে ফিস পরিশোধ করুন');
            }
        }
        return view('admission.application_copy', compact('school','application','payment'));
    }

    public function paymentSuccess($code, $appId, Request $request)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$appId)->firstOrFail();
        $tranId = $request->get('tran_id');
        $payment = AdmissionPayment::where('tran_id',$tranId)->where('admission_application_id',$application->id)->first();
        if ($payment) {
            $payment->update([
                'status' => 'Completed',
                'gateway_status' => $request->get('status'),
                'gateway_response' => $request->all()
            ]);
            $application->update(['payment_status'=>'Paid']);
        }
        // Auto-restore applicant session if lost during gateway redirect
        $sess = session('admission_applicant');
        if (!$sess || ($sess['app_id'] ?? null) !== $application->app_id) {
            session()->put('admission_applicant', [
                'app_id' => $application->app_id,
                'school_code' => $school->code,
                'name' => $application->name_bn ?? $application->name_en,
            ]);
        }
        return redirect()->route('admission.copy', [$school->code, $application->app_id])->with('success','পেমেন্ট সফল');
    }

    public function paymentFail($code, $appId, Request $request)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$appId)->firstOrFail();
        $tranId = $request->get('tran_id');
        $payment = AdmissionPayment::where('tran_id',$tranId)->where('admission_application_id',$application->id)->first();
        if ($payment) {
            $payment->update([
                'status' => 'Failed',
                'gateway_status' => $request->get('status'),
                'gateway_response' => $request->all()
            ]);
        }
        // Ensure applicant can retry payment by restoring session
        $sess = session('admission_applicant');
        if (!$sess || ($sess['app_id'] ?? null) !== $application->app_id) {
            session()->put('admission_applicant', [
                'app_id' => $application->app_id,
                'school_code' => $school->code,
                'name' => $application->name_bn ?? $application->name_en,
            ]);
        }
        return redirect()->route('admission.preview', [$school->code, $application->app_id])->with('error','পেমেন্ট ব্যর্থ হয়েছে');
    }

    public function paymentCancel($code, $appId, Request $request)
    {
        $school = $this->schoolByCode($code);
        $application = AdmissionApplication::where('school_id',$school->id)->where('app_id',$appId)->firstOrFail();
        $tranId = $request->get('tran_id');
        $payment = AdmissionPayment::where('tran_id',$tranId)->where('admission_application_id',$application->id)->first();
        if ($payment) {
            $payment->update([
                'status' => 'Failed',
                'gateway_status' => 'CANCELLED',
                'gateway_response' => $request->all()
            ]);
        }
        // Restore session for retry after cancellation
        $sess = session('admission_applicant');
        if (!$sess || ($sess['app_id'] ?? null) !== $application->app_id) {
            session()->put('admission_applicant', [
                'app_id' => $application->app_id,
                'school_code' => $school->code,
                'name' => $application->name_bn ?? $application->name_en,
            ]);
        }
        return redirect()->route('admission.preview', [$school->code, $application->app_id])->with('error','পেমেন্ট বাতিল করা হয়েছে');
    }

    public function paymentIpn(Request $request)
    {
        $tranId = $request->get('tran_id');
        $payment = AdmissionPayment::where('tran_id',$tranId)->first();
        if ($payment) {
            $payment->update([
                'gateway_status' => $request->get('status'),
                'gateway_response' => $request->all()
            ]);
        }
        return response('OK');
    }

    public function handleConsent(Request $request, $code)
    {
        $school = $this->schoolByCode($code);
        abort_unless($school->admissions_enabled, 404);

        $request->validate([
            'consent' => 'required|accepted',
        ]);

        $request->session()->put('admission_consent_given', true);

        return redirect()->route('admission.apply', ['schoolCode' => $code]);
    }
}
