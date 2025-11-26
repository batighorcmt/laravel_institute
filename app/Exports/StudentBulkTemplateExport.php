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
            // ** REQUIRED FIELDS: student_name_en, enroll_academic_year, enroll_roll_no, enroll_class_name, enroll_section_name **
            // All other fields are OPTIONAL - you can leave them blank
            
            // Basic identity
            'student_name_en','student_name_bn','date_of_birth','gender','blood_group','religion',
            // Guardian & parents
            'father_name','father_name_bn','mother_name','mother_name_bn','guardian_phone','guardian_relation','guardian_name_en','guardian_name_bn',
            // Addresses (detailed components)
            'present_village','present_para_moholla','present_post_office','present_upazilla','present_district',
            'permanent_village','permanent_para_moholla','permanent_post_office','permanent_upazilla','permanent_district',
            // Previous education
            'previous_school','pass_year','previous_result','previous_remarks',
            // Admission & status
            'admission_date','status',
            // Enrollment (REQUIRED: year, class_name, section_name, roll_no) - NO ID columns needed
            'enroll_academic_year','enroll_class_name','enroll_section_name','enroll_group_name','enroll_roll_no'
        ];
    }

    public function array(): array
    {
        return [
            // Example 1: Full data
            [
                // Identity
                'Rashid Ahmed','রশিদ আহমেদ','2010-05-13','male','O+','Islam',
                // Parents + guardian
                'আবু তালেব','আবু তালেব','আছিয়া খাতুন','আছিয়া খাতুন','01700000000','father','আবু তালেব','আবু তালেব',
                // Addresses (components then composed)
                'জোরেপুকুরিয়া','পাড়া-১','গাংনী','গাংনী','মেহেরপুর',
                'জোরেপুকুরিয়া','পাড়া-১','গাংনী','গাংনী','মেহেরপুর',
                // Previous education
                'Jorepukuria Govt Primary School','2022','A+','ভাল ছাত্র',
                // Admission & status
                '2023-01-15','active',
                // Enrollment (NO ID columns - only names)
                '2025','Six','A','Science','10'
            ],
            // Example 2: Minimum required fields only
            [
                // Identity (ONLY name_en required)
                'Fatema Akter','','','','','',
                // Parents + guardian (all optional)
                '','','','','','','','',
                // Addresses (all optional)
                '','','','','',
                '','','','','',
                // Previous education (all optional)
                '','','','',
                // Admission & status (optional)
                '','',
                // Enrollment (REQUIRED: year, class_name, section_name, roll_no)
                '2025','Seven','B','','5'
            ]
        ];
    }
}
