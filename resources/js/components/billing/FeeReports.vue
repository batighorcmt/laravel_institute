<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200">
                             <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </span>
                        ফি রিপোর্টস
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium">প্রতিষ্ঠানের আয় এবং বকেয়ার বিস্তারিত পরিসংখ্যান দেখুন</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 bg-white/80 backdrop-blur-md p-2 rounded-2xl shadow-sm border border-slate-200">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-5 py-2.5 rounded-xl text-sm font-black transition-all duration-300',
                            activeTab === tab.id
                                ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-200 translate-y-[-2px]'
                                : 'text-slate-500 hover:bg-slate-100'
                        ]"
                    >
                        {{ tab.name }}
                    </button>
                </div>
            </div>

            <!-- Global Filters (Hidden for Due Summary as it's static) -->
            <div v-if="activeTab !== 'due' && activeTab !== 'student_due'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 flex flex-wrap items-end gap-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শুরুর তারিখ</label>
                    <input type="date" v-model="filters.from_date" class="bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all outline-none border">
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শেষ তারিখ</label>
                    <input type="date" v-model="filters.to_date" class="bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all outline-none border">
                </div>
                <button @click="fetchReport" :disabled="loading" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black hover:bg-indigo-600 hover:shadow-xl hover:shadow-indigo-100 transition-all duration-300 disabled:opacity-50 flex items-center gap-2">
                    <svg v-if="loading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    রিপোর্ট আপডেট করুন
                </button>
            </div>

            <!-- Student Due Filters -->
            <div v-if="activeTab === 'student_due'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 flex flex-wrap items-end gap-6">
                <div class="space-y-2 w-full md:w-48">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ক্লাস</label>
                    <select v-model="filters.class_id" @change="fetchReport" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border appearance-none">
                        <option value="">সকল ক্লাস</option>
                        <option v-for="c in meta.classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="space-y-2 w-full md:w-48">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">সেকশন</label>
                    <select v-model="filters.section_id" @change="fetchReport" :disabled="!filters.class_id" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border appearance-none disabled:opacity-50">
                        <option value="">সকল সেকশন</option>
                        <option v-for="s in meta.sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <button @click="exportToCSV('student_due')" class="bg-slate-100 text-slate-700 px-6 py-3 rounded-2xl font-black hover:bg-slate-200 transition-all ml-auto">
                    এক্সপোর্ট লিস্ট
                </button>
            </div>

            <!-- Content Area -->
            <div v-if="loading" class="py-32 flex flex-col items-center justify-center">
                <div class="relative w-20 h-20">
                    <div class="absolute inset-0 rounded-full border-4 border-indigo-100"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin"></div>
                </div>
                <p class="mt-6 text-slate-400 font-bold tracking-widest uppercase text-[10px]">তথ্য অনুসন্ধান করা হচ্ছে</p>
            </div>

            <div v-else class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                <!-- Tab: Collection by Date -->
                <div v-if="activeTab === 'date'" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Left Stats -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all duration-700"></div>
                                <h3 class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em]">মোট সংগ্রহ</h3>
                                <div class="text-4xl font-black mt-3 flex items-baseline gap-1">
                                    <span class="text-xl">৳</span>
                                    {{ formatNumber(totalCollection) }}
                                </div>
                                <div class="mt-6 flex items-center gap-2 text-xs font-bold text-slate-400">
                                    <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                                    লাইভ আপডেট
                                </div>
                            </div>

                            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
                                <h3 class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-6">পেমেন্ট মেথড</h3>
                                <div class="space-y-5">
                                    <div v-for="item in collectionData.summary" :key="item.payment_method" class="flex flex-col gap-1">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-500 font-bold capitalize">{{ item.payment_method }}</span>
                                            <span class="text-slate-950 font-black">৳{{ formatShortNumber(item.total) }}</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-50 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000" :style="{ width: (item.total / totalCollection * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Chart & Table -->
                        <div class="lg:col-span-3 space-y-6">
                            <!-- Chart Card -->
                            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200 h-80 relative">
                                <h3 class="text-slate-800 font-black mb-4">সংগ্রহের ট্রেন্ড</h3>
                                <canvas ref="collectionChart"></canvas>
                            </div>

                            <!-- Table Card -->
                            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                                    <h3 class="text-slate-800 font-black">বিস্তারিত পরিসংখ্যান</h3>
                                    <button @click="exportToCSV('date')" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:text-indigo-800 transition-colors">এক্সপোর্ট CSV</button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                                <th class="px-8 py-5">তারিখ</th>
                                                <th class="px-8 py-5 text-right">সংগৃহীত পরিমাণ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                            <tr v-for="day in collectionData.daily_stats" :key="day.date" class="hover:bg-slate-50 transition-all">
                                                <td class="px-8 py-5 text-slate-700 font-bold">{{ formatDate(day.date) }}</td>
                                                <td class="px-8 py-5 text-right text-slate-950 font-black text-lg">৳ {{ formatNumber(day.total) }}</td>
                                            </tr>
                                            <tr v-if="!collectionData.daily_stats?.length">
                                                <td colspan="2" class="px-8 py-16 text-center text-slate-300 italic font-medium">কোন তথ্য পাওয়া যায়নি</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Collection by Teacher -->
                <div v-if="activeTab === 'teacher'" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-8 py-8 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-slate-800 font-black text-xl">সংগ্রহকারী ভিত্তিক রিপোর্ট</h3>
                            <p class="text-slate-400 text-sm mt-1 font-medium">কোন শিক্ষক বা ইউজার কত সংগ্রহ করেছেন তার তালিকা</p>
                        </div>
                        <button @click="exportToCSV('teacher')" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-indigo-600 transition-all">CSV ডাউনলোড</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                    <th class="px-8 py-6">শিক্ষকের নাম</th>
                                    <th class="px-8 py-6">পেমেন্ট মাধ্যম</th>
                                    <th class="px-8 py-6">লেনদেন সংখ্যা</th>
                                    <th class="px-8 py-6 text-right">মোট টাকা</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template v-for="(methods, teacher) in teacherData" :key="teacher">
                                    <tr v-for="(data, idx) in methods" :key="teacher + idx" class="group hover:bg-slate-50/80 transition-all">
                                        <td v-if="idx === 0" :rowspan="methods.length" class="px-8 py-6 text-slate-900 font-black border-r border-slate-50/50">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <div class="w-10 h-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 font-black">
                                                    {{ teacher.charAt(0) }}
                                                </div>
                                                {{ teacher }}
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-slate-600 font-bold">
                                            <span class="inline-flex items-center px-3 py-1 bg-white border border-slate-100 rounded-lg text-[10px] uppercase font-black tracking-wider text-slate-500 capitalize">
                                                {{ data.payment_method }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-slate-400 font-bold">{{ data.tx_count }} টি</td>
                                        <td class="px-8 py-6 text-right text-slate-950 font-black text-xl">৳ {{ formatNumber(data.total_amount) }}</td>
                                    </tr>
                                </template>
                                <tr v-if="Object.keys(teacherData).length === 0">
                                    <td colspan="4" class="px-8 py-24 text-center text-slate-300 italic font-medium">কোন তথ্য পাওয়া যায়নি</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Student Due List -->
                <div v-if="activeTab === 'student_due'" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-8 py-8 border-b border-slate-100">
                        <h3 class="text-slate-800 font-black text-xl">শিক্ষার্থী ভিত্তিক বকেয়া তালিকা</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                    <th class="px-8 py-6">শিক্ষার্থী</th>
                                    <th class="px-8 py-6">আইডি</th>
                                    <th class="px-8 py-6">রোল</th>
                                    <th class="px-8 py-6 text-right">বকেয়া পরিমাণ</th>
                                    <th class="px-8 py-6">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="student in studentDueList" :key="student.id" class="hover:bg-slate-50 transition-all">
                                    <td class="px-8 py-6">
                                        <div class="font-black text-slate-900">{{ student.name }}</div>
                                    </td>
                                    <td class="px-8 py-6 text-slate-500 font-bold uppercase tracking-wider text-xs">{{ student.student_id }}</td>
                                    <td class="px-8 py-6 text-slate-500 font-black">{{ student.roll }}</td>
                                    <td class="px-8 py-6 text-right">
                                        <span class="text-red-500 font-black text-lg">৳ {{ formatNumber(student.total_due) }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <a :href="'/billing/due?student_id=' + student.student_id" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:text-indigo-800">কালেক্ট করুন</a>
                                    </td>
                                </tr>
                                <tr v-if="studentDueList.length === 0">
                                    <td colspan="5" class="px-8 py-24 text-center text-slate-300 italic font-medium">কোন বকেয়া পাওয়া যায়নি</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Due Summary -->
                <div v-if="activeTab === 'due'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="item in dueData" :key="item.category_name" class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:shadow-red-100/50 transition-all duration-500 group border-t-[8px]" :style="{ borderTopColor: '#ef4444' }">
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h3 class="text-slate-900 font-black text-2xl tracking-tight">{{ item.category_name }}</h3>
                                <p class="text-slate-400 text-xs font-bold mt-1 uppercase tracking-widest">মোট {{ item.record_count }} টি এন্ট্রি</p>
                            </div>
                            <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-all duration-500 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex justify-between items-center group/item">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest">ধার্যকৃত</span>
                                <span class="text-slate-900 font-black text-lg tracking-tight">৳{{ formatNumber(item.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center group/item">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest">সংগৃহীত</span>
                                <span class="text-green-500 font-black text-lg tracking-tight">৳{{ formatNumber(item.total_paid) }}</span>
                            </div>
                            <div class="pt-6 border-t border-slate-50 flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-950 font-black uppercase text-xs tracking-widest">বকেয়া পরিমাণ</span>
                                    <span class="text-red-500 text-3xl font-black tracking-tighter shadow-red-100 drop-shadow-sm">৳{{ formatNumber(item.total_due) }}</span>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full h-2 bg-slate-50 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full" :style="{ width: (item.total_paid / item.total_amount * 100) + '%' }"></div>
                                </div>
                                <div class="text-[10px] text-slate-400 font-bold text-right">{{ Math.round(item.total_paid / item.total_amount * 100) }}% সংগৃহীত</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="dueData.length === 0" class="col-span-full py-32 text-center text-slate-300 italic font-medium">কোন বকেয়া পাওয়া যায়নি</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'FeeReports',
    data() {
        return {
            activeTab: 'date',
            loading: false,
            chart: null,
            tabs: [
                { id: 'date', name: 'কালেকশন (তারিখ)' },
                { id: 'teacher', name: 'কালেকশন (শিক্ষক)' },
                { id: 'student_due', name: 'বকেয়া তালিকা' },
                { id: 'due', name: 'সামারি রিপোর্ট' }
            ],
            filters: {
                from_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substr(0, 10),
                to_date: new Date().toISOString().substr(0, 10),
                class_id: '',
                section_id: ''
            },
            meta: {
                classes: [],
                sections: []
            },
            collectionData: {
                summary: [],
                daily_stats: [],
                details: {}
            },
            teacherData: {},
            dueData: [],
            studentDueList: []
        }
    },
    computed: {
        totalCollection() {
            return this.collectionData.summary?.reduce((acc, curr) => acc + parseFloat(curr.total), 0) || 0;
        }
    },
    watch: {
        activeTab: {
            immediate: true,
            handler(val) {
                this.fetchReport();
            }
        }
        ,
        'filters.class_id': function (newVal, oldVal) {
            this.fetchSections(newVal);
            if (!newVal) {
                this.filters.section_id = '';
            }
        }
    },
    mounted() {
        this.fetchMeta();
    },
    methods: {
        fetchMeta() {
            axios.get('/api/v1/meta/classes').then(res => this.meta.classes = res.data);
            // sections are loaded per-class when the user selects a class
            this.meta.sections = [];
        },
        fetchSections(classId) {
            if (!classId) {
                this.meta.sections = [];
                this.filters.section_id = '';
                return;
            }
            axios.get('/api/v1/meta/sections', { params: { class_id: classId } })
                .then(res => this.meta.sections = res.data)
                .catch(() => {
                    this.meta.sections = [];
                });
        },
        fetchReport() {
            this.loading = true;
            let url = '';

            if (this.activeTab === 'date') {
                url = `/api/v1/billing/reports/collection-by-date?from_date=${this.filters.from_date}&to_date=${this.filters.to_date}`;
            } else if (this.activeTab === 'teacher') {
                url = `/api/v1/billing/reports/collection-by-teacher?from_date=${this.filters.from_date}&to_date=${this.filters.to_date}`;
            } else if (this.activeTab === 'due') {
                url = `/api/v1/billing/reports/due-summary`;
            } else if (this.activeTab === 'student_due') {
                url = `/api/v1/billing/reports/student-dues?class_id=${this.filters.class_id}&section_id=${this.filters.section_id}`;
            }

            axios.get(url)
                .then(res => {
                    if (this.activeTab === 'date') {
                        this.collectionData = res.data;
                        this.$nextTick(() => this.renderChart());
                    } else if (this.activeTab === 'teacher') {
                        this.teacherData = res.data;
                    } else if (this.activeTab === 'due') {
                        this.dueData = res.data;
                    } else if (this.activeTab === 'student_due') {
                        this.studentDueList = res.data;
                    }
                })
                .catch(err => {
                    console.error('Error fetching report:', err);
                    toastr.error('রিপোর্ট লোড করতে ব্যর্থ হয়েছে।');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        renderChart() {
            if (this.chart) {
                this.chart.destroy();
            }

            const ctx = this.$refs.collectionChart?.getContext('2d');
            if (!ctx) return;

            // Sort dates for better visualization
            const sortedStats = [...(this.collectionData.daily_stats || [])].reverse();

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sortedStats.map(d => this.formatDate(d.date)),
                    datasets: [{
                        label: 'কালেকশন (৳)',
                        data: sortedStats.map(d => d.total),
                        borderColor: '#4f46e5',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return null;
                            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                            gradient.addColorStop(0, 'rgba(79, 70, 229, 0)');
                            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.15)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4f46e5',
                        borderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: {
                                font: { weight: 'bold', family: 'Hind Siliguri' },
                                callback: value => '৳' + value
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: 'bold', family: 'Hind Siliguri' } }
                        }
                    }
                }
            });
        },
        exportToCSV(type) {
            let data = [];
            let filename = `report_${type}_${new Date().getTime()}.csv`;

            if (type === 'date') {
                data = this.collectionData.daily_stats.map(d => ({
                    'তারিখ': d.date,
                    'মোট টাকা': d.total
                }));
            } else if (type === 'teacher') {
                Object.entries(this.teacherData).forEach(([teacher, methods]) => {
                    methods.forEach(m => {
                        data.push({
                            'সংগ্রহকারী': teacher,
                            'মাধ্যম': m.payment_method,
                            'লেনদেন': m.tx_count,
                            'মোট টাকা': m.total_amount
                        });
                    });
                });
            } else if (type === 'student_due') {
                data = this.studentDueList.map(s => ({
                    'শিক্ষার্থী': s.name,
                    'আইডি': s.student_id,
                    'রোল': s.roll,
                    'বকেয়া': s.total_due
                }));
            }

            if (!data.length) return;

            const csvContent = "data:text/csv;charset=utf-8,\uFEFF"
                + Object.keys(data[0]).join(",") + "\n"
                + data.map(row => Object.values(row).join(",")).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        formatNumber(num) {
            return parseFloat(num).toLocaleString('bn-BD', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },
        formatShortNumber(num) {
            if (num >= 1000) return (num/1000).toFixed(1) + 'k';
            return num;
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const options = { day: 'numeric', month: 'short' };
            return new Date(dateStr).toLocaleDateString('bn-BD', options);
        }
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
* {
    font-family: 'Hind Siliguri', sans-serif;
}
.animate-in {
    animation: animate-in 0.5s ease-out;
}
@keyframes animate-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

