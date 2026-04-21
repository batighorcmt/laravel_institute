<template>
    <div class="row">
        <div class="col-md-7">
            <div class="card card-primary card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ isEdit ? 'প্রত্যয়নপত্র সংশোধন' : 'প্রত্যয়নপত্র তৈরি' }}</h3>
                    <div class="card-tools">
                        <a :href="'/principal/institute/' + schoolId + '/documents/prottayon/history'" class="btn btn-sm btn-outline-secondary mr-2">
                            <i class="fas fa-list"></i> ইতিহাস (History)
                        </a>
                        <button v-if="selectedStudent && !isEdit" type="button" class="btn btn-sm btn-outline-primary" @click="showEditModal = true">
                            <i class="fas fa-user-edit"></i> শিক্ষার্থীর তথ্য এডিট
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Student Info Summary in Edit Mode -->
                    <div v-if="isEdit && selectedStudent" class="alert alert-light border mb-4">
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <strong>শিক্ষার্থী:</strong> {{ selectedStudent.student_name_bn || selectedStudent.name }}<br>
                                <strong>শ্রেণি:</strong> {{ selectedStudent.class_name_bn || selectedStudent.class_name }}
                            </div>
                            <div class="col-md-6 pl-md-4">
                                <strong>রোল:</strong> {{ toBengaliNumber(selectedStudent.roll_no) }}<br>
                                <strong>ধরন:</strong> {{ form.attestation_type === 'study' ? 'অধ্যয়নরত' : 'চারিত্রিক' }}
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm">
                        <div v-if="!isEdit" class="form-row">
                            <div class="form-group col-md-6">
                                <label>শ্রেণি</label>
                                <select v-model="form.class_id" class="form-control select2" data-model="class_id" @change="fetchStudents" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name || 'Class ' + c.numeric_value }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>শাখা (ঐচ্ছিক)</label>
                                <select v-model="form.section_id" class="form-control select2" data-model="section_id" @change="fetchStudents">
                                    <option value="">-- সকল শাখা --</option>
                                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="!isEdit" class="form-group">
                            <label>শিক্ষার্থী</label>
                            <select v-model="form.student_id" class="form-control select2" data-model="student_id" @change="onStudentChange" required>
                                <option value="">-- নির্বাচন করুন --</option>
                                <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                                    ({{ toBengaliNumber(s.roll_no) || '-' }} - {{ s.name }})
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div v-if="!isEdit" class="form-group col-md-6">
                                <label>প্রত্যয়নের ধরন</label>
                                <select v-model="form.attestation_type" class="form-control select2" data-model="attestation_type" required>
                                    <option value="study">অধ্যয়নরত</option>
                                    <option value="character">চরিত্রগত</option>
                                </select>
                            </div>
                            <div :class="isEdit ? 'col-md-12' : 'col-md-6'" class="form-group">
                                <label>প্রিন্ট লেআউট</label>
                                <select v-model="form.layout" class="form-control select2" data-model="layout" required>
                                    <option value="standard">Standard (হেডার সহ)</option>
                                    <option value="pad">Pad/Letterhead (হেডার ছাড়া)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>টেম্পলেট নির্বাচন করুন</label>
                            <select v-model="selectedTemplateId" class="form-control select2" data-model="template_id" @change="onTemplateChange">
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
                                <i class="fas" :class="isEdit ? 'fa-save' : 'fa-print'"></i> 
                                {{ isEdit ? 'তথ্য আপডেট করুন' : 'জেনারেট ও প্রিন্ট করুন' }}
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
                    <li v-if="!isEdit">শিক্ষার্থীর কোনো তথ্যে ভুল থাকলে উপরে <b>'শিক্ষার্থীর তথ্য এডিট'</b> বাটনে ক্লিক করে ঠিক করে নিন।</li>
                    <li>প্রিভিউতে সব ঠিক থাকলে তথ্য সেভ বা জেনারেট করুন।</li>
                    <li>যদি জেনারেট করা ডকুমেস্টে সরাসরি কিছু পরিবর্তন করতে চান তবে <b>'ইডিটিং মুড'</b> ব্যবহার করুন।</li>
                </ul>
            </div>
        </div>

        <!-- Student Edit Modal (Only for Generation Mode) -->
        <div v-if="showEditModal && !isEdit" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5)">
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
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import KeywordSelector from './KeywordSelector.vue';

