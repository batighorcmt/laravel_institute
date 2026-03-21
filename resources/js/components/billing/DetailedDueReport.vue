<template>
    <div class="p-4 md:p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span class="p-2 bg-red-600 rounded-lg shadow-lg shadow-red-200">
                             <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        বকেয়া আদায় রিপোর্ট
                    </h1>
                    <p class="text-slate-500 mt-1 font-medium italic">শিক্ষাবর্ষ, শ্রেণি ও মাস অনুযায়ী বকেয়া তালিকা</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button @click="printReport" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl font-black hover:bg-slate-800 transition-all flex items-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        প্রিন্ট করুন
                    </button>
                    <button @click="exportCSV" class="bg-white text-slate-700 border border-slate-200 px-6 py-2.5 rounded-xl font-black hover:bg-slate-50 transition-all">
                        CSV এক্সপোর্ট
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 no-print">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">শিক্ষাবর্ষ</label>
                        <select v-model="filters.academic_year_id" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border appearance-none">
                            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name_bn || y.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">শ্রেণি</label>
                        <select v-model="filters.class_id" @change="onClassChange" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border appearance-none">
                            <option value="">সকল শ্রেণি</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.bangla_name || c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">শাখা</label>
                        <select v-model="filters.section_id" :disabled="!filters.class_id" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border appearance-none disabled:opacity-50">
                            <option value="">সকল শাখা</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.bangla_name || s.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">ফি ক্যাটাগরি</label>
                        <select v-model="filters.fee_category_id" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border appearance-none">
                            <option value="">সকল ক্যাটাগরি</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">মাসের নাম</label>
                        <input type="month" v-model="filters.month" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">শিক্ষার্থী আইডি</label>
                        <input type="text" v-model="filters.student_id" placeholder="ID লিখুন..." class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">পরিশোধের অবস্থা</label>
                        <select v-model="filters.status" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-4 focus:ring-red-500/10 outline-none border appearance-none">
                            <option value="all">সবগুলো (All)</option>
                            <option value="due">বকেয়া (সব)</option>
                            <option value="unpaid">অপরিশোধিত</option>
                            <option value="partial">আংশিক পরিশোধিত</option>
                            <option value="paid">পরিশোধিত</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <button @click="resetFilters" class="text-slate-400 hover:text-red-500 text-sm font-bold transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        ফিল্টার রিসেট করুন
                    </button>
                    <button @click="fetchReport" :disabled="loading" class="bg-red-600 text-white px-10 py-3 rounded-2xl font-black hover:bg-red-700 shadow-xl shadow-red-100 transition-all flex items-center gap-2">
                        <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        রিপোর্ট অনুসন্ধান করুন
                    </button>
                </div>
            </div>

            <!-- Result Table Container -->
            <div v-if="loading" class="p-32 flex flex-col items-center justify-center bg-white rounded-3xl border border-slate-200">
                <div class="w-16 h-16 border-4 border-red-100 border-t-red-600 rounded-full animate-spin"></div>
                <p class="mt-4 text-slate-400 font-bold uppercase tracking-widest text-[10px]">তথ্য অনুসন্ধান করা হচ্ছে</p>
            </div>

            <div v-else-if="fees.length === 0" class="p-32 text-center text-slate-300 italic font-medium bg-white rounded-3xl border border-slate-200"> 
                 কোন তথ্য পাওয়া যায়নি। অনুগ্রহ করে ফিল্টার পরিবর্তন করে আবার চেষ্টা করুন।
            </div>

            <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden print:shadow-none print:border-none print:m-0 print:p-0 print:bg-white">
                <!-- Single Report Header -->
                <div class="report-header-container p-8 pb-4 text-center border-b-2 border-red-600 hidden print:block">
                    <div class="report-header-inner flex items-center gap-10 mb-4 px-4 overflow-hidden">
                        <!-- Logo -->
                        <img v-if="school.logo_url" :src="school.logo_url" class="w-24 h-24 object-contain report-logo-print">
                        
                        <!-- Header Content -->
                        <div class="report-text-area text-left flex-1 p-2">
                            <!-- Institution Name -->
                            <h1 class="school-name-print text-4xl font-black text-slate-900 tracking-tight mb-2">{{ school.name_bn || school.name }}</h1>
                            
                            <!-- Address -->
                            <p class="school-address-print text-slate-700 font-bold text-xl mb-2 leading-tight">{{ school.address_bn || school.address }}</p>
                            
                            <!-- Phone & Email (Web Only) -->
                            <div class="flex gap-6 text-slate-600 font-black text-lg no-print">
                                <span v-if="school.phone">ফোন: {{ school.phone }}</span>
                                <span v-if="school.email">ই-মেইল: {{ school.email }}</span>
                            </div>

                            <!-- Report Name -->
                            <div class="report-title-print mt-4">
                                <span class="no-print bg-red-600 text-white py-2 px-10 inline-block rounded-full font-black text-2xl">বকেয়া আদায় রিপোর্ট</span>
                                <span class="print-only-text">বকেয়া আদায় রিপোর্ট</span>
                            </div>

                            <!-- Filters -->
                            <div class="report-filter-print mt-6 text-base font-black text-slate-600 border-t border-b border-slate-100 py-3 px-4 no-print">
                                <span>ফিল্টার: {{ activeFilterText }}</span>
                            </div>
                            <!-- Separated Filter line for print to match image style -->
                            <div class="report-filter-print print-only-text no-web" style="display:none">
                                {{ activeFilterText }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table-tight">
                        <thead class="print:table-header-group">
                            <tr class="bg-slate-900 text-white text-[13px] uppercase font-bold tracking-widest print:bg-white print:text-black print:border-b-2 print:border-black">
                                <th class="px-4 py-4 border-r border-slate-800 print:border-black text-center">ক্র: নং</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">শিক্ষার্থী ও আইডি</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">{{ dynamicHeading }}</th>
                                <th v-if="showCategoryMonthCol" class="px-6 py-4 border-r border-slate-800 print:border-black text-center">{{ catMonthHeading }}</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">নির্ধারিত</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">জরিমানা</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">মওকুফ</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">পরিশোধিত</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">বকেয়া</th>
                                <th class="px-6 py-4 border-r border-slate-800 print:border-black text-center">অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(fee, index) in fees" :key="index" class="hover:bg-slate-50 transition-all font-bold text-sm print:text-black print:break-inside-avoid">
                                <td class="px-4 py-2 text-slate-900 text-xs font-black border-r border-slate-50 print:border-black print:text-black text-center">{{ formatNumberBN(index + 1) }}</td>
                                <td class="px-6 py-2 border-r border-slate-50 print:border-black">
                                    <div class="font-black text-slate-900 leading-tight print:text-black">{{ fee.student_name_bn || fee.student_name_en }}</div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest print:text-black">{{ fee.student_code }}</div>
                                </td>
                                <td class="px-6 py-2 text-center border-r border-slate-50 print:border-black">
                                    <div v-if="!filters.class_id" class="text-slate-900 font-black leading-tight print:text-black">{{ fee.class_bangla_name || fee.class_name }}</div>
                                    <div class="text-slate-700 text-xs font-black leading-tight print:text-black">
                                        <span v-if="!filters.section_id">{{ fee.section_bangla_name || fee.section_name }} | </span>
                                        {{ formatNumberBN(fee.roll_no) }}
                                    </div>
                                </td>
                                <td v-if="showCategoryMonthCol" class="px-6 py-2 border-r border-slate-50 print:border-black">
                                    <div v-if="!filters.fee_category_id" class="font-black text-slate-900 leading-tight print:text-black">{{ fee.category_name }}</div>
                                    <div v-if="!filters.month && fee.month" class="text-[10px] text-slate-900 font-black print:text-black">{{ formatMonth(fee.month) }}</div>
                                    <div v-else-if="!filters.month" class="text-[10px] text-slate-400 italic print:text-black">এককালীন</div>
                                </td>
                                <td class="px-6 py-2 text-right font-black border-r border-slate-50 print:border-black text-slate-900 print:text-black">৳{{ formatNumber(fee.amount) }}</td>
                                <td class="px-6 py-2 text-right text-slate-900 border-r border-slate-50 print:border-black print:text-black">৳{{ formatNumber(fee.fine_amount) }}</td>
                                <td class="px-6 py-2 text-right text-green-600 border-r border-slate-50 print:border-black print:text-black">৳{{ formatNumber(fee.fine_waiver) }}</td>
                                <td class="px-6 py-2 text-right text-slate-900 font-black border-r border-slate-50 print:border-black print:text-black">৳{{ formatNumber(fee.paid_amount) }}</td>
                                <td class="px-6 py-2 text-right font-black text-red-600 text-sm bg-red-50/20 print:bg-transparent print:text-black print:border-r print:border-black">
                                    ৳{{ formatNumber(calculateFeeDue(fee)) }}
                                </td>
                                <td class="px-6 py-2 text-center">
                                    <span :class="getStatusBadgeClass(fee.status)" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest shadow-xs print:text-black print:border print:border-black">
                                        {{ getStatusText(fee.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="print:table-footer-group">
                            <tr class="bg-slate-900 text-white font-bold print:bg-white print:text-black print:border-t-2 print:border-black">
                                <td :colspan="showCategoryMonthCol ? 4 : 3" class="px-6 py-2 text-right uppercase tracking-widest text-sm print:text-black">সর্বমোট</td>
                                <td class="px-6 py-2 text-right text-sm border-l border-slate-800 print:border-black print:text-black">৳{{ formatNumber(grandTotals.amount) }}</td>
                                <td class="px-6 py-2 text-right text-sm border-l border-slate-800 print:border-black print:text-black">৳{{ formatNumber(grandTotals.fine) }}</td>
                                <td class="px-6 py-2 text-right text-sm border-l border-slate-800 print:border-black print:text-black text-green-400 print:text-black">৳{{ formatNumber(grandTotals.waiver) }}</td>
                                <td class="px-6 py-2 text-right text-sm border-l border-slate-800 print:border-black print:text-black">৳{{ formatNumber(grandTotals.paid) }}</td>
                                <td class="px-6 py-2 text-right text-sm border-l border-slate-800 print:border-black print:text-black">৳{{ formatNumber(grandTotals.due) }}</td>
                                <td class="print:border-l print:border-black"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Print Footer for Date/Time and Signatures -->
                <div class="hidden print:block p-8 pt-4">
                    <p class="text-sm font-black text-slate-900 print:text-black mb-10">প্রিন্ট তারিখ ও সময়: {{ formatDateTime(new Date()) }}</p>
                    
                    <div class="flex justify-between items-end mt-12 bg-white print:text-black print:break-inside-avoid">
                        <div class="text-center">
                            <div class="w-44 border-t-2 border-black mt-4 pt-2 text-sm font-black uppercase print:text-black">আদায়কারী</div>
                        </div>
                        <div class="text-center">
                            <div class="w-44 border-t-2 border-black mt-4 pt-2 text-sm font-black uppercase print:text-black">যাচাইকারী</div>
                        </div>
                        <div class="text-center">
                            <div class="w-44 border-t-2 border-black mt-4 pt-2 text-sm font-black uppercase print:text-black">প্রধান শিক্ষক</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'DetailedDueReport',
    data() {
        return {
            loading: false,
            fees: [],
            classes: [],
            sections: [],
            academicYears: [],
            categories: [],
            school: { name: '', address: '', phone: '', email: '' },
            filters: {
                academic_year_id: '',
                class_id: '',
                section_id: '',
                fee_category_id: '',
                month: '',
                student_id: '',
                status: 'all'
            }
        }
    },
    computed: {
        grandTotals() {
            return this.fees.reduce((acc, fee) => {
                const amount = parseFloat(fee.amount) || 0;
                const fine = parseFloat(fee.fine_amount) || 0;
                const waiver = parseFloat(fee.fine_waiver) || 0;
                const paid = parseFloat(fee.paid_amount) || 0;
                const due = this.calculateFeeDue(fee);
                
                acc.amount += amount;
                acc.fine += fine;
                acc.waiver += waiver;
                acc.paid += paid;
                acc.due += due;
                return acc;
            }, { amount: 0, fine: 0, waiver: 0, paid: 0, due: 0 });
        },
        dynamicHeading() {
            if (this.filters.class_id && this.filters.section_id) return 'রোল নং';
            if (this.filters.class_id) return 'শাখা / রোল';
            return 'শ্রেণি / শাখা / রোল';
        },
        showCategoryMonthCol() {
            return !(this.filters.fee_category_id && this.filters.month);
        },
        catMonthHeading() {
            if (this.filters.fee_category_id) return 'মাসের নাম';
            if (this.filters.month) return 'ফি ক্যাটাগরি';
            return 'ফি ক্যাটাগরি ও মাস';
        },
        activeFilterText() {
            let text = [];
            if (this.filters.academic_year_id) {
                const y = this.academicYears.find(x => x.id == this.filters.academic_year_id);
                if (y) text.push('বর্ষ: ' + (y.name_bn || y.name));
            }
            if (this.filters.class_id) {
                const c = this.classes.find(x => x.id == this.filters.class_id);
                if (c) text.push('শ্রেণি: ' + (c.bangla_name || c.name_bangla || c.name_bn || c.name));
            }
            if (this.filters.section_id) {
                const s = this.sections.find(x => x.id == this.filters.section_id);
                if (s) text.push('শাখা: ' + (s.bangla_name || s.name_bangla || s.name_bn || s.name));
            }
            if (this.filters.fee_category_id) {
                const cat = this.categories.find(x => x.id == this.filters.fee_category_id);
                if (cat) text.push('ক্যাটাগরি: ' + cat.name);
            }
            if (this.filters.month) text.push('মাস: ' + this.formatMonth(this.filters.month));
            if (this.filters.status) text.push('অবস্থা: ' + this.getStatusText(this.filters.status));
            return text.join(' | ') || 'সবগুলো';
        }
    },
    mounted() {
        this.fetchConfig();
        this.fetchMeta();
        this.fetchSchoolInfo();
    },
    methods: {
        calculateFeeDue(fee) {
            const amount = parseFloat(fee.amount) || 0;
            const fine = parseFloat(fee.fine_amount) || 0;
            const paid = parseFloat(fee.paid_amount) || 0;
            const waiver = parseFloat(fee.fine_waiver) || 0;
            return Math.max(0, (amount - paid) + (fine - waiver));
        },
        fetchConfig() {
            axios.get('/api/v1/billing/config').then(res => {
                this.categories = res.data.categories;
            });
        },
        fetchMeta() {
            axios.get('/api/v1/meta/classes').then(res => this.classes = res.data);
        },
        fetchSchoolInfo() {
            axios.get('/api/v1/meta/school').then(res => {
                this.school = res.data;
                this.academicYears = res.data.academic_years || [];
                if (res.data.current_academic_year) {
                    this.filters.academic_year_id = res.data.current_academic_year.id;
                } else if (this.academicYears.length > 0) {
                    const current = this.academicYears.find(y => y.is_current) || this.academicYears[0];
                    this.filters.academic_year_id = current.id;
                }
            });
        },
        onClassChange() {
            this.filters.section_id = '';
            if (this.filters.class_id) {
                axios.get('/api/v1/meta/sections', { params: { class_id: this.filters.class_id } })
                    .then(res => this.sections = res.data);
            } else {
                this.sections = [];
            }
        },
        resetFilters() {
            const currentYearId = this.academicYears.find(y => y.is_current)?.id || (this.academicYears.length > 0 ? this.academicYears[0].id : '');
            this.filters = {
                academic_year_id: currentYearId,
                class_id: '',
                section_id: '',
                fee_category_id: '',
                month: '',
                student_id: '',
                status: 'all'
            };
            this.fees = [];
        },
        fetchReport() {
            if (!this.filters.academic_year_id) return toastr.warning('শিক্ষাবর্ষ নির্বাচন করুন');
            this.loading = true;
            axios.get('/api/v1/billing/reports/detailed-dues', { params: this.filters })
                .then(res => {
                    this.fees = res.data;
                })
                .catch(err => {
                    toastr.error('রিপোর্ট লোড করতে ব্যর্থ হয়েছে।');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        formatNumber(num) {
            return parseFloat(num).toLocaleString('bn-BD', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
        formatNumberBN(num) {
            if (num === null || num === undefined) return '';
            const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
            return num.toString().replace(/\d/g, d => bnDigits[parseInt(d)]);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('bn-BD');
        },
        formatDateTime(date) {
            const dt = new Date(date);
            const dateStr = dt.toLocaleDateString('bn-BD');
            const timeStr = dt.toLocaleTimeString('bn-BD', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            return `${dateStr} ${timeStr}`;
        },
        formatMonth(val) {
            if (!val) return '';
            const [y, m] = val.split('-');
            const d = new Date(y, m - 1);
            return d.toLocaleString('bn-BD', { month: 'long', year: 'numeric' });
        },
        getStatusText(status) {
            const map = {
                unpaid: 'অপরিশোধিত',
                partial: 'আংশিক',
                paid: 'পরিশোধিত',
                due: 'বকেয়া (সব)',
                all: 'সবগুলো'
            };
            return map[status] || status;
        },
        getStatusBadgeClass(status) {
            const map = {
                unpaid: 'bg-red-50 text-red-600',
                partial: 'bg-yellow-50 text-yellow-600',
                paid: 'bg-green-50 text-green-600'
            };
            return map[status] || 'bg-slate-100';
        },
        printReport() {
            window.print();
        },
        exportCSV() {
            if (this.fees.length === 0) return;
            let csv = "\uFEFF";
            csv += "শিক্ষার্থী,আইডি,শ্রেণি, শাখা, রোল, ক্যাটাগরি, মাস, নির্ধারিত, জরিমানা, মওকুফ, পরিশোধিত, বকেয়া\n";
            this.fees.forEach(f => {
                const due = this.calculateFeeDue(f);
                csv += `"${f.student_name_bn}","${f.student_code}","${f.class_bangla_name || f.class_name}","${f.section_bangla_name || f.section_name}","${f.roll_no}","${f.category_name}","${f.month || ''}","${f.amount}","${f.fine_amount}","${f.fine_waiver}","${f.paid_amount}","${due}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `detailed_due_report_${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}
</script>

<style>
.table-tight td, .table-tight th {
    padding-top: 0.2rem !important;
    padding-bottom: 0.2rem !important;
}

@media print {
    body * { visibility: hidden; }
    .print-only, .print-only * { visibility: visible; }
    .no-print { display: none !important; }
    .content-wrapper { padding: 0 !important; margin: 0 !important; }
    .max-w-7xl { max-width: 100% !important; width: 100% !important; margin: 0 !important; }
    .bg-slate-50 { background: white !important; }
    
    .bg-white, .bg-white * { visibility: visible; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    .no-web { display: none !important; }

    /* Remove container artifacts in print */
    .bg-white.rounded-3xl.shadow-sm.border.border-slate-200 {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .p-4, .p-6, .md\:p-6, .p-8 { padding: 0 !important; }
    .space-y-6 > * + * { margin-top: 0 !important; }
    .bg-slate-50 { background: white !important; }

    /* Dedicated Print Header Styles */
    .report-header-container {
        border-bottom: 4px solid #000 !important;
        padding-bottom: 2rem !important;
        margin-bottom: 2rem !important;
        text-align: center !important;
        background: white !important;
    }
    
    .report-header-inner {
        position: relative !important;
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .report-logo-print {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 120px !important;
        height: 120px !important;
        object-fit: contain !important;
        visibility: visible !important;
    }
    
    .report-text-area {
        width: 100% !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .school-name-print {
        font-size: 36pt !important;
        font-weight: 900 !important;
        color: #000 !important;
        margin: 0 !important;
        line-height: 1.1 !important;
        text-align: center !important;
    }
    
    .school-address-print {
        font-size: 18pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        margin: 8px 0 0 0 !important;
        text-align: center !important;
    }
    
    .report-title-print {
        margin: 15px 0 0 0 !important;
        text-align: center !important;
    }
    
    .print-only-text {
        display: block !important;
        font-size: 24pt !important;
        font-weight: 800 !important;
        color: #000 !important;
        text-align: center !important;
    }
    
    .report-filter-print {
        font-size: 16pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        margin-top: 10px !important;
        text-align: center !important;
        border: none !important;
        padding: 0 !important;
    }

    .no-web.report-filter-print {
        display: block !important;
    }
    
    table { 
        width: 100% !important; 
        border-collapse: collapse !important; 
        border: none !important;
        page-break-inside: auto !important;
    }
    th, td { 
        border: 1px solid #000 !important;
        padding: 6px 8px !important;
        color: #000 !important;
        font-size: 11pt !important;
    }
    
    td div { font-size: 11pt !important; }
    td .text-\[10px\], td .text-xs { font-size: 10pt !important; }
    td span { font-size: 10pt !important; }
    
    .print\:table-header-group { display: table-header-group !important; }
    .print\:table-footer-group { display: table-footer-group !important; }
    
    thead tr { 
        background-color: transparent !important; 
        color: #000 !important; 
        border-bottom: 2px solid #000 !important;
    }
    
    .text-red-600 { color: #000 !important; }
    .font-black { font-weight: 900 !important; }
    .text-slate-900, .text-slate-700, .text-slate-600, .text-slate-500 { color: #000 !important; }
    
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .print\:break-inside-avoid { break-inside: avoid !important; }
}
</style>
