<?php

namespace Database\Seeders;

use App\Models\WebsiteTheme;
use Illuminate\Database\Seeder;

class WebsiteThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'slug' => 'classic-gold',
                'template_key' => WebsiteTheme::TEMPLATE_ONE,
                'name' => 'থিম ১ — ক্লাসিক গোল্ড',
                'description' => 'ডিফল্ট ইন্ডিগো-অ্যাম্বার থিম',
                'colors' => ['primary' => '#4f46e5', 'secondary' => '#1e1b4b', 'accent' => '#f59e0b', 'bg' => '#fefcf5', 'text' => '#1f2937'],
                'font_family' => "'Hind Siliguri', sans-serif",
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'royal-blue',
                'template_key' => WebsiteTheme::TEMPLATE_ONE,
                'name' => 'থিম ১ — রয়েল ব্লু',
                'description' => 'গাঢ় নীল ও আকাশী থিম',
                'colors' => ['primary' => '#2563eb', 'secondary' => '#0c1e3e', 'accent' => '#38bdf8', 'bg' => '#f8fafc', 'text' => '#1e293b'],
                'font_family' => "'Hind Siliguri', sans-serif",
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'slug' => 'emerald-green',
                'template_key' => WebsiteTheme::TEMPLATE_ONE,
                'name' => 'থিম ১ — এমারল্ড গ্রিন',
                'description' => 'সবুজ ও প্রকৃতিনির্ভর থিম',
                'colors' => ['primary' => '#059669', 'secondary' => '#022c22', 'accent' => '#facc15', 'bg' => '#f7fdf9', 'text' => '#1f2937'],
                'font_family' => "'Hind Siliguri', sans-serif",
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'slug' => 'heritage-pine',
                'template_key' => WebsiteTheme::TEMPLATE_TWO,
                'name' => 'থিম ২ — ঐতিহ্যবাহী (পাইন-মেরুন-গোল্ড)',
                'description' => 'সম্পূর্ণ ভিন্ন লেআউট: টপবার, স্টিকি নেভিগেশন, হিরো স্লাইডার, নোটিশ বোর্ড ও কুইক ইনফো প্যানেল, পরিসংখ্যান, স্টাফ ট্যাব, লাইটবক্স গ্যালারি সহ',
                'colors' => ['primary' => '#0d3d24', 'secondary' => '#7a1f2b', 'accent' => '#c89b3c', 'bg' => '#f8f4ea', 'text' => '#211c14'],
                'font_family' => "'Hind Siliguri', sans-serif",
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($themes as $theme) {
            WebsiteTheme::updateOrCreate(['slug' => $theme['slug']], $theme);
        }
    }
}
