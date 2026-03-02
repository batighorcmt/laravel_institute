<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">প্রত্যয়ন টেম্পলেট সেটআপ</h3>
                    <div class="card-tools">
                        <button class="btn btn-success btn-sm" @click="resetForm" v-if="editingTemplate">
                            <i class="fas fa-plus"></i> নতুন টেম্পলেট
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="saveTemplate">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">প্রত্যয়ন এর ধরন</label>
                            <div class="col-sm-9">
                                <select v-model="form.type" class="form-control" required>
                                    <option value="prottayon">প্রত্যয়নপত্র (Prottayon)</option>
                                    <option value="certificate">সার্টিফিকেট (Certificate)</option>
                                    <option value="testimonial">প্রশংসাপত্র (Testimonial)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">টেম্পলেট এর নাম</label>
                            <div class="col-sm-9">
                                <input type="text" v-model="form.name" class="form-control" placeholder="যেমন: সাধারণ প্রত্যয়নপত্র" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">টেম্পলেট বডি</label>
                            <div class="col-sm-9">
                                <textarea id="template_body" v-model="form.content" class="form-control" rows="10" placeholder="এখানে টেম্পলেট লিখুন এবং ডানদিকের কিওয়ার্ড ব্যবহার করুন..." required></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">স্ট্যাটাস</label>
                            <div class="col-sm-9">
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" v-model="form.is_active" class="custom-control-input" id="statusSwitch">
                                    <label class="custom-control-label" for="statusSwitch">{{ form.is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="offset-sm-3 col-sm-9">
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                    {{ editingTemplate ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}
                                </button>
                                <button type="button" class="btn btn-default ml-2" @click="resetForm" v-if="editingTemplate">বাতিল</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <KeywordSelector :type="form.type" @select="insertKeyword" />

            <div class="card card-outline card-secondary mt-3">
                <div class="card-header">
                    <h3 class="card-title">সংরক্ষিত টেম্পলেটসমূহ</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>নাম</th>
                                <th>ধরণ</th>
                                <th style="width: 80px">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="template in templates" :key="template.id">
                                <td>
                                    {{ template.name }}
                                    <span v-if="!template.is_active" class="badge badge-danger">নিষ্ক্রিয়</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ template.type }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary" @click="editTemplate(template)" title="এডিট">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" @click="deleteTemplate(template.id)" title="ডিলিট">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="templates.length === 0">
                                <td colspan="3" class="text-center">কোন টেম্পলেট নেই</td>
                            </tr>
                        </tbody>
                    </table>
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
