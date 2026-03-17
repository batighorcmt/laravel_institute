<template>
    <div class="fee-collection-container p-6 bg-gray-50 min-h-screen">
        <div class="w-full px-4">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">ফি কালেকশন</h1>
                    <p class="text-gray-500 mt-1">শিক্ষার্থীদের ফি গ্রহণ এবং রিসিট জেনারেট করুন</p>
                </div>
                <div class="text-right">
                    <span class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full font-medium shadow-sm">
                        {{ new Date().toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column: Student Search & Dues -->
                <div class="lg:col-span-9 space-y-6">
                    <!-- Student Search Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-visible">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">শ্রেণী</label>
                                <select v-model="filters.class_id" @change="fetchSections" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                                    <option :value="null">সকল শ্রেণী</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">শাখা</label>
                                <select v-model="filters.section_id" @change="fetchStudentsList" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                                    <option :value="null">সকল শাখা</option>
                                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">শিক্ষার্থী নির্বাচন</label>
                                <div class="relative group">
                                    <input 
                                        type="text" 
                                        v-model="studentSearchTerms" 
                                        @focus="showStudentDropdown = true"
                                        placeholder="নাম বা রোল দিয়ে খুঁজুন..."
                                        class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm py-2 px-3"
                                    >
                                    <div v-if="showStudentDropdown && filteredStudents.length > 0" class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-xl max-h-60 overflow-y-auto overflow-x-hidden">
                                        <div 
                                            v-for="std in filteredStudents" 
                                            :key="std.id"
                                            @click="selectStudentFromDropdown(std)"
                                            class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0 flex items-center gap-3"
                                        >
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-gray-800 truncate">{{ std.name_bn || std.name_en }}</p>
                                                <p class="text-xs text-indigo-600 font-bold mt-0.5">আইডি: {{ std.student_id }} &nbsp;|&nbsp; রোল: {{ std.roll_no }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="showStudentDropdown && filteredStudents.length === 0 && studentsList.length > 0" class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-xl p-4 text-center text-xs text-gray-500">
                                        ম্যাচিং শিক্ষার্থী পাওয়া যায়নি
                                    </div>
                                    <div v-if="showStudentDropdown" class="fixed inset-0 z-[90]" @click="showStudentDropdown = false"></div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">সরাসরি আইডি সার্চ</label>
                                <input v-model="filters.q" type="text" placeholder="উদাঃ S26001" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button @click="searchStudents" :disabled="searching" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all flex items-center gap-2">
                                <span v-if="searching" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                                খুঁজুন
                            </button>
                        </div>
                        
                        <!-- Search Results Dropdown -->
                        <div v-if="searchResults.length > 0" class="mt-6 border-t pt-6">
                            <h4 class="text-sm font-bold text-gray-700 mb-3">সার্চ রেজাল্ট:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div 
                                    v-for="student in searchResults" 
                                    :key="student.id"
                                    @click="selectStudent(student)"
                                    class="p-4 bg-gray-50 hover:bg-indigo-50 border border-transparent hover:border-indigo-200 rounded-2xl cursor-pointer flex items-center gap-4 transition-all"
                                >
                                    <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 bg-indigo-100 flex items-center justify-center">
                                        <img 
                                            v-if="student.photo_url" 
                                            :src="student.photo_url" 
                                            :alt="student.name_bn || student.name_en"
                                            class="w-full h-full object-cover"
                                            @error="$event.target.style.display='none'"
                                        >
                                        <span v-else class="text-indigo-600 font-bold text-lg">
                                            {{ student.name_bn ? student.name_bn[0] : student.name_en[0] }}
                                        </span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <h4 class="font-bold text-gray-800 truncate">{{ student.name_bn || student.name_en }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">শ্রেণী: {{ student.class_name }} | আইডি: {{ student.student_id }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Student Dues Card -->
                    <div v-if="selectedStudent" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-indigo-600 p-6 flex justify-between items-center text-white">
                            <div class="flex items-center gap-4">
                                <!-- Student Photo -->
                                <div class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0 bg-white/20 border-2 border-white/40 flex items-center justify-center">
                                    <img 
                                        v-if="selectedStudent.photo_url" 
                                        :src="selectedStudent.photo_url"
                                        :alt="selectedStudent.name_bn || selectedStudent.name_en"
                                        class="w-full h-full object-cover"
                                        @error="$event.target.style.display='none'"
                                    >
                                    <span v-else class="text-2xl font-bold text-white">
                                        {{ selectedStudent.name_bn ? selectedStudent.name_bn[0] : selectedStudent.name_en[0] }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">{{ selectedStudent.name_bn || selectedStudent.name_en }}</h3>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                        <span class="text-indigo-100 text-sm">শ্রেণী: <strong class="text-white">{{ selectedStudent.class_name }}</strong></span>
                                        <span class="text-indigo-100 text-sm">রোল: <strong class="text-white">{{ selectedStudent.roll_no || 'N/A' }}</strong></span>
                                        <span class="text-indigo-100 text-sm">আইডি: <strong class="text-white">{{ selectedStudent.student_id }}</strong></span>
                                    </div>
                                </div>
                            </div>
                            <button @click="clearSelection" class="hover:bg-white/10 p-2 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6">
                            <div v-if="loadingFees" class="flex flex-col items-center py-12">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
                                <p class="text-gray-500">বকেয়া ফি লোড হচ্ছে...</p>
                            </div>

                            <div v-else-if="feeLoadError" class="flex flex-col items-center py-12">
                                <div class="bg-red-100 p-4 rounded-xl mb-4 w-full">
                                    <p class="text-red-600 font-bold text-sm">⚠️ API Error: {{ feeLoadError }}</p>
                                    <p class="text-red-400 text-xs mt-1">F12 → Console ট্যাব দেখুন।</p>
                                </div>
                            </div>

                            <div v-else-if="dueFees.length === 0" class="flex flex-col items-center py-12">
                                <div class="bg-green-100 p-4 rounded-full mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">কোন বকেয়া নেই!</h3>
                                <p class="text-gray-500">এই শিক্ষার্থীর সকল ফি পরিশোধিত।</p>
                            </div>

                            <div v-else>
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="font-bold text-gray-700">বকেয়া তালিকা</h4>
                                    <button @click="selectAllFees" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">সাবই সিলেক্ট করুন</button>
                                </div>
                                
                                <div class="space-y-3">
                                    <div 
                                        v-for="fee in dueFees" 
                                        :key="fee.id"
                                        class="flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer group"
                                        :class="fee.selected ? 'border-indigo-500 bg-indigo-50/30' : 'border-gray-100 hover:border-indigo-200'"
                                        @click="toggleFeeSelection(fee)"
                                    >
                                        <div class="relative flex items-center justify-center">
                                            <input 
                                                type="checkbox" 
                                                v-model="fee.selected"
                                                class="w-6 h-6 rounded-md border-gray-300 text-indigo-600 focus:ring-indigo-500 pointer-events-none"
                                            >
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h5 class="font-bold text-gray-800">{{ fee.fee_structure.category.name }}</h5>
                                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold pt-1">
                                                        {{ fee.month ? translateMonth(fee.month) : 'এককালীন' }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-lg font-bold text-indigo-700">৳{{ fee.amount - fee.paid_amount }}</span>
                                                    <div v-if="fee.paid_amount > 0" class="text-[10px] text-orange-600 font-bold bg-orange-50 px-2 rounded-full mt-1">
                                                        আংশিক পরিশোধিত: ৳{{ fee.paid_amount }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary & Payment -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 sticky top-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">পরিশোধের সারসংক্ষেপ</h3>
                        
                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between text-gray-600 italic" v-if="selectedFees.length === 0">
                                <span>কিছু সিলেক্ট করা হয়নি</span>
                                <span>৳০.০০</span>
                            </div>
                            <div 
                                v-for="fee in selectedFees" 
                                :key="fee.id" 
                                class="flex justify-between items-center animate-slide-in"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ fee.fee_structure.category.name }}</p>
                                    <p class="text-[10px] text-gray-400 capitalize">{{ fee.month ? translateMonthBn(fee.month) : 'এককালীন' }}</p>
                                </div>
                                <span class="font-bold text-gray-700">৳{{ fee.amount - fee.paid_amount }}</span>
                            </div>

                            <div class="border-t border-dashed border-gray-200 pt-4 mt-4">
                                <div class="flex justify-between items-center total-row">
                                    <span class="text-lg font-bold text-gray-800">মোট পরিমাণ</span>
                                    <span class="text-2xl font-black text-indigo-600">৳{{ totalPayable }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedFees.length > 0" class="space-y-4 animate-fade-in">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">পেমেন্ট মাধ্যম</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button 
                                        v-for="method in paymentMethods"
                                        :key="method.value"
                                        @click="paymentMethod = method.value"
                                        :class="paymentMethod === method.value ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-indigo-200'"
                                        class="py-3 px-2 rounded-xl border text-sm font-bold transition-all"
                                    >
                                        {{ method.label }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">মন্তব্য (ঐচ্ছিক)</label>
                                <textarea 
                                    v-model="remarks" 
                                    rows="2" 
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm"
                                    placeholder="কোন বিশেষ তথ্য..."
                                ></textarea>
                            </div>

                            <button 
                                @click="processPayment"
                                :disabled="processing"
                                class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform active:scale-95 transition-all flex items-center justify-center gap-3"
                            >
                                <span v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                                {{ processing ? 'প্রসেসিং হচ্ছে...' : 'পেমেন্ট সম্পন্ন করুন' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full text-center space-y-6 animate-scale-up">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-4xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">ধন্যবাদ!</h2>
                    <p class="text-gray-500 mt-2">পেমেন্ট সফলভাবে গ্রহণ করা হয়েছে।</p>
                    <div class="mt-4 p-4 bg-gray-50 rounded-2xl flex flex-col gap-2">
                        <p class="text-sm font-bold text-gray-700">রিসিট নং: {{ paymentInfo.payment_number }}</p>
                        <p class="text-xl font-black text-indigo-600">৳{{ totalPayable }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="printReceipt" class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors">প্রিন্ট রিসিট</button>
                    <button @click="resetForm" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-colors">পরবর্তী</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import debounce from 'lodash/debounce';

export default {
    props: {
        academicYearId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            filters: {
                class_id: null,
                section_id: null,
                student_id: null,
                role_no: '',
                q: ''
            },
            classes: [],
            sections: [],
            studentsList: [],
            searchResults: [],
            studentSearchTerms: '',
            showStudentDropdown: false,
            searching: false,
            selectedStudent: null,
            loadingFees: false,
            dueFees: [],
            paymentMethod: 'cash',
            paymentMethods: [
                { value: 'cash', label: 'Cash' },
                { value: 'bkash', label: 'Bkash' },
                { value: 'nagad', label: 'Nagad' },
                { value: 'sslcommerz', label: 'SSLCommerz' },
            ],
            remarks: '',
            processing: false,
            showSuccessModal: false,
            paymentInfo: null,
            totalPayable: 0,
            feeLoadError: null
        }
    },
    async mounted() {
        this.fetchClasses();
        
        // Handle student_id from URL
        const urlParams = new URLSearchParams(window.location.search);
        const studentId = urlParams.get('student_id');
        if (studentId) {
            try {
                const res = await axios.get(`/api/v1/principal/students/${studentId}`);
                if (res.data) {
                    this.selectStudent(res.data);
                }
            } catch (e) {
                console.error('Error fetching student from URL:', e);
            }
        }
    },
    computed: {
        selectedFees() {
            return this.dueFees.filter(f => f.selected);
        },
        filteredStudents() {
            let list = this.studentsList;
            if (this.studentSearchTerms) {
                const q = this.studentSearchTerms.toLowerCase();
                list = list.filter(s => 
                    (s.name_bn && s.name_bn.toLowerCase().includes(q)) || 
                    (s.name_en && s.name_en.toLowerCase().includes(q)) || 
                    (s.student_id && s.student_id.toLowerCase().includes(q)) ||
                    (s.roll_no && String(s.roll_no).includes(q))
                );
            }
            // Sort by roll_no numerically
            return list.slice().sort((a, b) => {
                const ra = a.roll_no ? parseInt(a.roll_no) : 9999;
                const rb = b.roll_no ? parseInt(b.roll_no) : 9999;
                return ra - rb;
            });
        }
    },
    watch: {
        selectedFees: {
            handler() {
                this.calculateTotal();
            },
            deep: true
        }
    },
    methods: {
        fetchClasses() {
            axios.get('/api/v1/meta/classes').then(res => this.classes = res.data);
        },

        fetchSections() {
            this.sections = [];
            this.studentsList = [];
            this.studentSearchTerms = '';
            this.filters.section_id = null;
            this.filters.student_id = null;
            if (!this.filters.class_id) return;
            axios.get(`/api/v1/meta/sections?class_id=${this.filters.class_id}`).then(res => {
                this.sections = res.data;
                this.fetchStudentsList();
            });
        },

        fetchStudentsList() {
            this.studentsList = [];
            this.filters.student_id = null;
            this.studentSearchTerms = '';
            if (!this.filters.class_id) return;
            
            const params = new URLSearchParams();
            params.append('class_id', this.filters.class_id);
            if (this.filters.section_id) params.append('section_id', this.filters.section_id);
            params.append('limit', 500);

            axios.get(`/api/v1/principal/students/search?${params.toString()}`)
                .then(res => {
                    this.studentsList = res.data;
                });
        },

        selectStudentFromDropdown(student) {
            this.filters.student_id = student.id;
            this.studentSearchTerms = student.name_bn || student.name_en;
            this.showStudentDropdown = false;
            this.selectStudent(student);
        },

        searchStudents() {
            if (this.filters.student_id) {
                const student = this.studentsList.find(s => s.id === this.filters.student_id);
                if (student) {
                    this.selectStudent(student);
                    return;
                }
            }

            if (!this.filters.q && !this.filters.class_id && !this.filters.roll_no) {
                return;
            }
            this.searching = true;
            this.searchResults = [];
            
            const params = new URLSearchParams();
            if (this.filters.class_id) params.append('class_id', this.filters.class_id);
            if (this.filters.section_id) params.append('section_id', this.filters.section_id);
            if (this.filters.roll_no) params.append('roll_no', this.filters.roll_no);
            if (this.filters.q) params.append('q', this.filters.q);

            axios.get(`/api/v1/principal/students/search?${params.toString()}`)
                .then(res => {
                    this.searchResults = res.data;
                    if (this.searchResults.length === 1) {
                        this.selectStudent(this.searchResults[0]);
                    }
                })
                .finally(() => {
                    this.searching = false;
                });
        },

        selectStudent(student) {
            this.selectedStudent = student;
            this.searchResults = [];
            this.fetchDueFees(student.id);
        },

        fetchDueFees(studentId) {
            this.loadingFees = true;
            this.feeLoadError = null;
            axios.get(`/api/v1/billing/fees/student/${studentId}/due`)
                .then(res => {
                    console.log('[FeeCollection] API Response:', res.data);
                    if (res.data.due_fees) {
                        this.dueFees = res.data.due_fees.map(f => ({
                            ...f,
                            selected: true
                        }));
                    } else {
                        console.warn('[FeeCollection] due_fees field missing in response', res.data);
                        this.dueFees = [];
                    }
                    this.loadingFees = false;
                })
                .catch(err => {
                    const errMsg = err.response?.data?.message || err.response?.data?.error_debug || err.message;
                    const errStatus = err.response?.status;
                    console.error('[FeeCollection] Error loading dues:', errStatus, errMsg, err.response?.data);
                    this.feeLoadError = `[${errStatus}] ${errMsg}`;
                    this.dueFees = [];
                    this.loadingFees = false;
                });
        },

        toggleFeeSelection(fee) {
            fee.selected = !fee.selected;
        },

        selectAllFees() {
            this.dueFees.forEach(f => f.selected = true);
        },

        calculateTotal() {
            this.totalPayable = this.selectedFees.reduce((acc, fee) => {
                return acc + (parseFloat(fee.amount) - parseFloat(fee.paid_amount));
            }, 0);
        },

        processPayment() {
            if (this.selectedFees.length === 0) {
                alert('অনুগ্রহ করে অন্তত একটি ফি নির্বাচন করুন।');
                return;
            }
            
            console.log('[FeeCollection] Starting payment process...', {
                method: this.paymentMethod,
                student: this.selectedStudent.id,
                total: this.totalPayable
            });

            this.processing = true;

            const payload = {
                student_id: this.selectedStudent.id,
                academic_year_id: this.academicYearId || this.selectedFees[0]?.academic_year_id,
                payment_method: this.paymentMethod,
                remarks: this.remarks,
                received_at: new Date().toISOString().split('T')[0], // YYYY-MM-DD format
                fees: this.selectedFees.map(f => ({
                    student_fee_id: f.id,
                    amount: f.amount - f.paid_amount
                }))
            };

            console.log('[FeeCollection] Payload generated:', payload);

            // If SSLCommerz is selected, use the initiation endpoint and redirect
            if (this.paymentMethod === 'sslcommerz') {
                console.log('[FeeCollection] Redirecting to SSLCommerz initiation API...');
                axios.post('/api/v1/billing/fees/initiate-ssl', payload)
                    .then(res => {
                        console.log('[FeeCollection] SSL Initiation Response:', res.data);
                        if (res.data.success && res.data.gateway_url) {
                            window.location.href = res.data.gateway_url;
                        } else {
                            alert('SSLCommerz গেটওয়ে থেকে পেমেন্ট পারমিশন পাওয়া যায়নি। সম্ভবত সেটিংস কনফিগার করা নেই।');
                            this.processing = false;
                        }
                    })
                    .catch(err => {
                        const msg = err.response?.data?.message || 'SSLCommerz পেমেন্ট শুরু করতে ত্রুটি হয়েছে।';
                        console.error('[FeeCollection] SSL Error:', err.response?.data);
                        alert(msg);
                        this.processing = false;
                    });
                return;
            }

            console.log('[FeeCollection] Processing immediate payment (Cash/Mobile)...');
            axios.post('/api/v1/billing/fees/collect', payload)
                .then(res => {
                    console.log('[FeeCollection] Payment success:', res.data);
                    this.paymentInfo = res.data.data;
                    this.showSuccessModal = true;
                    this.processing = false;
                })
                .catch(err => {
                    const msg = err.response?.data?.message || 'পেমেন্ট প্রসেস করতে ত্রুটি হয়েছে।';
                    console.error('[FeeCollection] Collect Error:', err.response?.data);
                    alert(msg);
                    this.processing = false;
                });
        },

        printReceipt() {
            if (this.paymentInfo?.receipt_url) {
                window.open(this.paymentInfo.receipt_url, '_blank');
            }
        },

        translateMonth(monthStr) {
            if (!monthStr) return null;
            const months = {
                '01': 'জানুয়ারি', '02': 'ফেব্রুয়ারি', '03': 'মার্চ', '04': 'এপ্রিল',
                '05': 'মে', '06': 'জুন', '07': 'জুলাই', '08': 'আগস্ট',
                '09': 'সেপ্টেম্বর', '10': 'অক্টোবর', '11': 'নভেম্বর', '12': 'ডিসেম্বর'
            };
            const [year, month] = monthStr.split('-');
            return months[month] ? `${months[month]}, ${year}` : monthStr;
        },

        translateMonthBn(monthStr) {
            if (!monthStr) return 'এককালীন';
            const months = {
                '01': 'জানুয়ারি', '02': 'ফেব্রুয়ারি', '03': 'মার্চ', '04': 'এপ্রিল',
                '05': 'মে', '06': 'জুন', '07': 'জুলাই', '08': 'আগস্ট',
                '09': 'সেপ্টেম্বর', '10': 'অক্টোবর', '11': 'নভেম্বর', '12': 'ডিসেম্বর'
            };
            const [year, month] = monthStr.split('-');
            return months[month] ? `${months[month]}-${year}` : monthStr;
        },

        clearSelection() {
            this.selectedStudent = null;
            this.dueFees = [];
            this.totalPayable = 0;
        },

        resetForm() {
            this.showSuccessModal = false;
            if (this.selectedStudent) {
                this.fetchDueFees(this.selectedStudent.id);
            }
            this.paymentMethod = 'cash';
            this.remarks = '';
            this.totalPayable = 0;
        }
    }
}
</script>

<style scoped>
.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
.animate-scale-up {
    animation: scaleUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.fee-collection-container {
    font-family: 'Hind Siliguri', sans-serif;
}
</style>
