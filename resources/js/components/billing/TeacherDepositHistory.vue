<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200">
                             <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        ডিপোজিট হিস্ট্রি
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium">ক্যাশিয়ারের নিকট জমা দেওয়া অর্থের হিসাব</p>
                </div>
                <div class="flex gap-3 no-print">
                    <button @click="printReport" class="bg-white text-slate-700 px-6 py-3 rounded-2xl font-black shadow-sm border border-slate-200 hover:bg-slate-50 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        প্রিন্ট করুন
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-200 no-print">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শুরুর তারিখ</label>
                        <input type="date" v-model="filters.from_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">শেষ তারিখ</label>
                        <input type="date" v-model="filters.to_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ফি ক্যাটাগরি</label>
                        <select v-model="filters.fee_category_id" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border appearance-none">
                            <option value="">সকল ক্যাটাগরি</option>
                            <option v-for="cat in meta.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">মাস</label>
                        <input type="month" v-model="filters.month" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="resetFilters" class="bg-slate-200 text-slate-700 px-8 py-3 rounded-2xl font-black hover:bg-slate-300 shadow-sm transition-all text-sm">
                        রিসেট
                    </button>
                    <button @click="fetchData" :disabled="loading" class="bg-indigo-600 text-white px-10 py-3 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all flex items-center gap-2 text-sm">
                        <svg v-if="loading" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        ডাটা ফিল্টার করুন
                    </button>
                </div>
            </div>

            <!-- List Section -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-slate-800 font-black">ডিপোজিট তালিকা</h3>
                    <div class="text-slate-400 text-xs font-bold uppercase tracking-widest">মোট {{ collections.length }} টি ডিপোজিট </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                <th class="px-8 py-5">তারিখ</th>
                                <th class="px-8 py-5">ফি ক্যাটাগরি</th>
                                <th class="px-8 py-5">মাস</th>
                                <th class="px-8 py-5">মাধ্যম/ক্যাশিয়ার</th>
                                <th class="px-8 py-5">স্ট্যাটাস</th>
                                <th class="px-8 py-5 text-right">পরিমাণ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in collections" :key="item.id" class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="text-slate-900 font-bold text-sm">{{ formatDate(item.deposit_date) }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-slate-900 font-black">{{ item.fee_category_name || 'সাধারণ' }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 bg-slate-50 text-slate-600 border border-slate-100 rounded-lg text-xs font-bold">{{ formatMonth(item.month) }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="capitalize text-slate-600 font-bold text-sm">{{ item.cashier_name || 'Pending' }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span v-if="item.status === 'received'" class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black">গৃহীত</span>
                                    <span v-else-if="item.status === 'pending'" class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-black">অপেক্ষমান</span>
                                    <span v-else class="px-3 py-1 bg-rose-50 text-rose-600 rounded-lg text-xs font-black">বাতিল</span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="text-slate-950 font-black text-lg">৳{{ formatNumber(item.amount) }}</div>
                                </td>
                            </tr>
                            <tr v-if="!collections.length && !loading">
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-300">
                                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="font-black text-lg">কোন ডাটা পাওয়া যায়নি</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TeacherDepositHistory',
    data() {
        return {
            loading: false,
            collections: [],
            meta: {
                categories: []
            },
            filters: {
                from_date: '',
                to_date: '',
                fee_category_id: '',
                month: ''
            }
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
            this.loading = true;
            axios.get('/api/v1/billing/reports/teacher-deposit-history', { params: this.filters })
                .then(res => {
                    this.collections = res.data.deposits || [];
                })
                .catch(err => {
                    toastr.error('ডাটা লোড করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        resetFilters() {
            this.filters = {
                from_date: '',
                to_date: '',
                fee_category_id: '',
                month: ''
            };
            this.fetchData();
        },
        printReport() {
            window.print();
        },
        formatNumber(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('bn-BD');
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        formatMonth(monthStr) {
            if (!monthStr) return 'সকল মাস';
            const parts = monthStr.split('-');
            if (parts.length === 2) {
                const date = new Date(parts[0], parseInt(parts[1]) - 1, 1);
                return date.toLocaleDateString('bn-BD', { month: 'long', year: 'numeric' });
            }
            return monthStr;
        }
    }
};
</script>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .p-6 {
        padding: 0 !important;
    }
    .bg-slate-50 {
        background-color: white !important;
    }
    .shadow-sm, .shadow-lg, .shadow-xl {
        box-shadow: none !important;
    }
    table {
        border: 1px solid #e2e8f0 !important;
    }
    th {
        background-color: #f8fafc !important;
        color: black !important;
    }
}
</style>
