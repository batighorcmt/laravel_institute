<?php

namespace Database\Seeders;

use App\Models\WebsitePageTemplate;
use Illuminate\Database\Seeder;

class WebsitePageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'about',
                'title' => 'About',
                'title_bn' => 'আমাদের সম্পর্কে',
                'default_slug' => 'about',
                'content_mode' => WebsitePageTemplate::MODE_DYNAMIC,
                'data_source' => 'about',
                'sort_order' => 1,
            ],
            [
                'key' => 'faculty',
                'title' => 'Faculty',
                'title_bn' => 'শিক্ষকমণ্ডলী',
                'default_slug' => 'faculty',
                'content_mode' => WebsitePageTemplate::MODE_DYNAMIC,
                'data_source' => 'teachers',
                'sort_order' => 2,
            ],
            [
                'key' => 'notice-board',
                'title' => 'Notice Board',
                'title_bn' => 'নোটিশ বোর্ড',
                'default_slug' => 'notice-board',
                'content_mode' => WebsitePageTemplate::MODE_DYNAMIC,
                'data_source' => 'notices',
                'sort_order' => 3,
            ],
            [
                'key' => 'gallery',
                'title' => 'Gallery',
                'title_bn' => 'গ্যালারি',
                'default_slug' => 'gallery',
                'content_mode' => WebsitePageTemplate::MODE_DYNAMIC,
                'data_source' => 'gallery',
                'sort_order' => 4,
            ],
            [
                'key' => 'contact',
                'title' => 'Contact',
                'title_bn' => 'যোগাযোগ',
                'default_slug' => 'contact-us',
                'content_mode' => WebsitePageTemplate::MODE_DYNAMIC,
                'data_source' => 'contact',
                'sort_order' => 5,
            ],
            [
                'key' => 'admission',
                'title' => 'Admission',
                'title_bn' => 'ভর্তি তথ্য',
                'default_slug' => 'admission-info',
                'content_mode' => WebsitePageTemplate::MODE_STATIC,
                'data_source' => null,
                'default_content' => '<h2>ভর্তি সংক্রান্ত তথ্য</h2><p>ভর্তি সংক্রান্ত বিস্তারিত তথ্যের জন্য অনুগ্রহ করে আমাদের অফিসে যোগাযোগ করুন অথবা অনলাইনে আবেদন করুন।</p><p><a href="/admission">অনলাইনে ভর্তি আবেদন করুন</a></p>',
                'sort_order' => 6,
            ],

            // Below: plain page containers only (title/slug). The Principal decides
            // dynamic-vs-static content per page after applying the template.
            ['key' => 'history', 'title' => 'History', 'title_bn' => 'প্রতিষ্ঠানের ইতিহাস', 'default_slug' => 'history', 'sort_order' => 10],
            ['key' => 'objectives', 'title' => 'Objectives', 'title_bn' => 'লক্ষ্য ও উদ্দেশ্য', 'default_slug' => 'objectives', 'sort_order' => 11],
            ['key' => 'vision-mission', 'title' => 'Vision & Mission', 'title_bn' => 'ভিশন ও মিশন', 'default_slug' => 'vision-mission', 'sort_order' => 12],

            ['key' => 'eiin-info', 'title' => 'EIIN', 'title_bn' => 'EIIN তথ্য', 'default_slug' => 'eiin', 'sort_order' => 13],
            ['key' => 'founding-info', 'title' => 'Founding', 'title_bn' => 'প্রতিষ্ঠাকাল', 'default_slug' => 'founding', 'sort_order' => 14],
            ['key' => 'recognition', 'title' => 'Recognition', 'title_bn' => 'অনুমোদন ও স্বীকৃতি', 'default_slug' => 'recognition', 'sort_order' => 15],

            ['key' => 'head-teacher', 'title' => 'Head Teacher', 'title_bn' => 'প্রধান শিক্ষক', 'default_slug' => 'head-teacher', 'sort_order' => 16],
            ['key' => 'assistant-head-teacher', 'title' => 'Assistant Head Teacher', 'title_bn' => 'সহকারী প্রধান শিক্ষক', 'default_slug' => 'assistant-head-teacher', 'sort_order' => 17],
            ['key' => 'staff', 'title' => 'Staff', 'title_bn' => 'কর্মকর্তা-কর্মচারী', 'default_slug' => 'staff', 'sort_order' => 18],
            ['key' => 'governing-body', 'title' => 'Governing Body', 'title_bn' => 'গভর্নিং বডি', 'default_slug' => 'governing-body', 'sort_order' => 19],

            ['key' => 'classes', 'title' => 'Classes', 'title_bn' => 'শ্রেণিসমূহ', 'default_slug' => 'classes', 'sort_order' => 20],
            ['key' => 'groups', 'title' => 'Groups', 'title_bn' => 'বিভাগ/গ্রুপ', 'default_slug' => 'groups', 'sort_order' => 21],
            ['key' => 'subjects', 'title' => 'Subjects', 'title_bn' => 'বিষয়সমূহ', 'default_slug' => 'subjects', 'sort_order' => 22],
            ['key' => 'syllabus', 'title' => 'Syllabus', 'title_bn' => 'সিলেবাস', 'default_slug' => 'syllabus', 'sort_order' => 23],
            ['key' => 'class-routine', 'title' => 'Class Routine', 'title_bn' => 'ক্লাস রুটিন', 'default_slug' => 'class-routine', 'sort_order' => 24],
            ['key' => 'academic-calendar', 'title' => 'Academic Calendar', 'title_bn' => 'একাডেমিক ক্যালেন্ডার', 'default_slug' => 'academic-calendar', 'sort_order' => 25],
            ['key' => 'exam-routine', 'title' => 'Exam Routine', 'title_bn' => 'পরীক্ষার রুটিন', 'default_slug' => 'exam-routine', 'sort_order' => 26],

            ['key' => 'student-count', 'title' => 'Student Count', 'title_bn' => 'শিক্ষার্থী সংখ্যা', 'default_slug' => 'student-count', 'sort_order' => 27],
            ['key' => 'gender-ratio', 'title' => 'Gender Ratio', 'title_bn' => 'ছেলে-মেয়ের সংখ্যা', 'default_slug' => 'gender-ratio', 'sort_order' => 28],
            ['key' => 'meritorious-students', 'title' => 'Meritorious Students', 'title_bn' => 'মেধাবী শিক্ষার্থী', 'default_slug' => 'meritorious-students', 'sort_order' => 29],
            ['key' => 'results', 'title' => 'Results', 'title_bn' => 'ফলাফল', 'default_slug' => 'results', 'sort_order' => 30],

            ['key' => 'admission-notice', 'title' => 'Admission Notice', 'title_bn' => 'ভর্তি বিজ্ঞপ্তি', 'default_slug' => 'admission-notice', 'sort_order' => 31],
            ['key' => 'admission-process', 'title' => 'Admission Process', 'title_bn' => 'আবেদন পদ্ধতি', 'default_slug' => 'admission-process', 'sort_order' => 32],
            ['key' => 'admission-eligibility', 'title' => 'Admission Eligibility', 'title_bn' => 'ভর্তির যোগ্যতা', 'default_slug' => 'admission-eligibility', 'sort_order' => 33],
            ['key' => 'admission-fees', 'title' => 'Admission Fees', 'title_bn' => 'ভর্তি ফি', 'default_slug' => 'admission-fees', 'sort_order' => 34],

            ['key' => 'downloads', 'title' => 'Downloads', 'title_bn' => 'ডাউনলোড', 'default_slug' => 'downloads', 'sort_order' => 35],
        ];

        foreach ($templates as $template) {
            WebsitePageTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
