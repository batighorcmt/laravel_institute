<template>
    <div class="fee-config-container p-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">ফি কনফিগারেশন</h1>
                    <p class="text-gray-500 mt-1">বিভিন্ন ফি-র হার এবং সময়কাল নির্ধারণ করুন</p>
                </div>
                <div class="flex gap-3">
                    <button @click="openGenerateModal" class="px-5 py-2.5 bg-green-600 text-white rounded-xl font-bold shadow-sm hover:bg-green-700 transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        ফি জেনারেট করুন
                    </button>
                    <button @click="openCategoryModal" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-sm hover:bg-indigo-700 transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        নতুন ক্যাটাগরি
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex flex-col items-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
                <p class="text-gray-500 italic">তথ্য লোড হচ্ছে...</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Fee Category Cards -->
                <div
                    v-for="category in categories"
                    :key="category.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="p-6 border-b border-gray-50 flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">{{ category.name }}</h3>
                            <span class="inline-block mt-2 px-2 py-1 bg-gray-100 text-gray-600 text-[10px] uppercase font-bold rounded">
                                {{ category.frequency }}
                            </span>
                        </div>
                        <button @click="addStructure(category)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="ফি যুক্ত করুন">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-4 space-y-3">
                        <div v-if="category.fee_structures.length === 0" class="text-center py-6 text-gray-400 text-sm">
                            টেবিলের ডাটা পাওয়া যায়নি
                        </div>
                        <div
                            v-for="struct in category.fee_structures"
                            :key="struct.id"
                            class="p-3 bg-gray-50 rounded-xl flex justify-between items-center group"
                        >
                            <div>
                                <p class="text-sm font-bold text-gray-700">
                                    {{ getClassName(struct.class_id) }}
                                </p>
                                <p class="text-[10px] text-gray-500">
                                    {{ formatDate(struct.effective_from) }} - {{ struct.effective_to ? formatDate(struct.effective_to) : 'চলমান' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-black text-indigo-600">৳{{ struct.amount }}</span>
                                <button @click="editStructure(struct)" class="opacity-0 group-hover:opacity-100 p-1.5 text-indigo-500 hover:text-indigo-700 transition-all" title="সম্পাদনা">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                    </svg>
                                </button>
                                <button @click="deleteStructure(struct.id)" class="opacity-0 group-hover:opacity-100 p-1.5 text-red-400 hover:text-red-600 transition-all" title="মুছুন">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full space-y-6">
                <h2 class="text-2xl font-bold text-gray-800">নতুন ক্যাটাগরি</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">নাম</label>
                        <input v-model="newCategory.name" type="text" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500" placeholder="উদাঃ জানুয়ারি ফি">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">ফ্রিকোয়েন্সি</label>
                        <select v-model="newCategory.frequency" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option value="monthly">মাসিক (Monthly)</option>
                            <option value="annual">বার্ষিক (Annual)</option>
                            <option value="one_time">এককালীন (One-time)</option>
                            <option value="termly">টার্ম ভিত্তিক (Termly)</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button @click="showCategoryModal = false" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200">বাতিল</button>
                    <button @click="saveCategory" class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">সংরক্ষণ করুন</button>
                </div>
            </div>
        </div>

        <!-- Add Structure Modal -->
        <div v-if="showStructureModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-lg w-full space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">ফি নির্ধারণ ({{ selectedCategory.name }})</h2>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full capitalize">{{ selectedCategory.frequency }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">শ্রেণী</label>
                        <select v-model="newStructure.class_id" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option :value="null">সকল শ্রেণী (Common)</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">টাকার পরিমাণ (৳)</label>
                        <input v-model="newStructure.amount" type="number" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xl font-bold" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">কার্যকর শুরু</label>
                        <input v-model="newStructure.effective_from" type="date" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">কার্যকর শেষ (ঐচ্ছিক)</label>
                        <input v-model="newStructure.effective_to" type="date" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button @click="showStructureModal = false" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200">বাতিল</button>
                    <button @click="saveStructure" class="flex-1 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 shadow-lg">নিশ্চিত করুন</button>
                </div>
            </div>
        </div>

        <!-- Generate Dues Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full space-y-6 text-center">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">ফি জেনারেট করুন</h2>
                <p class="text-gray-500">নির্বাচিত সেশন এবং মাসের জন্য শিক্ষার্থীদের প্রোফাইলে বকেয়া ফি যুক্ত করা হবে।</p>

                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">সেশন</label>
                        <select v-model="generateForm.academic_year_id" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">মাস (শুধুমাত্র মাসিক ফি-র জন্য)</label>
                        <input v-model="generateForm.month" type="month" class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button @click="showGenerateModal = false" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200">বাতিল</button>
                    <button @click="generateDues" :disabled="processing" class="flex-1 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 disabled:opacity-50">
                        {{ processing ? 'প্রসেসিং...' : 'শুরু করুন' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            loading: true,
            processing: false,
            categories: [],
            classes: [],
            academicYears: [],
            showCategoryModal: false,
            showStructureModal: false,
            showGenerateModal: false,
            selectedCategory: null,
            newCategory: {
                name: '',
                frequency: 'monthly',
                is_common: false,
                active: true
            },
            editingCategoryId: null,
            newStructure: {
                fee_category_id: null,
                class_id: null,
                amount: 0,
                effective_from: new Date().toISOString().substr(0, 10),
                effective_to: null,
                due_day_of_month: 10
            },
            generateForm: {
                academic_year_id: null,
                month: new Date().toISOString().substr(0, 7)
            }
        }
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fetchData() {
            this.loading = true;
            axios.get('/api/v1/billing/config')
                .then(res => {
                    this.categories = res.data.categories;
                    this.classes = res.data.classes;
                    this.academicYears = res.data.academic_years || [];
                    if (this.academicYears.length > 0 && !this.generateForm.academic_year_id) {
                        const current = this.academicYears.find(y => y.is_current) || this.academicYears[0];
                        this.generateForm.academic_year_id = current.id;
                    }
                    this.loading = false;
                });
        },

        openGenerateModal() {
            this.showGenerateModal = true;
        },

        generateDues() {
            if (!this.generateForm.academic_year_id) return alert('সেশন নির্বাচন করুন');
            this.processing = true;
            axios.post('/api/v1/billing/config/generate-dues', this.generateForm)
                .then(res => {
                    alert(res.data.message + ` (${res.data.count} টি রেকর্ড তৈরি হয়েছে)`);
                    this.showGenerateModal = false;
                })
                .catch(err => alert(err.response?.data?.message || 'ত্রুটি হয়েছে'))
                .finally(() => this.processing = false);
        },

        openCategoryModal() {
            this.newCategory = { name: '', frequency: 'monthly', is_common: false };
            this.showCategoryModal = true;
        },

        saveCategory() {
            if (this.editingCategoryId) {
                axios.patch(`/api/v1/billing/config/categories/${this.editingCategoryId}`, this.newCategory)
                    .then(res => {
                        this.showCategoryModal = false;
                        this.editingCategoryId = null;
                        this.fetchData();
                    })
                    .catch(err => alert(err.response?.data?.message || 'ত্রুটি হয়েছে'));
            } else {
                axios.post('/api/v1/billing/config/categories', this.newCategory)
                    .then(res => {
                        this.showCategoryModal = false;
                        this.fetchData();
                    })
                    .catch(err => alert(err.response?.data?.message || 'ত্রুটি হয়েছে'));
            }
        },

        addStructure(category) {
            this.selectedCategory = category;
            this.newStructure = {
                fee_category_id: category.id,
                class_id: null,
                amount: 0,
                effective_from: new Date().toISOString().substr(0, 10),
                effective_to: null,
                due_day_of_month: 10
            };
            this.showStructureModal = true;
        },

        editCategory(category) {
            this.editingCategoryId = category.id;
            this.newCategory = {
                name: category.name || '',
                frequency: category.frequency || 'monthly',
                is_common: !!category.is_common,
                active: category.active !== undefined ? !!category.active : true
            };
            this.showCategoryModal = true;
        },

        deactivateCategory(id) {
            if (!confirm('আপনি কি নিশ্চিত যে ক্যাটাগরিটি নিষ্ক্রিয় করতে চান?')) return;
            axios.delete(`/api/v1/billing/config/categories/${id}`)
                .then(() => this.fetchData())
                .catch(err => alert(err.response?.data?.message || 'ত্রুটি হয়েছে'));
        },

        saveStructure() {
            axios.post('/api/v1/billing/config/structures', this.newStructure)
                .then(res => {
                    this.showStructureModal = false;
                    this.fetchData();
                })
                .catch(err => alert(err.response?.data?.message || 'ত্রুটি হয়েছে'));
        },

        editStructure(struct) {
            this.selectedCategory = this.categories.find(c => c.id === struct.fee_category_id) || this.selectedCategory;
            this.newStructure = {
                id: struct.id,
                fee_category_id: struct.fee_category_id,
                class_id: struct.class_id,
                amount: struct.amount,
                effective_from: struct.effective_from,
                effective_to: struct.effective_to,
                due_day_of_month: struct.due_day_of_month || 10
            };
            this.showStructureModal = true;
        },

        deleteStructure(id) {
            if (!confirm('আপনি কি নিশ্চিত?')) return;
            axios.delete(`/api/v1/billing/config/structures/${id}`)
                .then(() => this.fetchData());
        },

        getClassName(id) {
            if (!id) return "সকল শ্রেণী";
            const cls = this.classes.find(c => c.id === id);
            return cls ? cls.name : 'Unknown';
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>

<style scoped>
.fee-config-container {
    font-family: 'Hind Siliguri', sans-serif;
}
</style>
