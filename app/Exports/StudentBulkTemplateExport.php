<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentBulkTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            // Mandatory basic identity
            'student_name_en','student_name_bn','date_of_birth','gender','blood_group',
            // Guardian & parents
            'father_name','father_name_bn','mother_name','mother_name_bn','guardian_phone','guardian_relation','guardian_name_en','guardian_name_bn',
            // Addresses (detailed components)
            'present_village','present_para_moholla','present_post_office','present_upazilla','present_district',
            'permanent_village','permanent_para_moholla','permanent_post_office','permanent_upazilla','permanent_district',
            // Backwards-compatible composed addresses
            'present_address','permanent_address',
            // Previous education
            'previous_school','pass_year','previous_result','previous_remarks',
            // Admission & status
            'admission_date','status',
            // Enrollment (names accepted if ids absent)
            'enroll_academic_year','enroll_class_id','enroll_class_name','enroll_section_id','enroll_section_name','enroll_group_id','enroll_group_name','enroll_roll_no'
        ];
    }

    public function array(): array
    {
        return [
            [
                // Identity
                'Rashid','রশিদ','2010-05-13','male','O+',
                // Parents + guardian
                'আবু তালেব','আবু তালেব','আছিয়া খাতুন','আছিয়া খাতুন','01700000000','father','আবু তালেব','আবু তালেব',
                // Addresses (components then composed)
                'জোরেপুকুরিয়া','পাড়া-১','গাংনী','গাংনী','মেহেরপুর',
                'জোরেপুকুরিয়া','পাড়া-১','গাংনী','গাংনী','মেহেরপুর',
                'গ্রাম: জোরেপুকুরিয়া, উপজেলা: গাংনী, জেলা: মেহেরপুর','গ্রাম: জোরেপুকুরিয়া, উপজেলা: গাংনী, জেলা: মেহেরপুর',
                // Previous education
                'Jorepukuria Govt Primary School','2022','A+','ভাল ছাত্র',
                // Admission & status
                '2023-01-15','active',
                // Enrollment
                '2023','1','Six','1','A','','','10'
            ]
        ];
    }
}
