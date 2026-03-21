<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span class="p-3 bg-purple-600 rounded-2xl shadow-lg shadow-purple-200">
                             <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                        ক্যাশিয়ার ম্যানেজমেন্ট
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium">প্রতিষ্ঠানের ক্যাশিয়ার নির্ধারণ এবং তাদের হিসাব-নিকাশ</p>
                </div>
            </div>

            <!-- Dashboard Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Current Cashier Info -->
                <div class="lg:col-span-2 bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                    <h3 class="text-slate-400 font-black text-xs uppercase tracking-widest mb-4">বর্তমান ক্যাশিয়ার</h3>
                    <div v-if="activeAssignment" class="flex items-center gap-6">
                        <div class="h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-2xl font-black shrink-0">
                            {{ getInitials(activeAssignment.cashier_name) }}
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 leading-none">{{ activeAssignment.cashier_name }}</h2>
                            <p class="text-slate-500 font-medium mt-1">দায়িত্ব শুরু: {{ formatDate(activeAssignment.started_at) }}</p>
                        </div>
                        <div class="ml-auto">
                            <button @click="viewStatement(activeAssignment.id)" class="bg-purple-50 text-purple-700 px-6 py-2 rounded-xl font-bold hover:bg-purple-100 transition-all text-sm">
                                হিসাব দেখুন
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-slate-400 font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        বর্তমানে কোনো ক্যাশিয়ার নির্ধারিত নেই
                    </div>
                </div>

                <!-- Assign New Cashier -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] shadow-lg p-8 relative overflow-hidden text-white">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                    <h3 class="text-slate-300 font-black text-xs uppercase tracking-widest mb-6">নতুন ক্যাশিয়ার নির্ধারণ করুন</h3>
                    
                    <div class="space-y-4 relative z-10">
                        <select v-model="selectedTeacher" class="w-full bg-slate-800 border-slate-700 text-white rounded-2xl px-5 py-3 text-sm font-bold focus:ring-4 focus:ring-purple-500/30 outline-none border appearance-none">
                            <option value="">শিক্ষক নির্বাচন করুন</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} ({{ teacher.username }})</option>
                        </select>
                        <button @click="assignCashier" :disabled="assigning || !selectedTeacher" class="w-full bg-purple-600 text-white px-6 py-3 rounded-2xl font-black hover:bg-purple-500 shadow-lg shadow-purple-500/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="assigning" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>নির্ধারণ করুন</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-slate-800 font-black">ক্যাশিয়ার দায়িত্বের ইতিহাস</h3>
                    <div class="text-slate-400 text-xs font-bold uppercase tracking-widest">মোট {{ history.length }} টি রেকর্ড</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                <th class="px-8 py-5">ক্যাশিয়ার</th>
                                <th class="px-8 py-5">শুরুর তারিখ</th>
                                <th class="px-8 py-5">শেষের তারিখ</th>
                                <th class="px-8 py-5">নির্ধারণকারী</th>
                                <th class="px-8 py-5">স্ট্যাটাস</th>
                                <th class="px-8 py-5 text-right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in history" :key="item.id" class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="text-slate-900 font-black">{{ item.cashier_name }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-slate-600 font-bold text-sm">{{ formatDate(item.started_at) }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-slate-600 font-bold text-sm">{{ item.ended_at ? formatDate(item.ended_at) : '-' }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-slate-500 font-bold text-sm">{{ item.assigned_by_name || 'System' }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span v-if="item.is_active" class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black">বর্তমান</span>
                                    <span v-else class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-xs font-black">সাবেক</span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <button @click="viewStatement(item.id)" class="text-purple-600 hover:text-purple-800 font-bold text-sm underline decoration-purple-200 underline-offset-4 hover:decoration-purple-400 transition-all">
                                        হিসাব দেখুন
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!history.length && !loading">
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <div class="text-slate-400 font-bold">কোন রেকর্ড পাওয়া যায়নি</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cashier Statement Modal -->
        <div v-if="showStatementModal && statementData" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                    <h2 class="text-2xl font-black text-slate-900">ক্যাশিয়ার স্টেটমেন্ট</h2>
                    <button @click="closeStatementModal" class="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-8 overflow-y-auto flex-1 space-y-8">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-emerald-50 p-6 rounded-3xl border border-emerald-100 relative overflow-hidden group">
                            <h4 class="text-emerald-800 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">মোট গ্রহণকৃত (ডিপোজিট + সরাসরি)</h4>
                            <div class="text-3xl font-black text-emerald-600 z-10 relative">৳{{ formatNumber(statementData.summary.total_received) }}</div>
                            <div class="absolute -bottom-4 -right-4 opacity-10 text-emerald-600 group-hover:scale-110 transition-transform">
                                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="bg-rose-50 p-6 rounded-3xl border border-rose-100 relative overflow-hidden group">
                            <h4 class="text-rose-800 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">মোট খরচ/ব্যাংকে জমা</h4>
                            <div class="text-3xl font-black text-rose-600 z-10 relative">৳{{ formatNumber(statementData.summary.total_spent) }}</div>
                            <div class="absolute -bottom-4 -right-4 opacity-10 text-rose-600 group-hover:scale-110 transition-transform">
                                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            </div>
                        </div>
                        <div class="bg-purple-50 p-6 rounded-3xl border border-purple-100 relative overflow-hidden group">
                            <h4 class="text-purple-800 font-black text-xs uppercase tracking-widest mb-2 z-10 relative">বর্তমান ক্যাশ ব্যালেন্স</h4>
                            <div class="text-3xl font-black text-purple-600 z-10 relative">৳{{ formatNumber(statementData.summary.balance) }}</div>
                            <div class="absolute -bottom-4 -right-4 opacity-10 text-purple-600 group-hover:scale-110 transition-transform">
                                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left side: Deposits -->
                        <div>
                            <h3 class="text-lg font-black text-slate-800 mb-4 border-b border-slate-200 pb-2">শিক্ষকদের জমাদান তালিকা</h3>
                            <div class="space-y-3">
                                <div v-for="d in statementData.deposits" :key="'dep_'+d.id" class="p-4 bg-slate-50 border border-slate-100 rounded-xl flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ d.teacher_name }}</div>
                                        <div class="text-xs font-black text-slate-400 uppercase tracking-wider">{{ formatDate(d.deposit_date) }}</div>
                                    </div>
                                    <div class="font-black text-emerald-600">
                                        +৳{{ formatNumber(d.amount) }}
                                    </div>
                                </div>
                                <div v-if="!statementData.deposits.length" class="text-slate-400 text-sm italic py-4">কোন জমার রেকর্ড নেই।</div>
                            </div>
                        </div>
                        
                        <!-- Right side: Expenses -->
                        <div>
                            <h3 class="text-lg font-black text-slate-800 mb-4 border-b border-slate-200 pb-2">খরচের তালিকা</h3>
                            <div class="space-y-3">
                                <div v-for="e in statementData.expenses" :key="'exp_'+e.id" class="p-4 bg-slate-50 border border-slate-100 rounded-xl flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ e.category || 'ক্যাটাগরি নেই' }}</div>
                                        <div class="text-xs font-black text-slate-400 uppercase tracking-wider">{{ formatDate(e.expense_date) }}</div>
                                        <div class="text-xs text-slate-500 mt-1 truncate max-w-[200px]" :title="e.description">{{ e.description }}</div>
                                    </div>
                                    <div class="font-black text-rose-600">
                                        -৳{{ formatNumber(e.amount) }}
                                    </div>
                                </div>
                                <div v-if="!statementData.expenses.length" class="text-slate-400 text-sm italic py-4">কোন খরচের রেকর্ড নেই।</div>
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
    name: 'PrincipalCashierSetup',
    data() {
        return {
            loading: false,
            assigning: false,
            activeAssignment: null,
            teachers: [],
            history: [],
            selectedTeacher: '',
            showStatementModal: false,
            statementData: null
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fetchData() {
            this.loading = true;
            axios.get('/api/v1/billing/cashier-setup')
                .then(res => {
                    this.activeAssignment = res.data.active_assignment;
                    this.teachers = res.data.teachers || [];
                    this.history = res.data.history || [];
                })
                .catch(err => toastr.error('ডেটা লোড করতে সমস্যা হয়েছে'))
                .finally(() => this.loading = false);
        },
        assignCashier() {
            if (!this.selectedTeacher) return;
            
            if (!confirm('আপনি কি নিশ্চিত যে নতুন ক্যাশিয়ার নির্ধারণ করতে চান? আগের ক্যাশিয়ার স্বয়ংক্রিয়ভাবে বাতিল হয়ে যাবে।')) {
                return;
            }

            this.assigning = true;
            axios.post('/api/v1/billing/cashier-setup/assign', {
                teacher_id: this.selectedTeacher
            })
            .then(() => {
                toastr.success('ক্যাশিয়ার সফলভাবে নির্ধারণ করা হয়েছে');
                this.selectedTeacher = '';
                this.fetchData();
            })
            .catch(err => toastr.error('ক্যাশিয়ার নির্ধারণে সমস্যা হয়েছে'))
            .finally(() => this.assigning = false);
        },
        viewStatement(id) {
            axios.get(`/api/v1/billing/cashier-setup/statement/${id}`)
                .then(res => {
                    this.statementData = res.data;
                    this.showStatementModal = true;
                })
                .catch(err => toastr.error('স্টেটমেন্ট লোড করা যায়নি'));
        },
        closeStatementModal() {
            this.showStatementModal = false;
            this.statementData = null;
        },
        getInitials(name) {
            if (!name) return 'C';
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        },
        formatNumber(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('bn-BD');
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' });
        }
    }
};
</script>
