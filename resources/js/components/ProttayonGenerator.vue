<template>
    <div class="row">
        <div class="col-md-7">
            <div class="card card-primary card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">প্রত্যয়নপত্র তৈরি (Enhanced)</h3>
                    <div class="card-tools">
                        <a :href="'/principal/institute/' + schoolId + '/documents/prottayon/history'" class="btn btn-sm btn-outline-secondary mr-2">
                            <i class="fas fa-list"></i> ইতিহাস (History)
                        </a>
                        <button v-if="selectedStudent" type="button" class="btn btn-sm btn-outline-primary" @click="showEditModal = true">
                            <i class="fas fa-user-edit"></i> শিক্ষার্থীর তথ্য এডিট
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="generateDocument">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>শ্রেণি</label>
                                <select v-model="form.class_id" class="form-control" @change="fetchStudents" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name || 'Class ' + c.numeric_value }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>শাখা (ঐচ্ছিক)</label>
                                <select v-model="form.section_id" class="form-control" @change="fetchStudents">
                                    <option value="">-- সকল শাখা --</option>
                                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>শিক্ষার্থী</label>
                            <select v-model="form.student_id" class="form-control" @change="onStudentChange" required>
                                <option value="">-- নির্বাচন করুন --</option>
                                <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                                    {{ s.name }} (রোল: {{ s.roll_no || '-' }}, আইডি: {{ s.student_id }})
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>প্রত্যয়নের ধরন</label>
                                <select v-model="form.attestation_type" class="form-control" required>
                                    <option value="study">অধ্যয়নরত</option>
                                    <option value="character">চরিত্রগত</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>প্রিন্ট লেআউট</label>
                                <select v-model="form.layout" class="form-control" required>
                                    <option value="standard">Standard (হেডার সহ)</option>
                                    <option value="pad">Pad/Letterhead (হেডার ছাড়া)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>টেম্পলেট নির্বাচন করুন</label>
                            <select v-model="selectedTemplateId" class="form-control" @change="onTemplateChange">
                                <option value="">-- সরাসরি লিখুন / কাস্টম --</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div class="form-group position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label>প্রত্যয়ন বডি</label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn" :class="!editMode ? 'btn-primary' : 'btn-default'" @click="editMode = false">
                                        <i class="fas fa-eye"></i> প্রিভিউ
                                    </button>
                                    <button type="button" class="btn" :class="editMode ? 'btn-warning' : 'btn-default'" @click="editMode = true">
                                        <i class="fas fa-edit"></i> ইডিটিং মুড
                                    </button>
                                </div>
                            </div>

                            <div v-if="!editMode" class="preview-area border p-3 bg-white rounded" style="min-height: 250px; white-space: pre-wrap;">
                                <div v-if="!parsedContent" class="text-muted text-center mt-5">শিক্ষার্থী ও টেম্পলেট নির্বাচন করলে এখানে প্রিভিউ দেখা যাবে</div>
                                <div v-else v-html="formattedPreview"></div>
                            </div>
                            
                            <textarea v-else v-model="form.content" class="form-control" rows="12" placeholder="এখানে প্রত্যয়নের মূল বক্তব্য লিখুন..."></textarea>
                            
                            <small v-if="editMode" class="text-danger">* ইডিটিং মুডে আপনি সরাসরি টেক্সট পরিবর্তন করতে পারছেন। এটিই প্রিন্টে আসবে।</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg btn-block" :disabled="loading || !form.student_id">
                                <i class="fas fa-print"></i> জেনারেট ও প্রিন্ট করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <KeywordSelector @select="insertKeywordToEditor" />
            
            <div class="alert alert-info mt-3 small">
                <h5><i class="icon fas fa-info"></i> নির্দেশিকা</h5>
                <ul>
                    <li>শিক্ষার্থীর কোনো তথ্যে ভুল থাকলে উপরে <b>'শিক্ষার্থীর তথ্য এডিট'</b> বাটনে ক্লিক করে ঠিক করে নিন।</li>
                    <li>প্রিভিউতে সব ঠিক থাকলে জেনারেট করুন।</li>
                    <li>যদি জেনারেট করা ডকুমেস্টে সরাসরি কিছু পরিবর্তন করতে চান তবে <b>'ইডিটিং মুড'</b> ব্যবহার করুন।</li>
                </ul>
            </div>
        </div>

        <!-- Student Edit Modal -->
        <div v-if="showEditModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">শিক্ষার্থীর তথ্য সংশোধন</h5>
                        <button type="button" class="close" @click="showEditModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row" v-if="tempStudent">
                            <div class="form-group col-md-6">
                                <label>নাম (বাংলা)</label>
                                <input type="text" v-model="tempStudent.student_name_bn" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>নাম (ইংরেজি)</label>
                                <input type="text" v-model="tempStudent.student_name_en" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>পিতার নাম (বাংলা)</label>
                                <input type="text" v-model="tempStudent.father_name_bn" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>পিতার নাম (ইংরেজি)</label>
                                <input type="text" v-model="tempStudent.father_name" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>মাতার নাম (বাংলা)</label>
                                <input type="text" v-model="tempStudent.mother_name_bn" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>মাতার নাম (ইংরেজি)</label>
                                <input type="text" v-model="tempStudent.mother_name" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>জন্ম তারিখ</label>
                                <input type="date" v-model="tempStudent.date_of_birth" class="form-control">
                            </div>
                            <div class="form-group col-md-3">
                                <label>গ্রাম</label>
                                <input type="text" v-model="tempStudent.present_village" class="form-control">
                            </div>
                            <div class="form-group col-md-3">
                                <label>ডাকঘর</label>
                                <input type="text" v-model="tempStudent.present_post_office" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showEditModal = false">বাতিল</button>
                        <button type="button" class="btn btn-primary" @click="applyStudentEdits">পরিবর্তনগুলো প্রয়োগ করুন</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import KeywordSelector from './KeywordSelector.vue';

