<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">বকেয়া প্রিভিউ (Due Preview)</h1>
                    <p class="text-slate-500 mt-1">শিক্ষার্থীদের বকেয়া ফি-র বিস্তারিত তালিকা</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button @click="printReport" class="flex items-center gap-2 bg-white text-slate-700 px-4 py-2.5 rounded-xl font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        প্রিন্ট করুন
                    </button>
                </div>
            </div>

            <!-- Enhanced Filter Bar -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider px-1">শ্রেণি</label>
                        <select v-model="filters.class_id" @change="fetchSections" class="w-full bg-slate-50 border-slate-100 border rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none appearance-none transition-all">
                            <option value="">সকল শ্রেণি</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider px-1">শাখা</label>
                        <select v-model="filters.section_id" class="w-full bg-slate-50 border-slate-100 border rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none appearance-none transition-all disabled:opacity-50">
                            <option value="">সকল শাখা</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider px-1">ফি ক্যাটাগরি</label>
                        <select v-model="filters.fee_category_id" class="w-full bg-slate-50 border-slate-100 border rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none appearance-none transition-all">
                            <option value="">সকল ক্যাটাগরি</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider px-1">মাসের নাম</label>
                        <input type="month" v-model="filters.month" class="w-full bg-slate-50 border-slate-100 border rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider px-1">শিক্ষার্থী আইডি</label>
                        <input type="text" v-model="filters.student_id" placeholder="ID লিখুন" class="w-full bg-slate-50 border-slate-100 border rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>

                    <div class="flex gap-2">
                        <button @click="resetFilters" class="bg-slate-100 text-slate-600 px-4 py-3.5 rounded-xl font-bold hover:bg-slate-200 transition-all">
                            রিসেট
                        </button>
                        <button @click="fetchDueStudents" :disabled="loading" class="flex-1 bg-indigo-600 text-white px-4 py-3.5 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 flex items-center justify-center gap-2 disabled:opacity-50">
                            <svg v-if="loading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            খুঁজুন
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div v-if="students.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-indigo-600 rounded-3xl p-6 text-white shadow-xl shadow-indigo-100">
                    <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest">মোট শিক্ষার্থীর সংখ্যা</p>
                    <div class="text-4xl font-black mt-2">{{ students.length }} জন</div>
                </div>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 border-l-4 border-l-orange-500">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">মোট বকেয়া পরিমাণ</p>
                    <div class="text-4xl font-black mt-2 text-slate-800">৳ {{ formatNumber(totalDueAmount) }}</div>
                </div>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 border-l-4 border-l-green-500">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">গড় বকেয়া (প্রতি শিক্ষার্থী)</p>
                    <div class="text-4xl font-black mt-2 text-slate-800">৳ {{ formatNumber(totalDueAmount / students.length) }}</div>
                </div>
            </div>

            <!-- Students List -->
            <div v-if="!loading && students.length > 0" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black tracking-widest">
                            <tr>
                                <th class="px-6 py-4">শিক্ষার্থী</th>
                                <th class="px-6 py-4">রোল</th>
                                <th class="px-6 py-4 text-right">মোট বকেয়া</th>
                                <th class="px-6 py-4 text-right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="student in sortedStudents" :key="student.id" class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center font-black text-slate-400 group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                                            {{ student.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-slate-900 font-black">{{ student.name }}</div>
                                            <div class="text-slate-400 text-xs font-bold uppercase">{{ student.student_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg font-black text-xs">
                                        {{ student.roll || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-red-600 font-black text-lg">৳ {{ formatNumber(student.total_due) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="goToCollection(student.id)" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl font-bold text-xs hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                        কালেকশন করুন
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!loading && students.length === 0" class="bg-white rounded-3xl p-20 shadow-sm border border-slate-200 flex flex-col items-center justify-center text-center">
                <div class="h-24 w-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">কোন বকেয়া পাওয়া যায়নি!</h3>
                <p class="text-slate-400 mt-2 max-w-xs">অনুগ্রহ করে শ্রেণি বা শাখা পরিবর্তন করে পুনরায় চেষ্টা করুন।</p>
                <button @click="resetFilters" class="mt-8 text-indigo-600 font-bold hover:underline">ফিল্টার রিসেট করুন</button>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="loading" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="i in 3" :key="i" class="h-32 bg-white rounded-3xl animate-pulse"></div>
                </div>
                <div class="h-96 bg-white rounded-3xl animate-pulse"></div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'FeeDuePreview',
    data() {
        return {
            loading: false,
            classes: [],
            sections: [],
            students: [],
            filters: {
                class_id: '',
                section_id: '',
                fee_category_id: '',
                month: '',
                student_id: '',
                min_due: 0
            },
            categories: []
        }
    },
    computed: {
        totalDueAmount() {
            return this.students.reduce((acc, s) => acc + s.total_due, 0);
        },
        sortedStudents() {
            return [...this.students].sort((a, b) => b.total_due - a.total_due);
        }
    },
    mounted() {
        this.fetchFilters();
    },
    methods: {
        fetchFilters() {
            axios.get('/api/v1/meta/classes').then(res => { this.classes = res.data; });
            axios.get('/api/v1/billing/config').then(res => { this.categories = res.data.categories || []; });
        },
        fetchSections() {
            this.filters.section_id = '';
            if (!this.filters.class_id) {
                this.sections = [];
                return;
            }
            axios.get(`/api/v1/meta/sections?class_id=${this.filters.class_id}`).then(res => {
                this.sections = res.data;
            });
        },
        fetchDueStudents() {
            this.loading = true;
            const params = { ...this.filters };

            axios.get(`/api/v1/billing/reports/student-dues`, { params })
                .then(res => {
                    this.students = res.data;
                })
                .catch(err => {
                    console.error('Error fetching students:', err);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        formatNumber(num) {
            return parseFloat(num || 0).toLocaleString('bn-BD', { minimumFractionDigits: 2 });
        },
        resetFilters() {
            this.filters.class_id = '';
            this.filters.section_id = '';
            this.filters.fee_category_id = '';
            this.filters.month = '';
            this.filters.student_id = '';
            this.filters.min_due = 0;
            this.students = [];
        },
        goToCollection(studentId) {
            window.location.href = `/billing/collect?student_id=${studentId}`;
        },
        printReport() {
            window.print();
        }
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
* {
    font-family: 'Hind Siliguri', sans-serif;
}

@media print {
    .p-6 { padding: 0 !important; background: white !important; }
    .max-w-7xl { max-width: 100% !important; margin: 0 !important; }
    button, .filter-bar, .flex.justify-between, .flex.items-center.gap-3 {
        display: none !important;
    }
    .bg-slate-50 { background: white !important; }
    .shadow-sm, .shadow-xl { box-shadow: none !important; border: 1px solid #eee !important; }
    .rounded-3xl { border-radius: 0 !important; }
    .divide-slate-100 { border-color: #eee !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #eee !important; padding: 10px !important; }
}
</style>