const props = defineProps({
    schoolId: { type: Number, required: true },
    schoolNameBn: { type: String, default: '' },
    schoolNameEn: { type: String, default: '' },
    initialClasses: { type: Array, default: () => [] },
    initialDocument: { type: Object, default: null } // New prop for editing
});

const isEdit = computed(() => !!props.initialDocument);
const classes = ref(props.initialClasses);
const sections = ref([]);
const students = ref([]);
const templates = ref([]);
const selectedTemplateId = ref('');
const selectedTemplate = ref(null);
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

const toBengaliNumber = (num) => {
    if (!num && num !== 0) return '';
    const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return num.toString().replace(/\d/g, d => bn[d]);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

const initSelect2 = () => {
    if (!window.$ || !$.fn.select2) return;
    
    $('.select2').each(function() {
        const $el = $(this);
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
        
        $el.select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: '-- নির্বাচন করুন --',
            allowClear: true
        });

        // Sync Select2 value to Vue on change
        $el.off('change.select2-sync').on('change.select2-sync', function() {
            const val = $(this).val();
            const model = $el.attr('data-model');
            
            if (model === 'class_id') {
                form.value.class_id = val;
                fetchStudents();
            } else if (model === 'section_id') {
                form.value.section_id = val;
                fetchStudents();
            } else if (model === 'student_id') {
                form.value.student_id = val;
                onStudentChange();
            } else if (model === 'attestation_type') {
                form.value.attestation_type = val;
            } else if (model === 'layout') {
                form.value.layout = val;
            } else if (model === 'template_id') {
                selectedTemplateId.value = val;
                onTemplateChange();
            }
        });
    });
};

// Refresh Select2 when options change
watch([classes, sections, students, templates], () => {
    nextTick(() => {
        initSelect2();
    });
}, { deep: true });

// Sync Vue value to Select2 if changed programmatically
watch(() => form.value.class_id, (newVal) => {
    const $el = $('[data-model="class_id"]');
    if ($el.val() !== newVal) $el.val(newVal).trigger('change.select2');
});
watch(() => form.value.section_id, (newVal) => {
    const $el = $('[data-model="section_id"]');
    if ($el.val() !== newVal) $el.val(newVal).trigger('change.select2');
});
watch(() => form.value.student_id, (newVal) => {
    const $el = $('[data-model="student_id"]');
    if ($el.val() !== newVal) $el.val(newVal).trigger('change.select2');
});
watch(() => selectedTemplateId.value, (newVal) => {
    const $el = $('[data-model="template_id"]');
    if ($el.val() !== newVal) $el.val(newVal).trigger('change.select2');
});

const fetchStudents = async () => {
    if (!form.value.class_id) {
        sections.value = [];
        students.value = [];
        return;
    }
    try {
        const secRes = await axios.get(`/principal/institute/${props.schoolId}/meta/sections?class_id=${form.value.class_id}`);
        sections.value = secRes.data;
    } catch (e) {}

    try {
        let url = `/principal/institute/${props.schoolId}/meta/students?class_id=${form.value.class_id}`;
        if (form.value.section_id) url += `&section_id=${form.value.section_id}`;
        const stuRes = await axios.get(url);
        students.value = stuRes.data;
        
        // If in edit mode, ensure the initial student is found in the list to trigger onStudentChange
        if (isEdit.value && form.value.student_id) {
            nextTick(() => onStudentChange());
        }
    } catch (e) {}
};

