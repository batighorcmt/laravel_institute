<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>বাতিঘর শিক্ষা প্রতিষ্ঠান ব্যবস্থাপনা সফটওয়্যার | Batighor Software Systems Ltd</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Hind Siliguri"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-gradient {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(14, 165, 233, 0.5);
        }
    </style>
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800 overflow-x-hidden selection:bg-brand-500 selection:text-white">
    <!-- Navbar -->
    <nav class="fixed w-full z-50 glass-panel bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <!-- Logo Placeholder -->
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30">
                        B
                    </div>
                    <span class="font-bold text-xl md:text-2xl tracking-tight text-slate-900 dark:text-white">BATIGHOR</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ url('/login') }}" class="hidden md:inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors shadow-sm">
                        লগইন
                    </a>
                    <a href="#contact" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full text-white bg-gradient-to-r from-brand-500 to-indigo-600 hover:from-brand-600 hover:to-indigo-700 transition-all shadow-md shadow-brand-500/30 hover:shadow-lg hover:-translate-y-0.5">
                        যোগাযোগ করুন
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden hero-gradient">
        <!-- Abstract Shapes -->
        <div class="absolute top-0 left-1/2 w-full -translate-x-1/2 h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[30%] -right-[10%] w-[70%] h-[70%] rounded-full bg-brand-500/20 blur-[120px]"></div>
            <div class="absolute top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-500/20 blur-[100px]"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-panel bg-white/5 border-white/10 text-brand-100 mb-8 animate-fade-in-up">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-500"></span>
                </span>
                <span class="text-sm font-medium">স্মার্ট এডুকেশন ম্যানেজমেন্ট</span>
            </div>
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight tracking-tight">
                বাতিঘর <br class="hidden md:block"/>
                <span class="text-brand-400">শিক্ষা প্রতিষ্ঠান ব্যবস্থাপনা</span> সফটওয়্যার
            </h1>
            
            <p class="mt-6 max-w-2xl mx-auto text-lg md:text-xl text-slate-300 leading-relaxed font-light">
                আপনার স্কুল, কলেজ বা মাদ্রাসার যাবতীয় কার্যক্রম ডিজিটাল ও স্বয়ংক্রিয় করার সবচেয়ে নির্ভরযোগ্য সমাধান। সময় ও খরচ বাঁচান, প্রতিষ্ঠানকে নিয়ে যান এক নতুন উচ্চতায়।
            </p>
            
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <a href="#features" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-full text-slate-900 bg-white hover:bg-slate-50 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-1 group">
                    সুবিধা সমূহ দেখুন
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
                <a href="{{ url('/login') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-full text-white glass-panel bg-white/10 hover:bg-white/20 transition-all hover:-translate-y-1">
                    সিস্টেম লগইন
                </a>
            </div>
        </div>
        
        <!-- Dashboard Preview Image/Mockup -->
        <div class="relative max-w-5xl mx-auto px-4 mt-20 z-10">
            <div class="rounded-2xl glass-panel bg-white/5 border border-white/10 p-2 shadow-2xl backdrop-blur-xl transform perspective-1000 rotate-x-12 hover:rotate-x-0 transition-all duration-700">
                <div class="rounded-xl overflow-hidden bg-slate-900 aspect-[16/9] flex items-center justify-center relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAzNHYtNGgydjRoLTJ6bTAtOGgtMnY0aDJ2LTR6bS00IDRoLTJ2NGgydi00em0wLThoLTh2NGg4di00em0tNCA0aC0ydjRoMnYtNHoiIGZpbGw9IiMzMzQxNTUiIGZpbGwtb3BhY2l0eT0iLjQiLz48L2c+PC9zdmc+')] opacity-20"></div>
                    <!-- Dummy UI elements to represent software -->
                    <div class="w-full h-full p-4 flex flex-col gap-4">
                        <div class="h-8 w-full bg-white/10 rounded-md flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="flex gap-4 h-full">
                            <div class="w-1/4 h-full bg-white/5 rounded-md flex flex-col gap-2 p-3">
                                <div class="h-4 w-3/4 bg-white/10 rounded"></div>
                                <div class="h-4 w-1/2 bg-white/10 rounded"></div>
                                <div class="h-4 w-2/3 bg-white/10 rounded"></div>
                            </div>
                            <div class="w-3/4 h-full flex flex-col gap-4">
                                <div class="flex gap-4 h-1/4">
                                    <div class="flex-1 bg-gradient-to-br from-brand-500/20 to-indigo-500/20 rounded-md border border-white/10 p-3">
                                         <div class="h-3 w-1/2 bg-white/20 rounded mb-2"></div>
                                         <div class="h-6 w-1/3 bg-white/30 rounded"></div>
                                    </div>
                                    <div class="flex-1 bg-gradient-to-br from-brand-500/20 to-indigo-500/20 rounded-md border border-white/10 p-3">
                                         <div class="h-3 w-1/2 bg-white/20 rounded mb-2"></div>
                                         <div class="h-6 w-1/3 bg-white/30 rounded"></div>
                                    </div>
                                    <div class="flex-1 bg-gradient-to-br from-brand-500/20 to-indigo-500/20 rounded-md border border-white/10 p-3">
                                         <div class="h-3 w-1/2 bg-white/20 rounded mb-2"></div>
                                         <div class="h-6 w-1/3 bg-white/30 rounded"></div>
                                    </div>
                                </div>
                                <div class="flex-1 bg-white/5 rounded-md p-4">
                                    <div class="w-full h-full border border-white/10 border-dashed rounded flex items-center justify-center">
                                        <p class="text-slate-500 font-medium">Dashboard Overview</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-50 dark:bg-slate-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-brand-600 font-semibold tracking-wide uppercase text-sm mb-2">কেন ব্যবহার করবেন?</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    সফটওয়্যারটির ব্যবহারের মূল সুবিধা ও কারণ
                </p>
                <p class="mt-4 max-w-2xl text-xl text-slate-500 dark:text-slate-400 mx-auto">
                    আপনার প্রতিষ্ঠানের দৈনন্দিন কাজগুলোকে সহজ, দ্রুত এবং নির্ভুল করার জন্য আমাদের রয়েছে একগুচ্ছ স্মার্ট ফিচার।
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">ডিজিটাল ব্যবস্থাপনা</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        ছাত্র-ছাত্রী, শিক্ষক ও কর্মচারীদের প্রোফাইল, তথ্য এবং ডকুমেন্টস অত্যন্ত সুশৃঙ্খলভাবে এক ক্লিকে পরিচালনা করুন।
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">অনলাইন ভর্তি ও ফি সংগ্রহ</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        ঘরে বসেই অনলাইনে ভর্তি প্রক্রিয়া সম্পন্ন এবং পেমেন্ট গেটওয়ের মাধ্যমে স্বয়ংক্রিয় ফি সংগ্রহ ও ডিজিটাল রসিদ প্রদান।
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">একাডেমিক রেজাল্ট ও রুটিন</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        নির্ভুলভাবে স্বয়ংক্রিয় মার্কশিট, মেরিট লিস্ট, ট্যাবুলেশন শিট এবং ডাইনামিক ক্লাস ও পরীক্ষার রুটিন তৈরি।
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">উপস্থিতি ও এসএমএস এলার্ট</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        বায়োমেট্রিক ডিভাইস ইন্টিগ্রেশন, ম্যানুয়াল হাজিরা এবং অনুপস্থিতির ক্ষেত্রে অভিভাবকদের কাছে তাৎক্ষণিক SMS নোটিফিকেশন।
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">নিরাপদ ও নির্ভরযোগ্য</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        উচ্চ ক্ষমতাসম্পন্ন ক্লাউড সার্ভার এবং ডাটাবেস ব্যাকআপ সুবিধা, ফলে তথ্য হারানোর কোনো ভয় নেই এবং ১০০% নিরাপত্তা নিশ্চিত।
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-100 dark:border-slate-700 feature-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400 mb-6">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">সময় ও অর্থের সাশ্রয়</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        ম্যানুয়াল খাতা-কলমের কাজের চাপ কমিয়ে, কাগজের খরচ বাঁচান এবং প্রশাসনিক কাজ দ্রুত সম্পন্ন করে সময় সাশ্রয় করুন।
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer / Contact Section -->
    <footer id="contact" class="bg-slate-900 pt-16 pb-8 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-24 mb-16">
                
                <!-- Company Info -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                            B
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white tracking-wider">BATIGHOR</h2>
                            <p class="text-brand-400 text-sm font-medium uppercase tracking-widest">Software Systems Ltd</p>
                        </div>
                    </div>
                    <p class="text-slate-400 mb-8 max-w-md leading-relaxed">
                        শিক্ষাপ্রতিষ্ঠান ডিজিটালাইজেশন এবং আইটি সেবায় বাংলাদেশের অন্যতম বিশ্বস্ত প্রতিষ্ঠান। আমাদের লক্ষ্য আধুনিক প্রযুক্তির মাধ্যমে শিক্ষা ব্যবস্থাকে আরও সহজ ও উন্নত করা।
                    </p>
                    <div class="flex space-x-4">
                        <a href="https://batighorbd.com" target="_blank" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </a>
                        <a href="mailto:info@batighorbd.com" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="glass-panel bg-white/5 p-8 rounded-2xl border border-white/10">
                    <h3 class="text-xl font-semibold text-white mb-6 border-b border-white/10 pb-4">যোগাযোগের ঠিকানা</h3>
                    <ul class="space-y-6">
                        <li class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-white font-medium mb-1">অফিস ঠিকানা</p>
                                <p class="text-slate-400 leading-relaxed text-sm">সোনালী ব্যাংকের সামনে, কুষ্টিয়া রোড,<br>জোড়পুকুরিয়া বাজার, গাংনী, মেহেরপুর।</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-white font-medium mb-1">মোবাইল নম্বর</p>
                                <p class="text-slate-400 text-sm">01762-396713<br>01885-926363</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-white font-medium mb-1">ইমেইল ও ওয়েবসাইট</p>
                                <p class="text-slate-400 text-sm">
                                    <a href="mailto:info@batighorbd.com" class="hover:text-brand-400 transition-colors">info@batighorbd.com</a><br>
                                    <a href="https://batighorbd.com" target="_blank" class="hover:text-brand-400 transition-colors">www.batighorbd.com</a>
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="pt-8 border-t border-slate-800 text-center flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} BATIGHOR SOFTWARE SYSTEMS LTD. সর্বস্বত্ব সংরক্ষিত।
                </p>
                <div class="flex space-x-6 text-sm text-slate-500">
                    <a href="#" class="hover:text-white transition-colors">গোপনীয়তা নীতি</a>
                    <a href="#" class="hover:text-white transition-colors">শর্তাবলী</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
