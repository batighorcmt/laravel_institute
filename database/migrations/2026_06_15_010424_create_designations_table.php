<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->timestamps();
        });

        // Seed default designations
        $designations = [
            ['name_bn' => 'সভাপতি', 'name_en' => 'Chairman'],
            ['name_bn' => 'সহ-সভাপতি', 'name_en' => 'Vice Chairman'],
            ['name_bn' => 'সদস্য', 'name_en' => 'Member'],
            ['name_bn' => 'প্রধান শিক্ষক', 'name_en' => 'Head Teacher'],
            ['name_bn' => 'প্রধান শিক্ষিকা', 'name_en' => 'Headmistress'],
            ['name_bn' => 'অধ্যক্ষ', 'name_en' => 'Principal'],
            ['name_bn' => 'উপাধ্যক্ষ', 'name_en' => 'Vice Principal'],
            ['name_bn' => 'সহকারী প্রধান শিক্ষক', 'name_en' => 'Assistant Head Teacher'],
            ['name_bn' => 'সহকারী প্রধান শিক্ষিকা', 'name_en' => 'Assistant Headmistress'],
            ['name_bn' => 'ভারপ্রাপ্ত প্রধান শিক্ষক', 'name_en' => 'Acting Head Teacher'],
            ['name_bn' => 'সিনিয়র শিক্ষক', 'name_en' => 'Senior Teacher'],
            ['name_bn' => 'সহকারী শিক্ষক', 'name_en' => 'Assistant Teacher'],
            ['name_bn' => 'প্রভাষক', 'name_en' => 'Lecturer'],
            ['name_bn' => 'সিনিয়র প্রভাষক', 'name_en' => 'Senior Lecturer'],
            ['name_bn' => 'প্রশিক্ষক', 'name_en' => 'Instructor'],
            ['name_bn' => 'প্রদর্শক', 'name_en' => 'Demonstrator'],
            ['name_bn' => 'বিষয়ভিত্তিক শিক্ষক', 'name_en' => 'Subject Teacher'],
            ['name_bn' => 'শ্রেণি শিক্ষক', 'name_en' => 'Class Teacher'],
            ['name_bn' => 'ধর্মীয় শিক্ষক', 'name_en' => 'Religious Teacher'],
            ['name_bn' => 'ক্রীড়া শিক্ষক', 'name_en' => 'Sports Teacher'],
            ['name_bn' => 'চারু ও কারুকলা শিক্ষক', 'name_en' => 'Art Teacher'],
            ['name_bn' => 'সংগীত শিক্ষক', 'name_en' => 'Music Teacher'],
            ['name_bn' => 'আইসিটি শিক্ষক', 'name_en' => 'ICT Teacher'],
            ['name_bn' => 'গ্রন্থাগারিক', 'name_en' => 'Librarian'],
            ['name_bn' => 'সহকারী গ্রন্থাগারিক', 'name_en' => 'Assistant Librarian'],
            ['name_bn' => 'ল্যাব সহকারী', 'name_en' => 'Laboratory Assistant'],
            ['name_bn' => 'ল্যাব টেকনিশিয়ান', 'name_en' => 'Laboratory Technician'],
            ['name_bn' => 'কম্পিউটার অপারেটর', 'name_en' => 'Computer Operator'],
            ['name_bn' => 'আইটি অফিসার', 'name_en' => 'IT Officer'],
            ['name_bn' => 'সিস্টেম অ্যাডমিনিস্ট্রেটর', 'name_en' => 'System Administrator'],
            ['name_bn' => 'হিসাবরক্ষক', 'name_en' => 'Accountant'],
            ['name_bn' => 'প্রধান হিসাবরক্ষক', 'name_en' => 'Chief Accountant'],
            ['name_bn' => 'ক্যাশিয়ার', 'name_en' => 'Cashier'],
            ['name_bn' => 'প্রশাসনিক কর্মকর্তা', 'name_en' => 'Administrative Officer'],
            ['name_bn' => 'অফিস সুপার', 'name_en' => 'Office Superintendent'],
            ['name_bn' => 'অফিস সহকারী', 'name_en' => 'Office Assistant'],
            ['name_bn' => 'অফিস সহায়ক', 'name_en' => 'Office Support Staff'],
            ['name_bn' => 'উচ্চমান সহকারী', 'name_en' => 'Upper Division Assistant'],
            ['name_bn' => 'নিম্নমান সহকারী', 'name_en' => 'Lower Division Assistant'],
            ['name_bn' => 'স্টেনোগ্রাফার', 'name_en' => 'Stenographer'],
            ['name_bn' => 'ডাটা এন্ট্রি অপারেটর', 'name_en' => 'Data Entry Operator'],
            ['name_bn' => 'একাডেমিক সমন্বয়কারী', 'name_en' => 'Academic Coordinator'],
            ['name_bn' => 'পরীক্ষা নিয়ন্ত্রক', 'name_en' => 'Controller of Examinations'],
            ['name_bn' => 'সহকারী পরীক্ষা নিয়ন্ত্রক', 'name_en' => 'Assistant Controller of Examinations'],
            ['name_bn' => 'ভর্তি কর্মকর্তা', 'name_en' => 'Admission Officer'],
            ['name_bn' => 'ছাত্র কল্যাণ কর্মকর্তা', 'name_en' => 'Student Welfare Officer'],
            ['name_bn' => 'ক্যারিয়ার কাউন্সেলর', 'name_en' => 'Career Counselor'],
            ['name_bn' => 'চিকিৎসা সহকারী', 'name_en' => 'Medical Assistant'],
            ['name_bn' => 'নার্স', 'name_en' => 'Nurse'],
            ['name_bn' => 'দপ্তরী', 'name_en' => 'Office Attendant'],
            ['name_bn' => 'অফিস পিয়ন', 'name_en' => 'Peon'],
            ['name_bn' => 'আয়া', 'name_en' => 'Aya'],
            ['name_bn' => 'পরিচ্ছন্নতা কর্মী', 'name_en' => 'Cleaner'],
            ['name_bn' => 'ঝাড়ুদার', 'name_en' => 'Sweeper'],
            ['name_bn' => 'নিরাপত্তা প্রহরী', 'name_en' => 'Security Guard'],
            ['name_bn' => 'নৈশ প্রহরী', 'name_en' => 'Night Guard'],
            ['name_bn' => 'গেটকিপার', 'name_en' => 'Gatekeeper'],
            ['name_bn' => 'মালি', 'name_en' => 'Gardener'],
            ['name_bn' => 'ড্রাইভার', 'name_en' => 'Driver'],
            ['name_bn' => 'বাস সুপারভাইজার', 'name_en' => 'Bus Supervisor'],
            ['name_bn' => 'বিদ্যুৎ মিস্ত্রি', 'name_en' => 'Electrician'],
            ['name_bn' => 'প্লাম্বার', 'name_en' => 'Plumber'],
            ['name_bn' => 'কেয়ারটেকার', 'name_en' => 'Caretaker'],
            ['name_bn' => 'স্টোর কিপার', 'name_en' => 'Store Keeper'],
            ['name_bn' => 'হোস্টেল সুপার', 'name_en' => 'Hostel Superintendent'],
            ['name_bn' => 'সহকারী হোস্টেল সুপার', 'name_en' => 'Assistant Hostel Superintendent'],
            ['name_bn' => 'আবাসিক শিক্ষক', 'name_en' => 'Residential Teacher'],
            ['name_bn' => 'রান্নাঘর তত্ত্বাবধায়ক', 'name_en' => 'Kitchen Supervisor'],
            ['name_bn' => 'রাঁধুনি', 'name_en' => 'Cook'],
        ];

        $now = now();
        foreach ($designations as &$designation) {
            $designation['created_at'] = $now;
            $designation['updated_at'] = $now;
        }

        \Illuminate\Support\Facades\DB::table('designations')->insert($designations);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
