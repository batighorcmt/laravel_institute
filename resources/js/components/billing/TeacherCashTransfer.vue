<template>
    <div class="p-4 md:p-8 bg-slate-50 min-h-screen font-inter">
        <div class="max-w-7xl mx-auto space-y-10">
            <!-- Premium Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                        <span class="p-4 bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-[2rem] shadow-2xl shadow-indigo-200">
                             <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                        ক্যাশ ট্রান্সফার
                    </h1>
                    <p class="text-slate-500 mt-4 font-semibold text-lg">সংগৃহীত অর্থ ক্যাশিয়ারের নিকট জমা দেওয়ার পোর্টাল</p>
                </div>
                
                <!-- Quick Summary for Mobile/Top -->
                <div class="bg-indigo-600 p-6 rounded-[2.5rem] shadow-2xl shadow-indigo-100 flex items-center gap-6 text-white min-w-[300px]">
                    <div class="p-4 bg-white/20 rounded-2xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <div>
                        <div class="text-indigo-100 text-xs font-black uppercase tracking-widest mb-1">মোট হাতে নগদ</div>
                        <div class="text-3xl font-black">৳{{ formatNumber(summary.total_remaining) }}</div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Filters -->
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200/60 no-print backdrop-blur-xl">
                <div class="flex flex-col md:flex-row gap-6 items-end">
                    <div class="flex-1 space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">আদায়ের শুরুর তারিখ</label>
                        <div class="relative group">
                            <input type="date" v-model="filters.from_date" class="w-full bg-slate-50 border-slate-100 group-hover:border-indigo-200 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-600 outline-none border transition-all">
                            <svg class="w-5 h-5 absolute right-5 top-4 text-slate-300 pointer-events-none group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">আদায়ের শেষ তারিখ</label>
                        <div class="relative group">
                            <input type="date" v-model="filters.to_date" class="w-full bg-slate-50 border-slate-100 group-hover:border-indigo-200 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-600 outline-none border transition-all">
                            <svg class="w-5 h-5 absolute right-5 top-4 text-slate-300 pointer-events-none group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                    <button @click="fetchData" :disabled="loading" class="bg-slate-900 text-white px-12 py-4 rounded-2xl font-black hover:bg-black shadow-xl shadow-slate-200 transition-all flex items-center justify-center gap-3 text-sm min-w-[200px]">
                        <svg v-if="loading" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span v-else>রিপোর্ট দেখুন</span>
                    </button>
                </div>
            </div>

            <!-- Main Dashboard State -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-20 animate-pulse">
                <div class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mb-4"></div>
                <p class="text-slate-400 font-bold">ডাটা লোড হচ্ছে...</p>
            </div>

            <div v-else-if="hasData" class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                
                <!-- Breakdown Table (Left Side) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <h3 class="text-xl font-black text-slate-900 flex items-center gap-3">
                                <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                                অবিক্রীত হ্যান্ড ক্যাশ (বিস্তারিত)
                            </h3>
                            <div class="flex items-center gap-3">
                                <button @click="selectAll" class="text-xs font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors">সব সিলেক্ট করুন</button>
                                <span class="text-slate-300">|</span>
                                <button @click="selectNone" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">সব মুছুন</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th class="px-8 py-5 text-left"><input type="checkbox" @change="toggleAll" id="checkAll" class="rounded border-slate-200 text-indigo-600 focus:ring-indigo-600 w-5 h-5 cursor-pointer"></th>
                                        <th class="px-8 py-5 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">ফি ক্যাটাগরি</th>
                                        <th class="px-8 py-5 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">মাস</th>
                                        <th class="px-8 py-5 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest">মাট আদায়</th>
                                        <th class="px-8 py-5 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest">অবশিষ্ট</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, idx) in breakdown" :key="idx" 
                                        :class="{'bg-indigo-50/30': selectedItems.includes(idx), 'hover:bg-slate-50': !selectedItems.includes(idx)}" 
                                        @click="toggleItem(idx)"
                                        class="transition-colors cursor-pointer group">
                                        <td class="px-8 py-6">
                                            <input type="checkbox" v-model="selectedItems" :value="idx" @click.stop class="rounded border-slate-200 text-indigo-600 focus:ring-indigo-600 w-5 h-5 cursor-pointer">
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">{{ item.category_name }}</div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="px-4 py-1.5 bg-slate-100 rounded-full text-slate-600 text-xs font-black uppercase tracking-tight">{{ formatMonth(item.month) }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="font-bold text-slate-400">৳{{ formatNumber(item.collected_amount) }}</div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="font-black text-rose-500 text-lg">৳{{ formatNumber(item.remaining_to_request) }}</div>
                                        </td>
                                    </tr>
                                    <tr v-if="breakdown.length === 0">
                                        <td colspan="5" class="px-8 py-20 text-center">
                                            <div class="flex flex-col items-center gap-4">
                                                <div class="p-6 bg-slate-100 rounded-full text-slate-300">
                                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                                <p class="text-slate-400 font-bold">এই তারিখের মধ্যে কোন আদায় নেই</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Submission Card (Right Side) -->
                <div class="lg:col-span-4 sticky top-8">
                    <div class="bg-slate-900 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-indigo-100 relative overflow-hidden">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-600/10 rounded-full blur-3xl"></div>
                        
                        <div class="relative z-10 space-y-8">
                            <div>
                                <h3 class="text-2xl font-black mb-2">জমার সারসংক্ষেপ</h3>
                                <p class="text-slate-400 font-bold text-sm">সিলেক্টকৃত আইটেমগুলো ক্যাশিয়য়ারের নিকট জমা দিন</p>
                            </div>

                            <div class="space-y-6">
                                <div class="flex justify-between items-center group">
                                    <span class="text-slate-400 font-black text-xs uppercase tracking-widest group-hover:text-indigo-400 transition-colors">সিলেক্টকৃত আইটেম</span>
                                    <span class="text-2xl font-black">{{ selectedItems.length }} টি</span>
                                </div>
                                <div class="h-px bg-slate-800"></div>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <span class="text-indigo-400 font-black text-xs uppercase tracking-widest mb-2 block">মোট জমার পরিমাণ</span>
                                        <div class="text-5xl font-black text-white">৳{{ formatNumber(selectedTotal) }}</div>
                                    </div>
                                </div>
                            </div>

                            <button @click="submitDeposit" 
                                    :disabled="submitting || selectedItems.length === 0" 
                                    class="w-full bg-white text-slate-900 py-6 rounded-[1.8rem] font-black hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-xl shadow-black/20 flex items-center justify-center gap-4 text-lg">
                                <svg v-if="submitting" class="animate-spin h-6 w-6 text-slate-900" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span v-else>ক্যাশ জমা দিন</span>
                                <svg v-if="!submitting" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>

                            <!-- Mini History Link -->
                            <div class="text-center">
                                <a href="/teacher/institute/1/billing/deposit-history" class="text-slate-500 font-black text-xs uppercase tracking-widest hover:text-white transition-colors underline-offset-8 underline decoration-slate-800">পুরানো ইতিহাস দেখুন</a>
                            </div>
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
    name: 'TeacherCashTransfer',
    data() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), 0, 1).toLocaleDateString('en-CA');
        const lastDay = new Date(now.getFullYear(), 11, 31).toLocaleDateString('en-CA');

        return {
            loading: false,
            submitting: false,
            hasData: false,
            summary: {
                total_collected: 0,
                total_received: 0,
                total_pending: 0,
                total_remaining: 0
            },
            breakdown: [],
            selectedItems: [],
            filters: {
                from_date: firstDay,
                to_date: lastDay
            }
        };
    },
    computed: {
        selectedTotal() {
            return this.selectedItems.reduce((acc, idx) => {
                const item = this.breakdown[idx];
                return acc + (item ? (parseFloat(item.remaining_to_request) || 0) : 0);
            }, 0);
        }
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fetchData() {
            if (!this.filters.from_date || !this.filters.to_date) {
                toastr.warning('তারিখ সিলেক্ট করুন');
                return;
            }
            this.loading = true;
            this.selectedItems = [];
            
            axios.get('/api/v1/billing/reports/teacher-cash-transfer', { params: this.filters })
                .then(res => {
                    this.summary = {
                        total_collected: res.data.total_collected || 0,
                        total_received: res.data.total_received || 0,
                        total_pending: res.data.total_pending || 0,
                        total_remaining: res.data.total_remaining || 0
                    };
                    this.breakdown = res.data.breakdown || [];
                    this.hasData = true;
                })
                .catch(err => {
                    toastr.error('ডাটা লোড করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        toggleItem(idx) {
            const pos = this.selectedItems.indexOf(idx);
            if (pos === -1) {
                this.selectedItems.push(idx);
            } else {
                this.selectedItems.splice(pos, 1);
            }
        },
        toggleAll(e) {
            if (e.target.checked) {
                this.selectAll();
            } else {
                this.selectNone();
            }
        },
        selectAll() {
            this.selectedItems = this.breakdown.map((_, i) => i);
            const checkAll = document.getElementById('checkAll');
            if (checkAll) checkAll.checked = true;
        },
        selectNone() {
            this.selectedItems = [];
            const checkAll = document.getElementById('checkAll');
            if (checkAll) checkAll.checked = false;
        },
        submitDeposit() {
            if (this.selectedItems.length === 0) return;

            const itemsToSubmit = this.selectedItems.map(idx => {
                const item = this.breakdown[idx];
                return {
                    fee_category_id: item.fee_category_id,
                    month: item.month,
                    amount: item.remaining_to_request
                };
            }).filter(i => i.amount > 0);

            if (itemsToSubmit.length === 0) {
                toastr.info('কোন পজিটিভ অ্যামাউন্ট নেই');
                return;
            }

            this.submitting = true;
            axios.post('/api/v1/billing/reports/teacher-cash-transfer/deposit', {
                items: itemsToSubmit
            })
            .then(res => {
                toastr.success('সফলভাবে জমা দেওয়ার রিকোয়েস্ট পাঠানো হয়েছে!');
                this.fetchData(); // Refresh summary
            })
            .catch(err => {
                const msg = err.response?.data?.error || 'জমা দিতে সমস্যা হয়েছে';
                toastr.error(msg);
            })
            .finally(() => {
                this.submitting = false;
            });
        },
        formatNumber(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('bn-BD', { minimumFractionDigits: 0 });
        },
        formatMonth(m) {
            if (!m) return 'GENERAL';
            // m is YYYY-MM
            const months = ['জানুয়ারি', 'ফেব্রুযারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
            try {
                const parts = m.split('-');
                if (parts.length === 2) {
                    const monthIdx = parseInt(parts[1]) - 1;
                    return months[monthIdx] + ' ' + parts[0];
                }
            } catch (e) {}
            return m;
        }
    }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');
.font-inter { font-family: 'Inter', 'SolaimanLipi', sans-serif; }
</style>
