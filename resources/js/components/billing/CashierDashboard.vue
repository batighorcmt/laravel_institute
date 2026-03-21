<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-3 bg-emerald-600 rounded-2xl shadow-lg shadow-emerald-200">
                             <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </span>
                        ক্যাশিয়ার ড্যাশবোর্ড
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium">আপনার বর্তমান দায়িত্বের সম্পূর্ণ হিসাব এবং পেন্ডিং জমা</p>
                </div>
                <div>
                    <button @click="showExpenseModal = true" class="bg-rose-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-rose-700 shadow-lg shadow-rose-200 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        নতুন ব্যয় যুক্ত করুন
                    </button>
                </div>
            </div>

            <!-- Loader -->
            <div v-if="loading" class="text-center py-20 text-slate-400 font-bold text-lg">
                <svg class="animate-spin h-8 w-8 text-emerald-500 mx-auto mb-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                ডেটা লোড হচ্ছে...
            </div>

            <div v-else>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 relative overflow-hidden group hover:shadow-xl hover:shadow-emerald-100 transition-all">
                        <h4 class="text-slate-500 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">মোট গ্রহণকৃত (ডিপোজিট ও সরাসরি সংগ্রহ)</h4>
                        <div class="text-3xl font-black text-emerald-600 z-10 relative">৳{{ formatNumber(summary.total_received) }}</div>
                        <div class="absolute -bottom-4 -right-4 opacity-[0.03] text-emerald-600 group-hover:scale-110 group-hover:opacity-10 transition-all duration-300">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 relative overflow-hidden group hover:shadow-xl hover:shadow-rose-100 transition-all">
                        <h4 class="text-slate-500 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">মোট ব্যয়/খরচ</h4>
                        <div class="text-3xl font-black text-rose-600 z-10 relative">৳{{ formatNumber(summary.total_spent) }}</div>
                        <div class="absolute -bottom-4 -right-4 opacity-[0.03] text-rose-600 group-hover:scale-110 group-hover:opacity-10 transition-all duration-300">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-600 to-teal-600 p-6 rounded-3xl relative overflow-hidden group hover:shadow-xl hover:shadow-emerald-200 transition-all text-white">
                        <h4 class="text-emerald-100 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">অবশিষ্ট ক্যাশ ব্যালেন্স</h4>
                        <div class="text-4xl font-black z-10 relative">৳{{ formatNumber(summary.balance) }}</div>
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                        <div class="absolute -bottom-4 -right-4 opacity-10 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Main Content: Filters & Table -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200">
                    <!-- Filers -->
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 rounded-t-[2rem]">
                        <h2 class="text-lg font-black text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            তথ্য ফিল্টার করুন
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">শুরুর তারিখ</label>
                                <input type="date" v-model="filters.from_date" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">শেষ তারিখ</label>
                                <input type="date" v-model="filters.to_date" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">জমাকারী শিক্ষক</label>
                                <select v-model="filters.teacher_id" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                                    <option value="">সকল শিক্ষক</option>
                                    <option v-for="t in filterOptions.teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">ক্যাটাগরি</label>
                                <select v-model="filters.fee_category_id" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                                    <option value="">সকল ক্যাটাগরি</option>
                                    <option v-for="cat in filterOptions.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">মাস</label>
                                <select v-model="filters.month" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                                    <option value="">সকল মাস</option>
                                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">অবস্থা</label>
                                <select v-model="filters.status" class="w-full bg-white border border-slate-200 outline-none rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                                    <option value="">সকল অবস্থা</option>
                                    <option value="pending">অপেক্ষমাণ (Pending)</option>
                                    <option value="received">গৃহীত (Received)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button @click="fetchData" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-bold text-sm hover:bg-slate-700 transition-all shadow shadow-slate-200">
                                ফিল্টার প্রয়োগ
                            </button>
                            <button @click="resetFilters" class="bg-white text-slate-600 border border-slate-200 px-6 py-2 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all">
                                রিসেট
                            </button>
                        </div>
                    </div>

                    <!-- Deposit Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                                    <th class="px-6 py-4">জমাদানের তারিখ</th>
                                    <th class="px-6 py-4">জমাকারী (শ্রেণি শিক্ষক)</th>
                                    <th class="px-6 py-4">খাত / মাস</th>
                                    <th class="px-6 py-4 text-right">পরিমাণ (৳)</th>
                                    <th class="px-6 py-4 text-center">স্ট্যাটাস</th>
                                    <th class="px-6 py-4 text-center">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="deposit in deposits" :key="deposit.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-700">{{ formatDate(deposit.deposit_date) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-black text-slate-900">{{ deposit.teacher_name }}</div>
                                        <div class="text-xs font-bold text-emerald-600 mt-0.5" v-if="deposit.class_teacher_class">
                                            {{ deposit.class_teacher_class }} {{ deposit.class_teacher_section ? '- '+deposit.class_teacher_section : '' }} এর শ্রেণি শিক্ষক
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-slate-800">{{ deposit.fee_category_name || '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ deposit.month ? formatMonth(deposit.month) : '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="font-black text-slate-800 text-base">৳{{ formatNumber(deposit.amount) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="deposit.status === 'pending'" class="inline-block px-3 py-1 bg-amber-50 text-amber-600 text-xs font-black rounded-lg uppercase tracking-wider border border-amber-100/50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block mr-1"></span> অপেক্ষমাণ
                                        </span>
                                        <span v-else-if="deposit.status === 'received'" class="inline-block px-3 py-1 bg-emerald-50 text-emerald-600 text-xs font-black rounded-lg uppercase tracking-wider border border-emerald-100/50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block mr-1"></span> গৃহীত
                                        </span>
                                        <span v-else class="inline-block px-3 py-1 bg-slate-50 text-slate-500 text-xs font-black rounded-lg uppercase tracking-wider">
                                            {{ deposit.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button v-if="deposit.status === 'pending'" @click="acceptDeposit(deposit.id)" :disabled="processingAction === deposit.id" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-xs font-black shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 hover:scale-105 active:scale-95 transition-all disabled:opacity-50 inline-flex items-center gap-2">
                                            <svg v-if="processingAction === deposit.id" class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span v-else>রিসিভ করুন</span>
                                        </button>
                                        <span v-else class="text-slate-300 text-xs font-black flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            সম্পন্ন
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!deposits.length">
                                    <td colspan="6" class="px-6 py-16 text-center text-slate-400 font-bold">
                                        <div class="mb-2 opacity-50"><svg class="w-12 h-12 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
                                        কোনো রেকর্ড পাওয়া যায়নি
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Expense Modal -->
        <div v-if="showExpenseModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-3">
                        <div class="bg-rose-100 p-2 rounded-xl text-rose-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        নতুন ব্যয় যুক্ত করুন
                    </h2>
                    <button @click="closeExpenseModal" class="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitExpense" class="p-8 space-y-5">
                    <div>
                        <label class="block text-sm font-black text-slate-700 mb-1.5">ব্যয়ের তারিখ *</label>
                        <input type="date" required v-model="expenseForm.expense_date" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-black text-slate-700 mb-1.5">ব্যয়ের খাত/ক্যাটাগরি *</label>
                        <input type="text" required v-model="expenseForm.category" placeholder="যেমন: আপ্যায়ন কিংবা বিদ্যুৎ বিল" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-black text-slate-700 mb-1.5">পরিমাণ (৳) *</label>
                        <input type="number" step="0.01" min="1" required v-model="expenseForm.amount" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 text-sm font-black focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-black text-slate-700 mb-1.5">বিস্তারিত বিবরণ (ঐচ্ছিক)</label>
                        <textarea v-model="expenseForm.description" rows="3" placeholder="অতিরিক্ত কোনো তথ্য থাকলে লিখুন..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all resize-none"></textarea>
                    </div>
                    
                    <div class="pt-4 flex gap-3">
                        <button type="submit" :disabled="processingExpense" class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-black text-sm hover:bg-rose-500 shadow-lg shadow-rose-500/30 transition-all flex justify-center items-center gap-2 disabled:opacity-50">
                            <svg v-if="processingExpense" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>যুক্ত করুন</span>
                        </button>
                        <button type="button" @click="closeExpenseModal" class="px-6 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all">বাতিল</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CashierDashboard',
    data() {
        return {
            loading: true,
            processingAction: null,
            deposits: [],
            summary: {
                total_received: 0,
                total_spent: 0,
                balance: 0
            },
            filterOptions: {
                categories: [],
                teachers: []
            },
            filters: {
                from_date: '',
                to_date: '',
                teacher_id: '',
                fee_category_id: '',
                month: '',
                status: ''
            },
            months: [
                { value: 1, label: 'January' }, { value: 2, label: 'February' },
                { value: 3, label: 'March' }, { value: 4, label: 'April' },
                { value: 5, label: 'May' }, { value: 6, label: 'June' },
                { value: 7, label: 'July' }, { value: 8, label: 'August' },
                { value: 9, label: 'September' }, { value: 10, label: 'October' },
                { value: 11, label: 'November' }, { value: 12, label: 'December' }
            ],
            
            showExpenseModal: false,
            processingExpense: false,
            expenseForm: {
                expense_date: new Date().toISOString().split('T')[0],
                category: '',
                amount: '',
                description: ''
            }
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fetchData() {
            this.loading = true;
            axios.get('/api/v1/billing/cashier-dashboard/data', { params: this.filters })
                .then(res => {
                    this.deposits = res.data.deposits || [];
                    this.summary = res.data.summary;
                    this.filterOptions.categories = res.data.categories || [];
                    this.filterOptions.teachers = res.data.teachers || [];
                })
                .catch(err => {
                    toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        resetFilters() {
            this.filters = {
                from_date: '',
                to_date: '',
                teacher_id: '',
                fee_category_id: '',
                month: '',
                status: ''
            };
            this.fetchData();
        },
        acceptDeposit(id) {
            if (!confirm('আপনি কি এই জমাদানটি গ্রহণ করতে চান?')) return;
            
            this.processingAction = id;
            axios.post(`/api/v1/billing/cashier-accept-deposit/${id}`)
                .then(res => {
                    toastr.success('জমাদান সফলভাবে গ্রহণ করা হয়েছে');
                    this.fetchData(); // refresh the list to update summary & statuses
                })
                .catch(err => {
                    toastr.error(err.response?.data?.error || 'জমাদান গ্রহণে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.processingAction = null;
                });
        },
        submitExpense() {
            this.processingExpense = true;
            axios.post('/api/v1/billing/cashier-dashboard/add-expense', this.expenseForm)
                .then(res => {
                    toastr.success('ব্যয় সফলভাবে যুক্ত করা হয়েছে');
                    this.closeExpenseModal();
                    this.fetchData(); // refresh summary
                })
                .catch(err => {
                    toastr.error(err.response?.data?.error || 'ব্যয় যুক্ত করতে সমস্যা হয়েছে');
                })
                .finally(() => {
                    this.processingExpense = false;
                });
        },
        closeExpenseModal() {
            this.showExpenseModal = false;
            this.expenseForm = {
                expense_date: new Date().toISOString().split('T')[0],
                category: '',
                amount: '',
                description: ''
            };
        },
        formatNumber(num) {
            return parseFloat(num || 0).toLocaleString('bn-BD');
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        formatMonth(num) {
            const row = this.months.find(m => m.value == num);
            return row ? row.label : num;
        }
    }
};
</script>