const fetchTemplates = async () => {
    try {
        const res = await axios.get(`/principal/institute/${props.schoolId}/documents/settings/templates`);
        templates.value = res.data.filter(t => t.type === 'prottayon' && t.is_active);
        
        // After templates loaded, if editing, we might need to set the selected template
        if (isEdit.value && form.value.template_id) {
            selectedTemplateId.value = form.value.template_id;
            const t = templates.value.find(x => x.id == selectedTemplateId.value);
            if (t) selectedTemplate.value = t;
        }
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
    selectedTemplate.value = template;
    if (template) {
        form.value.template_id = template.id;
        form.value.content = template.content;
    } else {
        form.value.template_id = '';
        form.value.content = '';
    }
    // Automatically switch to Preview mode when template changes to see results
    editMode.value = false;
};

const insertKeywordToEditor = (keyword) => {
    if (!editMode.value) {
        editMode.value = true;
    }
    form.value.content += ' ' + keyword;
};

const parsedContent = computed(() => {
    if (!form.value.content) return '';
    if (!selectedStudent.value) return form.value.content;

    let content = form.value.content;
    const s = selectedStudent.value;
    
    const tokens = {
        '[student_name_bn]': s.student_name_bn || s.name || '',
        '[student_name_en]': s.student_name_en || '',
        '[father_name_bn]': s.father_name_bn || '',
        '[father_name_en]': s.father_name || '',
        '[mother_name_bn]': s.mother_name_bn || '',
        '[mother_name_en]': s.mother_name || '',
        '[student_id]': s.student_id || '',
        '[roll_no]': s.roll_no || '',
        '[date_of_birth]': formatDate(s.date_of_birth),
        '[date_of_birth_bn]': formatDate(s.date_of_birth),
        '[date_of_birth_en]': formatDate(s.date_of_birth),
        '[gender]': s.gender == 'male' ? 'ছাত্র' : 'ছাত্রী',
        '[blood_group]': s.blood_group || '',
        '[guardian_phone]': s.guardian_phone || '',
        '[present_village_bn]': s.present_village || '',
        '[present_village_en]': s.present_village_en || '',
        '[present_post_office_bn]': s.present_post_office || '',
        '[present_post_office_en]': s.present_post_office_en || '',
        '[present_upazilla_bn]': s.present_upazilla || '',
        '[present_upazilla_en]': s.present_upazilla_en || '',
        '[present_district_bn]': s.present_district || '',
        '[present_district_en]': s.present_district_en || '',
        '[permanent_village_bn]': s.permanent_village || '',
        '[permanent_village_en]': s.permanent_village_en || '',
        '[permanent_post_office_bn]': s.permanent_post_office || '',
        '[permanent_post_office_en]': s.permanent_post_office_en || '',
        '[permanent_upazilla_bn]': s.permanent_upazilla || '',
        '[permanent_upazilla_en]': s.permanent_upazilla_en || '',
        '[permanent_district_bn]': s.permanent_district || '',
        '[permanent_district_en]': s.permanent_district_en || '',
        '[present_village]': selectedTemplate.value?.language === 'en' ? (s.present_village_en || '') : (s.present_village || ''),
        '[present_post_office]': selectedTemplate.value?.language === 'en' ? (s.present_post_office_en || '') : (s.present_post_office || ''),
        '[present_upazilla]': selectedTemplate.value?.language === 'en' ? (s.present_upazilla_en || '') : (s.present_upazilla || ''),
        '[present_district]': selectedTemplate.value?.language === 'en' ? (s.present_district_en || '') : (s.present_district || ''),
        '[permanent_village]': selectedTemplate.value?.language === 'en' ? (s.permanent_village_en || '') : (s.permanent_village || ''),
        '[permanent_post_office]': selectedTemplate.value?.language === 'en' ? (s.permanent_post_office_en || '') : (s.permanent_post_office || ''),
        '[permanent_upazilla]': selectedTemplate.value?.language === 'en' ? (s.permanent_upazilla_en || '') : (s.permanent_upazilla || ''),
        '[permanent_district]': selectedTemplate.value?.language === 'en' ? (s.permanent_district_en || '') : (s.permanent_district || ''),
        '[class_name_bn]': s.class_name_bn || s.class_name || '',
        '[class_name_en]': s.class_name || '',
        '[section_name_bn]': s.section_name_bn || s.section_name || '',
        '[section_name_en]': s.section_name || '',
        '[session_bn]': s.academic_year_bn || s.academic_year || '',
        '[session_en]': s.academic_year || '',
        '[session]': selectedTemplate.value?.language === 'en' ? (s.academic_year || '') : (s.academic_year_bn || s.academic_year || ''),
        '[date]': formatDate(new Date()),
        '[school_name]': selectedTemplate.value?.language === 'en' ? props.schoolNameEn : props.schoolNameBn,
        '[school_name_bn]': props.schoolNameBn,
        '[school_name_en]': props.schoolNameEn,
        '[roll_no_bn]': toBengaliNumber(s.roll_no),
        '[roll_no_en]': s.roll_no,
        '[student_name]': selectedTemplate.value?.language === 'en' ? (s.student_name_en || s.name) : (s.student_name_bn || s.name),
        '[father_name]': selectedTemplate.value?.language === 'en' ? (s.father_name || '') : (s.father_name_bn || ''),
        '[mother_name]': selectedTemplate.value?.language === 'en' ? (s.mother_name || '') : (s.mother_name_bn || ''),
    };

    const isEn = selectedTemplate.value?.language === 'en';
    if (isEn) {
        tokens['[roll_no]'] = s.roll_no || '';
        tokens['[class_name]'] = s.class_name || '';
        tokens['[section_name]'] = s.section_name || '';
    } else {
        tokens['[roll_no]'] = toBengaliNumber(s.roll_no);
        tokens['[class_name]'] = s.class_name_bn || s.class_name || '';
        tokens['[section_name]'] = s.section_name_bn || s.section_name || '';
    }

    Object.keys(tokens).forEach(key => {
        const val = tokens[key] || '';
        content = content.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
    });

    return content;
});

const formattedPreview = computed(() => {
    return parsedContent.value.replace(/\n/g, '<br>');
});

const submitForm = async () => {
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            content: editMode.value ? form.value.content : parsedContent.value,
            is_final: true,
            updated_student_data: selectedStudent.value
        };

        let res;
        if (isEdit.value) {
            // Update mode
            res = await axios.put(`/principal/institute/${props.schoolId}/documents/prottayon/${props.initialDocument.id}`, payload);
            toastr.success('প্রত্যয়নপত্র সফলভাবে আপডেট করা হয়েছে');
            if (res.data.redirect) {
                window.location.href = res.data.redirect + '?layout=' + form.value.layout;
            }
        } else {
            // Generate mode
            res = await axios.post(`/principal/institute/${props.schoolId}/documents/prottayon/generate`, payload);
            if (res.data.redirect) {
                window.location.href = res.data.redirect + '?layout=' + form.value.layout;
            }
        }
    } catch (error) {
        console.error(error);
        toastr.error('প্রত্যয়নপত্র প্রসেস করতে সমস্যা হয়েছে');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (isEdit.value) {
        const doc = props.initialDocument;
        form.value = {
            class_id: doc.data?.class_id || '',
            section_id: doc.data?.section_id || '',
            student_id: doc.student_id || '',
            attestation_type: doc.data?.attestation_type || 'study',
            layout: doc.data?.layout || 'standard',
            template_id: doc.data?.template_id || '',
            content: doc.data?.custom_content || doc.data?.content || ''
        };
        // In Edit mode, we don't start in editMode (textarea) but in Preview mode
        editMode.value = true; // Use existing custom content
        
        fetchStudents();
    }
    
    fetchTemplates();
    
    nextTick(() => {
        initSelect2();
    });
});
</script>

<style scoped>
.preview-area {
    line-height: 1.8;
    font-size: 1.15rem;
    color: #333;
    border: 1px solid #ddd;
}
.cursor-pointer { cursor: pointer; }

:deep(.select2-container--bootstrap4 .select2-selection) {
    border: 1px solid #ced4da !important;
    border-radius: 8px !important;
    min-height: 38px !important;
    display: flex !important;
    align-items: center !important;
}

:deep(.select2-container--bootstrap4.select2-container--focus .select2-selection) {
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
}
</style>
