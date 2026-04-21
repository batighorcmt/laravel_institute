<template>
    <div class="row mt-4">
        <div class="col-md-12 mb-4">
            <h3 class="premium-title">
                <i class="fas fa-file-signature mr-2"></i>ডকুমেন্ট টেম্পলেট ম্যানেজমেন্ট
            </h3>
        </div>

        <div class="col-md-8">
            <div class="card premium-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas" :class="editingTemplate ? 'fa-edit text-warning' : 'fa-plus-circle text-success'"></i>
                        {{ editingTemplate ? 'টেম্পলেট এডিট করুন' : 'নতুন টেম্পলেট তৈরি' }}
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3" @click="resetForm" v-if="editingTemplate">
                         নতুন তৈরি করুন
                    </button>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="saveTemplate">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="section-label">প্রত্যয়ন এর ধরন</label>
                                <select v-model="form.type" class="form-control modern-input" required>
                                    <option value="prottayon">প্রত্যয়নপত্র (Prottayon)</option>
                                    <option value="certificate">সার্টিফিকেট (Certificate)</option>
                                    <option value="testimonial">প্রশংসাপত্র (Testimonial)</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="section-label">ভাষা (Language)</label>
                                <select v-model="form.language" class="form-control modern-input" required>
                                    <option value="bn">বাংলা (Bengali)</option>
                                    <option value="en">ইংরেজি (English)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="section-label">টেম্পলেট এর নাম</label>
                            <input type="text" v-model="form.name" class="form-control modern-input" placeholder="যেমন: সাধারণ প্রত্যয়নপত্র" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="section-label d-flex justify-content-between">
                                টেম্পলেট বডি
                                <span class="badge badge-light border xsmall">কি-ওয়ার্ড ব্যবহার করুন</span>
                            </label>
                            <textarea id="template_body" v-model="form.content" 
                                      class="form-control modern-input" rows="12" 
                                      placeholder="এখানে টেম্পলেট লিখুন..." required></textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <div class="custom-control custom-switch premium-switch">
                                <input type="checkbox" v-model="form.is_active" class="custom-control-input" id="statusSwitch">
                                <label class="custom-control-label font-weight-bold" for="statusSwitch">
                                    {{ form.is_active ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Inactive)' }}
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-light rounded-pill px-4 mr-2" @click="resetForm" v-if="editingTemplate">
                                    বাতিল
                                </button>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 premium-shadow" :disabled="loading">
                                    <i class="fas mr-2" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                    {{ editingTemplate ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-4 mt-md-0">
            <KeywordSelector :type="form.type" @select="insertKeyword" class="mb-4" />

            <div class="card premium-card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-light-blue py-3">
                    <h5 class="mb-0 text-primary small font-weight-bold text-uppercase">সংরক্ষিত টেম্পলেটসমূহ</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div v-for="template in templates" :key="template.id" 
                             class="list-group-item list-group-item-action d-flex flex-column p-3 border-bottom border-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 font-weight-bold text-dark">{{ template.name }}</h6>
                                <span class="status-dot" :class="template.is_active ? 'bg-success' : 'bg-danger'"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="badge-group">
                                    <span class="badge badge-soft-info mr-1">{{ template.type }}</span>
                                    <span class="badge" :class="template.language === 'bn' ? 'badge-soft-warning' : 'badge-soft-primary'">
                                        {{ template.language === 'bn' ? 'বাংলা' : 'English' }}
                                    </span>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-icon btn-sm" @click="editTemplate(template)" title="এডিট">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                    <button class="btn btn-icon btn-sm" @click="deleteTemplate(template.id)" title="ডিলিট">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="templates.length === 0" class="p-4 text-center text-muted italic">
                            কোন টেম্পলেট পাওয়া যায়নি
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import KeywordSelector from './KeywordSelector.vue';

const props = defineProps({
    schoolId: {
        type: Number,
        required: true
    }
});

const templates = ref([]);
const editingTemplate = ref(null);
const loading = ref(false);

const form = ref({
    id: null,
    type: 'prottayon',
    language: 'bn',
    name: '',
    content: '',
    is_active: true
});

const fetchTemplates = async () => {
    try {
        const response = await axios.get(`/principal/institute/${props.schoolId}/documents/settings/templates`);
        templates.value = response.data;
    } catch (error) {
        console.error('Error fetching templates:', error);
        toastr.error('টেম্পলেট লোড করতে সমস্যা হয়েছে');
    }
};

const saveTemplate = async () => {
    loading.value = true;
    try {
        await axios.post(`/principal/institute/${props.schoolId}/documents/settings/templates`, form.value);
        toastr.success('টেম্পলেট সফলভাবে সংরক্ষণ করা হয়েছে');
        resetForm();
        fetchTemplates();
    } catch (error) {
        console.error('Error saving template:', error);
        toastr.error('টেম্পলেট সংরক্ষণ করতে সমস্যা হয়েছে');
    } finally {
        loading.value = false;
    }
};

const deleteTemplate = async (id) => {
    if (!confirm('আপনি কি নিশ্চিত যে এই টেম্পলেটটি মুছতে চান?')) return;
    
    try {
        await axios.delete(`/principal/institute/${props.schoolId}/documents/settings/templates/${id}`);
        toastr.success('টেম্পলেট সফলভাবে মুছে ফেলা হয়েছে');
        fetchTemplates();
    } catch (error) {
        console.error('Error deleting template:', error);
        toastr.error('টেম্পলেট মুছতে সমস্যা হয়েছে');
    }
};

const editTemplate = (template) => {
    editingTemplate.value = template;
    form.value = { ...template, is_active: !!template.is_active };
};

const resetForm = () => {
    editingTemplate.value = null;
    form.value = {
        id: null,
        type: 'prottayon',
        language: 'bn',
        name: '',
        content: '',
        is_active: true
    };
};

const insertKeyword = (keyword) => {
    const textarea = document.getElementById('template_body');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = form.value.content;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);

    form.value.content = before + keyword + after;
    
    // Focus back and set cursor position after keyword
    setTimeout(() => {
        textarea.focus();
        const newCursorPos = start + keyword.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
};

onMounted(fetchTemplates);
</script>

<style scoped>
.premium-title { font-weight: 800; color: #2d3436; font-size: 1.7rem; letter-spacing: -0.5px; }
.premium-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: all 0.3s; background: #fff; }
.card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 1.25rem 1.5rem; }
.section-label { font-size: 0.8rem; font-weight: 700; color: #636e72; text-transform: uppercase; margin-bottom: 0.6rem; display: block; letter-spacing: 0.5px; }

.modern-input { border-radius: 12px; border: 1px solid #dfe6e9; padding: 10px 15px; transition: all 0.2s; background: #fcfcfc; height: auto; min-height: 45px; }
select.modern-input { padding-top: 0; padding-bottom: 0; height: 45px; }
.modern-input:focus { border-color: #0984e3; background: #fff; box-shadow: 0 0 0 4px rgba(9, 132, 227, 0.1); outline: none; }

.premium-switch .custom-control-label::before { height: 1.5rem; width: 2.75rem; border-radius: 1rem; }
.premium-switch .custom-control-label::after { width: calc(1.5rem - 4px); height: calc(1.5rem - 4px); border-radius: 50%; }
.premium-switch .custom-control-input:checked ~ .custom-control-label::after { transform: translateX(1.25rem); }

.premium-shadow { box-shadow: 0 4px 15px rgba(9, 132, 227, 0.2); }
.bg-light-blue { background: #f0f7ff; }

/* Badges */
.badge-soft-primary { background: #e0eeff; color: #0984e3; }
.badge-soft-warning { background: #fff8e1; color: #f39c12; }
.badge-soft-info { background: #e0f7fa; color: #00bcd4; }

.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.btn-icon { background: #f8f9fa; border-radius: 8px; margin-left: 5px; }
.btn-icon:hover { background: #e9ecef; }

.xsmall { font-size: 0.65rem; }
.italic { font-style: italic; }
</style>
