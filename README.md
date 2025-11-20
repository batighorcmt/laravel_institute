<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Bulk Student Import (Excel/CSV)

বাংলা নির্দেশিকা — কীভাবে এক্সেল/CSV থেকে শিক্ষার্থীদের তথ্য ইমপোর্ট করবেন:

### ১. টেমপ্লেট ডাউনলোড
- Principal প্যানেল: `ইনস্টিটিউট > স্টুডেন্টস > Bulk Add` এ যান।
- "নমুনা টেমপ্লেট ডাউনলোড" বাটনে ক্লিক করে `students-template.xlsx` (অথবা CSV) নিন।

### ২. কলাম ব্যাখ্যা (আপডেটেড মিনিমাল রিকোয়ারমেন্ট)
নতুন নিয়ম অনুযায়ী শুধুমাত্র নিচের কলামগুলো বাধ্যতামূলক:
student_name_en, enroll_academic_year, enroll_class_id অথবা enroll_class_name, enroll_roll_no, status
status ফাঁকা থাকলে স্বয়ংক্রিয়ভাবে active ধরা হবে। বাকিগুলো সম্পূর্ণ ঐচ্ছিক (দিলে সংরক্ষণ হবে, না দিলে স্কিপ হবে)।

| কলাম | আবশ্যক | বিবরণ |
|-------|--------|--------|
| student_name_en | হ্যাঁ | শিক্ষার্থীর ইংরেজি নাম |
| student_name_bn | না | বাংলা নাম |
| enroll_academic_year | হ্যাঁ | একাডেমিক বছর (সংখ্যা) |
| enroll_class_id বা enroll_class_name | হ্যাঁ | ক্লাস আইডি অথবা মিল থাকা নাম |
| enroll_roll_no | হ্যাঁ | রোল নম্বর (ডুপ্লিকেট হলে সেই রো Enrollment স্কিপ) |
| status | হ্যাঁ (ফাঁকা হলে active) | active / inactive / graduated / transferred |
| date_of_birth | না | জন্ম তারিখ (YYYY-MM-DD বা DD/MM/YYYY) |
| gender | না | male / female |
| father_name / mother_name | না | অভিভাবকের নাম |
| father_name_bn / mother_name_bn | না | অভিভাবকের বাংলা নাম |
| guardian_phone | না | যোগাযোগ নম্বর |
| address | না | ঠিকানা |
| admission_date | না | ভর্তি তারিখ |
| blood_group | না | রক্তের গ্রুপ |
| enroll_section_id বা enroll_section_name | না | সেকশন আইডি অথবা নাম |
| enroll_group_id বা enroll_group_name | না | গ্রুপ আইডি অথবা নাম (যদি ক্লাস গ্রুপ ব্যবহার করে) |

### ৩. ইমপোর্ট মোড
- তাৎক্ষণিক (সিঙ্ক) ইমপোর্ট: ফাইল নির্বাচন করে "ফাইল আপলোড ও ইমপোর্ট"—ছোট ফাইলের জন্য দ্রুত।
- Queue ইমপোর্ট: "ফাইল আপলোড ও কিউতে পাঠান"—বড় ফাইল হলে ব্যাকগ্রাউন্ডে প্রসেস করবে। `php artisan queue:work` চালু থাকতে হবে।

### ৪. প্রগ্রেস ও রিপোর্ট
- Queue ইমপোর্টে প্রগ্রেস বার প্রসেসড রেকর্ডের শতকরা দেখায়।
- ব্যর্থ রেকর্ডগুলির জন্য আলাদা CSV রিপোর্ট (`bulk-report-<id>.csv`) ডাউনলোড লিঙ্ক দেখাবে।

### ৫. এক্সেল সাপোর্ট
- `maatwebsite/excel` প্যাকেজ ইনস্টল থাকলে (`composer require maatwebsite/excel`) সরাসরি XLSX/XLS/ODS ফাইল আপলোড করা যায়।
- না থাকলে CSV তে সেভ করে আপলোড করুন।

### ৬. সাধারণ সমস্যা
- Date format ভুল: ঐচ্ছিক হলেও দিলে নিশ্চিত করুন YYYY-MM-DD অথবা DD/MM/YYYY।
- Roll duplicate: একই বছর/ক্লাস/সেকশন/গ্রুপে যে রোল আগে আছে তা স্কিপ হবে (শিক্ষার্থী তৈরি হবে, শুধু Enrollment বাদ যাবে)।
- Gender মান দিলে male বা female হতে হবে।

### ৭. কিউ রান কমান্ড
```
php artisan queue:work --queue=default --tries=3
```

### ৮. কাস্টমাইজ
- অতিরিক্ত কলাম যোগ করতে চাইলে `ProcessStudentBulkImport` জব ও `bulkImport` মেথডে ম্যাপ আপডেট করুন।
- টেমপ্লেট হেডিং পরিবর্তন করতে `StudentBulkTemplateExport` সংশোধন করুন।

এই সেকশন প্রকল্পের স্টুডেন্ট বাল্ক ইমপোর্ট সম্পূর্ণভাবে ব্যবহারকারীকে গাইড করবে।
