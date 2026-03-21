<template>
    <div class="parent-fee-dashboard pb-10">
        <!-- Header / Student Selector -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-wallet text-indigo-600"></i> ফিসের হিসাব
            </h2>
            
            <div v-if="children.length > 1" class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-600 hidden sm:inline">শিক্ষার্থী পরিবর্তন:</label>
                <select 
                    v-model="currentStudentId" 
                    @change="changeStudent"
                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 shadow-sm"
                >
                    <option v-for="child in children" :key="child.id" :value="child.id">
                        {{ child.student_name_en }} ({{ child.student_id }})
                    </option>
                </select>
            </div>
        </div>

        <!-- Student Info Quick View -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-8 flex flex-col sm:flex-row items-center gap-6">
            <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-indigo-100 shadow-sm bg-gray-50 flex items-center justify-center">
                <img v-if="studentData?.photo" :src="'/storage/' + studentData.photo" class="w-full h-full object-cover">
                <i v-else class="fas fa-user-graduate text-3xl text-gray-300"></i>
            </div>
            <div class="text-center sm:text-left">
                <h3 class="text-xl font-bold text-gray-800">{{ studentData?.name }}</h3>
                <p class="text-gray-500 text-xs tracking-wider mb-2 font-mono">ID: {{ studentData?.student_id }}</p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-3 mt-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-black rounded-lg border border-indigo-100 shadow-sm">
                        <i class="fas fa-graduation-cap text-[10px]"></i> {{ studentData?.class || 'N/A' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 text-xs font-black rounded-lg border border-green-100 shadow-sm">
                        <i class="fas fa-users text-[10px]"></i> {{ studentData?.section || 'N/A' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 text-orange-700 text-xs font-black rounded-lg border border-orange-100 shadow-sm">
                        <i class="fas fa-id-badge text-[10px]"></i> রোল: {{ studentData?.roll || 'N/A' }}
                    </span>
                </div>
            </div>
            
            <div class="sm:ml-auto flex flex-col items-center sm:items-end gap-1">
                <p class="text-xs text-gray-400 uppercase font-black">মোট বকেয়া</p>
                <p class="text-3xl font-black text-red-600">৳{{ totalDueAmount.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 mb-6">
            <button 
                @click="activeTab = 'due'" 
                :class="activeTab === 'due' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-all"
            >
                বকেয়া ফিস ({{ dueFees.length }})
            </button>
            <button 
                @click="activeTab = 'paid'" 
                :class="activeTab === 'paid' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-all"
            >
                পরিশোধকৃত ফিস ({{ paidFees.length }})
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Due Fees Content -->
            <div v-if="activeTab === 'due'" class="space-y-4">
                <div v-if="loading" class="text-center py-20">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                    <p class="text-gray-500 italic">ফিস লিস্ট লোড হচ্ছে...</p>
                </div>
                
                <div v-else-if="dueFees.length === 0" class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800">কোনো বকেয়া নেই!</h4>
                    <p class="text-gray-500">আপনার সকল ফিস পরিশোধিত আছে।</p>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <!-- List of Fees -->
                    <div class="lg:col-span-2 space-y-3">
                        <div 
                            v-for="fee in dueFees" 
                            :key="fee.id"
                            class="group relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:border-indigo-200 transition-all flex justify-between items-center"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-lg">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ fee.category_name }}</h4>
                                    <p class="text-xs text-gray-500">শেষ তারিখ: {{ formatDate(fee.due_date) }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span v-if="fee.fine > 0" class="text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded border border-red-100">
                                            জরিমানা: ৳{{ fee.fine }}
                                        </span>
                                        <span v-if="fee.status === 'partial'" class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded border border-blue-100">
                                            আংশিক পরিশোধিত
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-400 line-through" v-if="fee.paid_amount > 0">৳{{ fee.amount }}</p>
                                <p class="text-xl font-black text-indigo-600">৳{{ fee.total_due.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary Side -->
                    <div class="bg-gray-800 text-white rounded-3xl p-6 shadow-xl sticky top-4">
                        <h4 class="text-lg font-bold mb-6 border-b border-gray-700 pb-4">পেমেন্ট সামারি</h4>
                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between text-gray-400">
                                <span>মোট ফিস:</span>
                                <span>৳{{ dueFees.reduce((acc, f) => acc + f.amount, 0).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>মোট জরিমানা:</span>
                                <span>৳{{ dueFees.reduce((acc, f) => acc + f.fine, 0).toLocaleString() }}</span>
                            </div>
                            <div v-if="dueFees.reduce((acc, f) => acc + f.paid_amount, 0) > 0" class="flex justify-between text-gray-400 text-sm italic">
                                <span>ইতিমধ্যে পরিশোধিত:</span>
                                <span>- ৳{{ dueFees.reduce((acc, f) => acc + f.paid_amount, 0).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-xl font-black border-t border-gray-700 pt-4">
                                <span>সর্বমোট:</span>
                                <span class="text-indigo-400">৳{{ totalDueAmount.toLocaleString() }}</span>
                            </div>
                        </div>

                        <button 
                            @click="initiatePayment" 
                            :disabled="processing || totalDueAmount <= 0"
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg shadow-indigo-500/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group"
                        >
                            <span v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                            <i v-else class="fas fa-credit-card"></i>
                            {{ processing ? 'প্রসেসিং...' : 'পেমেন্ট করুন' }}
                        </button>
                        
                        <p class="text-[10px] text-gray-500 text-center mt-4">
                            <i class="fas fa-lock mr-1"></i> পেমেন্টটি SSLCommerz এর মাধ্যমে নিরাপদে সম্পন্ন করা হবে।
                        </p>
                    </div>
                </div>
            </div>

            <!-- Paid Fees Content -->
            <div v-if="activeTab === 'paid'" class="space-y-4">
                <div v-if="loading" class="text-center py-20">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                </div>

                <div v-else-if="paidFees.length === 0" class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
                    <p class="text-gray-500 italic">এখন পর্যন্ত কোনো পেমেন্ট রেকর্ড নেই।</p>
                </div>

                <div v-else class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">ট্রানজেকশন #</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">তারিখ</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase text-center">মাধ্যম</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase text-right">পরিমাণ</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase text-right">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="payment in paidFees" :key="payment.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800 text-sm">
                                        {{ payment.payment_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ formatDateTime(payment.received_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded-full uppercase">
                                            {{ payment.payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="font-black text-gray-800">৳{{ payment.amount_paid.toLocaleString() }}</p>
                                        <p v-if="payment.fine_applied > 0" class="text-[10px] text-red-500 mt-0.5">জরিমানা সহ</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            @click="downloadReceipt(payment)"
                                            :disabled="downloadingId === payment.id"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white rounded-lg transition-all text-xs font-bold disabled:opacity-50 disabled:cursor-wait"
                                        >
                                            <span v-if="downloadingId === payment.id" class="animate-spin rounded-full h-3 w-3 border-b-2 border-current"></span>
                                            <i v-else class="fas fa-file-pdf"></i>
                                            {{ downloadingId === payment.id ? 'ডাউনলোড হচ্ছে...' : 'পিডিএফ' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    props: {
        children: {
            type: Array,
            required: true
        },
        selectedStudent: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            currentStudentId: this.selectedStudent?.id,
            activeTab: 'due',
            loading: false,
            processing: false,
            downloadingId: null,
            dueFees: [],
            paidFees: [],
            studentData: null,
            academicYearId: null
        }
    },
    computed: {
        totalDueAmount() {
            return this.dueFees.reduce((acc, fee) => acc + fee.total_due, 0);
        }
    },
    mounted() {
        this.fetchFees();
    },
    methods: {
        changeStudent() {
            // Re-fetch data for the new student
            this.fetchFees();
        },
        fetchFees() {
            this.loading = true;
            axios.get('/api/v1/parent/fees', {
                params: { student_id: this.currentStudentId }
            })
            .then(res => {
                this.dueFees = res.data.due_fees || [];
                this.paidFees = res.data.paid_fees || [];
                this.studentData = res.data.student;
                this.academicYearId = res.data.academic_year_id;
                this.loading = false;
            })
            .catch(err => {
                this.loading = false;
                console.error(err);
                toastr.error('ফিস তথ্য সংগ্রহ করতে ব্যর্থ হয়েছে।');
            });
        },
        initiatePayment() {
            if (this.dueFees.length === 0) return;
            
            this.processing = true;
            
            // Format fees for initial SSL payment
            const feesPayload = this.dueFees.map(fee => ({
                student_fee_id: fee.id,
                amount: fee.amount - fee.paid_amount, // Basic due
                fine_amount: fee.fine // Current fine
            }));

            axios.post('/api/v1/billing/fees/initiate-ssl', {
                student_id: this.currentStudentId,
                academic_year_id: this.academicYearId,
                fees: feesPayload,
                remarks: 'Payment via Parent Panel'
            })
            .then(res => {
                if (res.data.gateway_url) {
                    window.location.href = res.data.gateway_url;
                } else {
                    toastr.error('পেমেন্ট গেটওয়েতে সমস্যা হয়েছে।');
                    this.processing = false;
                }
            })
            .catch(err => {
                this.processing = false;
                toastr.error(err.response?.data?.message || 'পেমেন্ট গেটওয়ে চালু করতে ব্যর্থ হয়েছে।');
            });
        },
        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        formatDateTime(dateStr) {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleString('bn-BD', { 
                day: 'numeric', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },
        downloadReceipt(payment) {
            if (this.downloadingId) return;
            this.downloadingId = payment.id;
            axios.get('/api/v1/' + payment.receipt_url, { responseType: 'blob' })
                .then(res => {
                    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `Receipt-${payment.payment_number}.pdf`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    window.URL.revokeObjectURL(url);
                })
                .catch(() => {
                    toastr.error('পিডিএফ ডাউনলোড করতে ব্যর্থ হয়েছে।');
                })
                .finally(() => {
                    this.downloadingId = null;
                });
        }
    }
}
</script>

<style scoped>
.parent-fee-dashboard {
    font-family: 'Hind Siliguri', sans-serif;
}
</style>
