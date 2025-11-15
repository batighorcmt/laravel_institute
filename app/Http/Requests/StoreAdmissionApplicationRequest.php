<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\School;

class StoreAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public form
    }

    public function rules(): array
    {
        // Determine current school and academic year for scoped uniqueness
        $code = $this->route('schoolCode') ?? $this->route('code');
        $school = $code ? School::where('code', $code)->first() : null;

        return [
            'name_en' => ['required','string','max:191','regex:/^[A-Za-z .\-]+$/'],
            'name_bn' => ['required','string','max:191','regex:/^[\x{0980}-\x{09FF} .\-]+$/u'],
            'father_name_en' => ['required','string','max:191','regex:/^[A-Za-z .\-]+$/'],
            'father_name_bn' => ['required','string','max:191','regex:/^[\x{0980}-\x{09FF} .\-]+$/u'],
            'mother_name_en' => ['required','string','max:191','regex:/^[A-Za-z .\-]+$/'],
            'mother_name_bn' => ['required','string','max:191','regex:/^[\x{0980}-\x{09FF} .\-]+$/u'],
            'guardian_relation' => 'required|in:father,mother,uncle,aunt,brother,sister,other',
            'guardian_name_en' => ['required_unless:guardian_relation,father,mother','nullable','string','max:191','regex:/^[A-Za-z .\-]+$/'],
            'guardian_name_bn' => ['required_unless:guardian_relation,father,mother','nullable','string','max:191','regex:/^[\x{0980}-\x{09FF} .\-]+$/u'],
            'gender' => 'required|in:Male,Female,Other',
            'religion' => 'nullable|string|max:32',
            'dob' => 'required|date|before:today',
            'mobile' => array_filter([
                'required',
                'regex:/^01\\d{9}$/',
                $school && $school->admission_academic_year_id
                    ? Rule::unique('admission_applications', 'mobile')
                        ->where(fn($q) => $q->where('school_id', $school->id)
                                            ->where('academic_year_id', $school->admission_academic_year_id))
                    : 'unique:admission_applications,mobile',
            ]),
            'birth_reg_no' => ['required','string','max:50','regex:/^\d+$/'],
            'present_address' => ['required','string','max:255'],
            'permanent_address' => ['required','string','max:255'],
            'class_name' => 'nullable|string|max:64',
            'last_school' => 'nullable|string|max:191',
            'result' => 'nullable|string|max:64',
            'pass_year' => 'nullable|string|max:8',
            // Allow larger uploads; server will resize to <=1MB passport size
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'ইংরেজি নাম আবশ্যক',
            'name_bn.required' => 'বাংলা নাম আবশ্যক',
            'father_name_en.required' => 'পিতার ইংরেজি নাম আবশ্যক',
            'father_name_bn.required' => 'পিতার বাংলা নাম আবশ্যক',
            'mother_name_en.required' => 'মাতার ইংরেজি নাম আবশ্যক',
            'mother_name_bn.required' => 'মাতার বাংলা নাম আবশ্যক',
            'guardian_relation.required' => 'অভিভাবকের সম্পর্ক নির্বাচন করুন',
            'guardian_name_en.required_unless' => 'অভিভাবকের ইংরেজি নাম আবশ্যক',
            'guardian_name_bn.required_unless' => 'অভিভাবকের বাংলা নাম আবশ্যক',
            'gender.required' => 'লিঙ্গ নির্বাচন করুন',
            'gender.in' => 'লিঙ্গ মান সঠিক নয়',
            'dob.required' => 'জন্ম তারিখ আবশ্যক',
            'dob.before' => 'জন্ম তারিখ ভবিষ্যৎ হতে পারবে না',
            'mobile.required' => 'মোবাইল নম্বর আবশ্যক',
            'mobile.regex' => 'মোবাইল নম্বর 01 দিয়ে শুরু ১১ সংখ্যার হতে হবে',
            'mobile.unique' => 'এই মোবাইল নম্বর দিয়ে পূর্বে একটি আবেদন করা হয়েছে',
            'birth_reg_no.required' => 'জন্ম নিবন্ধন নম্বর আবশ্যক',
            'birth_reg_no.regex' => 'জন্ম নিবন্ধন নম্বর শুধুমাত্র সংখ্যা হবে',
            'present_address.required' => 'বর্তমান ঠিকানা আবশ্যক',
            'permanent_address.required' => 'স্থায়ী ঠিকানা আবশ্যক',
            'name_en.regex' => 'ইংরেজি নাম ইংরেজি অক্ষরেই হতে হবে',
            'name_bn.regex' => 'বাংলা নাম শুধুমাত্র বাংলায় লিখতে হবে',
            'father_name_en.regex' => 'পিতার নাম (ইংরেজি) সঠিক নয়',
            'father_name_bn.regex' => 'পিতার নাম (বাংলা) সঠিক নয়',
            'mother_name_en.regex' => 'মাতার নাম (ইংরেজি) সঠিক নয়',
            'mother_name_bn.regex' => 'মাতার নাম (বাংলা) সঠিক নয়',
            'guardian_name_en.regex' => 'অভিভাবকের নাম (ইংরেজি) সঠিক নয়',
            'guardian_name_bn.regex' => 'অভিভাবকের নাম (বাংলা) সঠিক নয়',
            'photo.image' => 'ছবি ফাইল সঠিক নয়',
            'photo.mimes' => 'ছবি JPG বা PNG হতে হবে',
            'photo.max' => 'ছবি 512KB এর বেশি নয়',
        ];
    }
}
