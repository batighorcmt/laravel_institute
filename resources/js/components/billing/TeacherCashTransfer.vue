<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-3 bg-emerald-600 rounded-2xl shadow-lg shadow-emerald-200">
                             <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                        ক্যাশ ট্রান্সফার
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium">ক্যাশিয়ারের নিকট ক্যাশ জমা দেওয়ার পোর্টাল</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-200 no-print">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শুরুর তারিখ</label>
                        <input type="date" v-model="filters.from_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10 focus:bg-white outline-none border">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শেষ তারিখ</label>
                        <input type="date" v-model="filters.to_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10 focus:bg-white outline-none border">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="fetchData" :disabled="loading" class="bg-emerald-600 text-white px-10 py-3 rounded-2xl font-black hover:bg-emerald-700 shadow-xl shadow-emerald-100 transition-all flex items-center gap-2 text-sm">
                        <svg v-if="loading" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        রিপোর্ট দেখুন
                    </button>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" v-if="hasData">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all duration-300">
                    <h3 class="text-slate-400 font-black text-xs uppercase tracking-widest mb-1">আদায়কৃত মোট ফিস</h3>
                    <div class="text-3xl font-black text-slate-900">৳{{ formatNumber(summary.total_collected) }}</div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all duration-300">
                    <h3 class="text-slate-400 font-black text-xs uppercase tracking-widest mb-1">ইতোমধ্যে জমা দেওয়া হয়েছে</h3>
                    <div class="text-3xl font-black text-emerald-600">৳{{ formatNumber(summary.total_deposited) }}</div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all duration-300 ring-2 ring-rose-500 ring-offset-4">
                    <h3 class="text-slate-400 font-black text-xs uppercase tracking-widest mb-1">হাতে অবশিষ্ট অর্থ</h3>
                    <div class="text-3xl font-black text-rose-500">৳{{ formatNumber(summary.total_remaining) }}</div>
                </div>
            </div>

            <!-- Deposit Form Section -->
            <div v-if="hasData && summary.total_remaining > 0" class="bg-white p-8 rounded-[2rem] shadow-sm border border-rose-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-2 h-full bg-rose-500"></div>
                
                <h3 class="text-xl font-black text-slate-900 mb-6">ক্যাশিয়ারের নিকট জমা দিন</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">জমার পরিমাণ</label>
                            <input type="number" v-model="depositAmount" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-xl font-black focus:ring-4 focus:ring-rose-500/10 focus:bg-white outline-none border" placeholder="0.00">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ফি ক্যাটাগরি (ঐচ্ছিক)</label>
                                <select v-model="depositCategory" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-rose-500/10 focus:bg-white outline-none border appearance-none">
                                    <option value="">সকল ক্যাটাগরি</option>
                                    <option v-for="cat in meta.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">মাস (ঐচ্ছিক)</label>
                                <input type="month" v-model="depositMonth" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-rose-500/10 focus:bg-white outline-none border">
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-end">
                        <button @click="submitDeposit" :disabled="submitting || depositAmount <= 0 || depositAmount > summary.total_remaining" class="bg-rose-600 text-white px-8 py-4 rounded-2xl font-black hover:bg-rose-700 shadow-xl shadow-rose-200 transition-all flex items-center justify-center gap-2 w-full disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="submitting" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>জমা দিন</span>
                        </button>
                    </div>
                </div>
            </div>
            <div v-else-if="hasData && summary.total_remaining <= 0" class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100 flex items-center gap-4">
                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h4 class="text-emerald-800 font-bold text-lg">কোন অবশিষ্ট অর্থ নেই</h4>
                    <p class="text-emerald-600/80 text-sm font-medium">আপনার সম্পূর্ণ কালেকশন ক্যাশিয়ারের নিকট জমা দেওয়া হয়েছে</p>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TeacherCashTransfer',
    data() {
        // Default to current month start and end dates
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toLocaleDateString('en-CA');
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toLocaleDateString('en-CA');

        return {
            loading: false,
            submitting: false,
            hasData: false,
            summary: {
                total_collected: 0,
                total_deposited: 0,
                total_remaining: 0
            },
            filters: {
                from_date: firstDay,
                to_date: lastDay
            },
            meta: {
                categories: []
            },
            depositAmount: 0,
            depositCategory: '',
            depositMonth: ''
        };
    },
    mounted() {
        this.fetchMeta();
        this.fetchData();
    },
    methods: {
        fetchMeta() {
            axios.get('/api/v1/billing/config').then(res => {
                this.meta.categories = res.data.categories || [];
            });
        },
        fetchData() {
            if (!this.filters.from_date || !this.filters.to_date) {
                toastr.warning('Please select dates');
                return;
            }
            this.loading = true;
            axios.get('/api/v1/billing/reports/teacher-cash-transfer', { params: this.filters })
                .then(res => {
                    this.summary = {
                        total_collected: res.data.total_collected || 0,
                        total_deposited: res.data.total_deposited || 0,
                        total_remaining: res.data.total_remaining || 0
                    };
                    this.depositAmount = this.summary.total_remaining;
                    this.hasData = true;
                })
                .catch(err => {
                    toastr.error('ডাটা লোড করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        submitDeposit() {
            if (this.depositAmount <= 0) return;
            if (this.depositAmount > this.summary.total_remaining) {
                toastr.error('অবশিষ্ট অর্থের চেয়ে বেশি জমা দেওয়া যাবে না');
                return;
            }

            this.submitting = true;
            axios.post('/api/v1/billing/reports/teacher-cash-transfer/deposit', {
                amount: this.depositAmount,
                fee_category_id: this.depositCategory,
                month: this.depositMonth
            })
            .then(res => {
                toastr.success('সফলভাবে জমা দেওয়া হয়েছে!');
                this.depositAmount = 0;
                this.depositCategory = '';
                this.depositMonth = '';
                this.fetchData(); // Refresh summary
            })
            .catch(err => {
                toastr.error('জমা দিতে সমস্যা হয়েছে');
            })
            .finally(() => {
                this.submitting = false;
            });
        },
        formatNumber(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('bn-BD');
        }
    }
};
</script>