const props = defineProps({
    schoolId: { type: Number, required: true },
    initialClasses: { type: Array, default: () => [] }
});

const classes = ref(props.initialClasses);
const sections = ref([]);
const students = ref([]);
const templates = ref([]);
const selectedTemplateId = ref('');
const selectedStudent = ref(null);
const tempStudent = ref(null);
const showEditModal = ref(false);
const editMode = ref(false);
const loading = ref(false);

const form = ref({
    class_id: '',
    section_id: '',
    student_id: '',
    attestation_type: 'study',
    layout: 'standard',
    template_id: '',
    content: ''
});

const fetchStudents = async () => {
    if (!form.value.class_id) return;
    try {
        const secRes = await axios.get(`/principal/institute/${props.schoolId}/meta/sections?class_id=${form.value.class_id}`);
        sections.value = secRes.data;
    } catch (e) {}

    try {
        let url = `/principal/institute/${props.schoolId}/meta/students?class_id=${form.value.class_id}`;
        if (form.value.section_id) url += `&section_id=${form.value.section_id}`;
        const stuRes = await axios.get(url);
        students.value = stuRes.data;
    } catch (e) {}
};

const fetchTemplates = async () => {
    try {
        const res = await axios.get(`/principal/institute/${props.schoolId}/documents/settings/templates`);
        templates.value = res.data.filter(t => t.type === 'prottayon' && t.is_active);
    } catch (e) {}
};

const onStudentChange = () => {
    const s = students.value.find(s => s.student_id == form.value.student_id);
    selectedStudent.value = s ? { ...s } : null;
    tempStudent.value = s ? { ...s } : null;
};

const applyStudentEdits = () => {
    selectedStudent.value = { ...tempStudent.value };
    showEditModal.value = false;
    toastr.info('শিক্ষার্থীর তথ্য সেশনে আপডেট করা হয়েছে (ডাটাবেজে স্থায়ী নয়)');
};

const onTemplateChange = () => {
    const template = templates.value.find(t => t.id == selectedTemplateId.value);
    if (template) {
        form.value.template_id = template.id;
        form.value.content = template.content;
    } else {
        form.value.template_id = '';
        form.value.content = '';
    }
};

const insertKeywordToEditor = (keyword) => {
    if (!editMode.value) {
        editMode.value = true;
    }
    const text = form.value.content;
    form.value.content += ' ' + keyword;
};

const parsedContent = computed(() => {
    if (!form.value.content) return '';
    if (!selectedStudent.value) return form.value.content;

    let content = form.value.content;
    const s = selectedStudent.value;
    
    // Map all likely tokens
    const tokens = {
        '[student_name_bn]': s.student_name_bn || s.name || '',
        '[student_name_en]': s.student_name_en || '',
        '[father_name_bn]': s.father_name_bn || '',
        '[father_name_en]': s.father_name || '',
        '[mother_name_bn]': s.mother_name_bn || '',
        '[mother_name_en]': s.mother_name || '',
        '[student_id]': s.student_id || '',
        '[roll_no]': s.roll_no || '',
        '[date_of_birth]': s.date_of_birth ? new Date(s.date_of_birth).toLocaleDateString('bn-BD') : '',
        '[gender]': s.gender == 'male' ? 'ছাত্র' : 'ছাত্রী',
        '[blood_group]': s.blood_group || '',
        '[present_village]': s.present_village || '',
        '[present_post_office]': s.present_post_office || '',
        '[present_upazilla]': s.present_upazilla || '',
        '[present_district]': s.present_district || '',
        '[permanent_village]': s.permanent_village || '',
        '[permanent_post_office]': s.permanent_post_office || '',
        '[permanent_upazilla]': s.permanent_upazilla || '',
        '[permanent_district]': s.permanent_district || '',
        '[class_name]': s.class_name || '',
        '[section_name]': s.section_name || '',
        '[session]': s.academic_year || '',
        '[date]': new Date().toLocaleDateString('bn-BD'),
        '[school_name]': 'আপনার বিদ্যালয়'
    };

    Object.keys(tokens).forEach(key => {
        const val = tokens[key] || '';
        content = content.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
    });

    return content;
});

const formattedPreview = computed(() => {
    return parsedContent.value.replace(/\n/g, '<br>');
});

const generateDocument = async () => {
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            content: editMode.value ? form.value.content : parsedContent.value,
            is_final: true,
            updated_student_data: selectedStudent.value // Send modified student info
        };

        const res = await axios.post(`/principal/institute/${props.schoolId}/documents/prottayon/generate`, payload);
        if (res.data.redirect) {
            window.location.href = res.data.redirect + '?layout=' + form.value.layout;
        }
    } catch (error) {
        console.error(error);
        toastr.error('প্রত্যয়নপত্র জেনারেট করতে সমস্যা হয়েছে');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchTemplates);
</script>

<style scoped>
.preview-area {
    line-height: 1.8;
    font-size: 1.15rem;
    color: #333;
    border: 1px solid #ddd;
}
.cursor-pointer { cursor: pointer; }
</style>
