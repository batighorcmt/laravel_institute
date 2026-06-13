<template>
    <div class="p-4 md:p-8 bg-slate-50 min-h-screen font-inter">
        <div class="max-w-7xl mx-auto space-y-10">
            <!-- Premium Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                        <span class="p-4 bg-gradient-to-br from-slate-800 to-black rounded-[2rem] shadow-2xl shadow-indigo-100">
                             <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        ডিপোজিট হিস্ট্রি
                    </h1>
                    <p class="text-slate-500 mt-4 font-semibold text-lg">ক্যাশিয়ারের নিকট জমা দেওয়া অর্থের হিসাব ও ট্র্যাক</p>
                </div>
                <div class="flex gap-4 no-print">
                    <button @click="printReport" class="bg-white text-slate-700 px-8 py-4 rounded-2xl font-black shadow-sm border border-slate-200 hover:bg-slate-50 transition-all flex items-center gap-3 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        প্রিন্ট রিপোর্ট
                    </button>
                    <a href="/teacher/institute/1/billing/cash-transfer" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all flex items-center gap-3 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        নতুন জমা
                    </a>
                </div>
            </div>

            <!-- Enhanced Filters -->
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200/60 no-print backdrop-blur-xl">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
                    <div class="space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">শুরুর তারিখ</label>
                        <input type="date" v-model="filters.from_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border transition-all">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">শেষ তারিখ</label>
                        <input type="date" v-model="filters.to_date" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border transition-all">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">ফি ক্যাটাগরি</label>
                        <select v-model="filters.fee_category_id" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border appearance-none transition-all">
                            <option value="">সকল ক্যাটাগরি</option>
                            <option v-for="cat in meta.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">মাস</label>
                        <input type="month" v-model="filters.month" class="w-full bg-slate-50 border-slate-100 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:bg-white outline-none border transition-all">
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-4 border-t border-slate-50 pt-8 mt-8">
                    <button @click="resetFilters" class="bg-slate-100 text-slate-600 px-8 py-4 rounded-2xl font-black hover:bg-slate-200 transition-all text-sm uppercase tracking-widest">রিসেট</button>
                    <button @click="fetchData" :disabled="loading" class="bg-indigo-600 text-white px-12 py-4 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all flex items-center gap-3 text-sm min-w-[200px] justify-center">
                        <svg v-if="loading" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span v-else>ডাটা ফিল্টার করুন</span>
                    </button>
                </div>
            </div>

            <!-- List Section -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 overflow-hidden min-h-[400px]">
                <div class="px-10 py-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-8 bg-indigo-600 rounded-full"></div>
                        <h3 class="text-xl font-black text-slate-900">ডিপোজিট তালিকা</h3>
                    </div>
                    <div class="text-indigo-600 bg-indigo-50 px-5 py-2 rounded-full text-xs font-black uppercase tracking-[0.1em]">মোট {{ pagination.total }} টি এনট্রি </div>
                </div>

                <div v-if="loading && collections.length === 0" class="flex flex-col items-center justify-center py-20 animate-pulse">
                    <div class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-400 font-bold">ডাটা লোড হচ্ছে...</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/20 text-slate-400 text-[11px] uppercase font-black tracking-widest border-b border-slate-50">
                                <th class="px-10 py-6">তারিখ</th>
                                <th class="px-10 py-6">ফি ক্যাটাগরি</th>
                                <th class="px-10 py-6">মাস</th>
                                <th class="px-10 py-6">ক্যাশিয়ার</th>
                                <th class="px-10 py-6">স্ট্যাটাস</th>
                                <th class="px-10 py-6 text-right">পরিমাণ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in collections" :key="item.id" class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-10 py-7">
                                    <div class="text-slate-900 font-black text-sm group-hover:text-indigo-600 transition-colors">{{ formatDate(item.deposit_date) }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-tighter">{{ formatTime(item.created_at) }}</div>
                                </td>
                                <td class="px-10 py-7">
                                    <div class="text-slate-900 font-black">{{ item.category_name || 'সাধারণ' }}</div>
                                </td>
                                <td class="px-10 py-7">
                                    <span class="px-4 py-1.5 bg-slate-100 rounded-full text-slate-600 text-xs font-black uppercase tracking-tight">{{ formatMonth(item.month) }}</span>
                                </td>
                                <td class="px-10 py-7">
                                    <div v-if="item.cashier_name" class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-black text-slate-500 uppercase">{{ item.cashier_name.charAt(0) }}</div>
                                        <span class="text-slate-900 font-bold text-sm">{{ item.cashier_name }}</span>
                                    </div>
                                    <span v-else class="text-slate-300 font-black tracking-widest text-[10px] uppercase">প্রসেসিং...</span>
                                </td>
                                <td class="px-10 py-7">
                                    <div class="flex">
                                        <span v-if="item.status === 'received'" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></div>
                                            গৃহীত
                                        </span>
                                        <span v-else-if="item.status === 'pending'" class="px-4 py-2 bg-amber-50 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></div>
                                            অপেক্ষমান
                                        </span>
                                        <span v-else class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                            বাতিল
                                        </span>
                                    </div>
                                </td>
                                <td class="px-10 py-7 text-right">
                                    <div class="text-slate-900 font-black text-2xl tracking-tight">৳{{ formatNumber(item.amount) }}</div>
                                </td>
                            </tr>
                            <tr v-if="!collections.length && !loading">
                                <td colspan="6" class="px-10 py-32 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-300">
                                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="font-black text-xl text-slate-400">কোন ডাটা পাওয়া যায়নি</p>
                                        <p class="text-slate-300 font-bold mt-2">আপনার সার্চ ফিল্টার পরিবর্তন করে চেষ্টা করুন</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="px-10 py-8 border-t border-slate-50 flex justify-center">
                    <div class="flex gap-2">
                        <button v-for="p in pagination.last_page" :key="p" 
                                @click="goToPage(p)"
                                :class="pagination.current_page == p ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-100' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50'"
                                class="w-12 h-12 rounded-xl font-black text-sm transition-all">
                            {{ p }}
                        </button>
                    </div>
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
                month: '',
                page: 1
            },
            pagination: {
                current_page: 1,
                last_page: 1,
                total: 0
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
                    // Laravel paginator returns data in res.data.data
                    this.collections = res.data.data || [];
                    this.pagination = {
                        current_page: res.data.current_page,
                        last_page: res.data.last_page,
                        total: res.data.total
                    };
                })
                .catch(err => {
                    toastr.error('ডাটা লোড করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        goToPage(p) {
            this.filters.page = p;
            this.fetchData();
        },
        resetFilters() {
            this.filters = {
                from_date: '',
                to_date: '',
                fee_category_id: '',
                month: '',
                page: 1
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
        formatTime(dateTime) {
            if (!dateTime) return '';
            const d = new Date(dateTime);
            return d.toLocaleTimeString('bn-BD', { hour: '2-digit', minute: '2-digit' });
        },
        formatMonth(monthStr) {
            if (!monthStr) return 'GENERAL';
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
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');
.font-inter { font-family: 'Inter', 'SolaimanLipi', sans-serif; }

@media print {
    .no-print {
        display: none !important;
    }
    .p-4, .p-8 {
        padding: 0 !important;
    }
    .bg-slate-50 {
        background-color: white !important;
    }
    .shadow-sm, .shadow-2xl, .shadow-xl {
        box-shadow: none !important;
    }
    \.rounded-\[2\.5rem\], \.rounded-\[2rem\] {
        border-radius: 0 !important;
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
