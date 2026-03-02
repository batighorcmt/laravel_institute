<template>
    <div class="row mt-4">
        <div class="col-md-12">
            <h4 class="mb-3">প্রিন্ট সেটিংস (ব্যাকগ্রাউন্ড, কালার ও মেমো ফরম্যাট)</h4>
        </div>
        <div v-for="pageKey in pages" :key="pageKey" class="col-md-4">
            <div class="card card-secondary card-outline mb-3">
                <div class="card-header">
                    <strong class="card-title">{{ pageKey.toUpperCase() }} Print Setup</strong>
                </div>
                <form @submit.prevent="saveSettings(pageKey)">
                    <div class="card-body">
                        <!-- Background -->
                        <div class="form-group">
                            <label>Background Image</label>
                            <div v-if="getSetting(pageKey).background_path" class="mb-2">
                                <img :src="'/storage/' + getSetting(pageKey).background_path" class="img-thumbnail" style="max-height: 80px">
                            </div>
                            <input type="file" @change="onFileChange($event, pageKey)" class="form-control-file" accept="image/*">
                        </div>

                        <!-- Colors -->
                        <div class="form-row">
                            <div class="form-group col">
                                <label class="small">Title Color</label>
                                <input type="color" v-model="getSetting(pageKey).colors.title" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col">
                                <label class="small">Body Color</label>
                                <input type="color" v-model="getSetting(pageKey).colors.body" class="form-control form-control-sm">
                            </div>
                        </div>

                        <!-- Memo Format (Keywords) -->
                        <div class="form-group">
                            <label class="small">Memo Number Format (Drag/Check to reorder)</label>
                            <div class="border p-2 bg-light rounded" style="min-height: 100px;">
                                <div v-for="(keyword, index) in availableKeywords[pageKey]" :key="keyword" class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" :id="'chk-' + pageKey + '-' + keyword" 
                                           v-model="getSetting(pageKey).memo_format" :value="keyword" class="custom-control-input">
                                    <label class="custom-control-label" :for="'chk-' + pageKey + '-' + keyword">
                                        {{ formatKeyword(keyword) }}
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted">চেক করা কিওয়ার্ডগুলো দিয়ে মেমো নম্বর তৈরি হবে।</small>
                        </div>
                        
                        <!-- Custom Text with Keywords -->
                        <div class="form-group">
                            <label class="small d-flex justify-content-between">
                                Custom Text 
                                <span class="text-info cursor-pointer" @click="showKeywords = !showKeywords">
                                    <i class="fas fa-keyboard"></i> কি-ওয়ার্ড
                                </span>
                            </label>
                            <div v-if="showKeywords" class="mb-2 p-1 border rounded bg-white">
                                <button v-for="kw in ['[year]', '[class]', '[section]', '[type]', '[serial]']" 
                                        :key="kw" type="button" 
                                        class="btn btn-xs btn-outline-info mr-1 mb-1"
                                        @click="insertKeyword(pageKey, kw)">
                                    {{ kw }}
                                </button>
                            </div>
                            <textarea :id="'custom_text_' + pageKey" 
                                      v-model="getSetting(pageKey).custom_text_raw" 
                                      class="form-control form-control-sm" 
                                      rows="2" 
                                      placeholder="Text1, Text2 or use keywords"></textarea>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="loading[pageKey]">
                            <i class="fas" :class="loading[pageKey] ? 'fa-spinner fa-spin' : 'fa-save'"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    schoolId: { type: Number, required: true },
    pages: { type: Array, default: () => ['prottayon', 'certificate', 'testimonial'] },
    initialSettings: { type: Object, default: () => ({}) }
});

const settings = ref({});
const files = ref({});
const loading = ref({});
const showKeywords = ref(false);

const availableKeywords = ref({});
props.pages.forEach(p => {
    availableKeywords.value[p] = ['institution_code', 'custom_text', 'academic_year', 'serial_no', 'class', 'type'];
});

const getSetting = (page) => {
    if (!settings.value[page]) {
        const initial = props.initialSettings[page] || {};
        settings.value[page] = {
            colors: initial.colors || { title: '#000000', body: '#333333' },
            memo_format: initial.memo_format || ['institution_code', 'serial_no'],
            custom_text_raw: Array.isArray(initial.custom_text) ? initial.custom_text.join(',') : (initial.custom_text || ''),
            background_path: initial.background_path || null
        };
    }
    return settings.value[page];
};

const formatKeyword = (kw) => {
    return kw.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const onFileChange = (e, page) => {
    files.value[page] = e.target.files[0];
};

const insertKeyword = (page, kw) => {
    const el = document.getElementById('custom_text_' + page);
    if (!el) return;
    
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const text = settings.value[page].custom_text_raw;
    settings.value[page].custom_text_raw = text.substring(0, start) + kw + text.substring(end);
    
    setTimeout(() => {
        el.focus();
        el.setSelectionRange(start + kw.length, start + kw.length);
    }, 0);
};

const saveSettings = async (page) => {
    loading.value[page] = true;
    try {
        const formData = new FormData();
        formData.append('page', page);
        if (files.value[page]) {
            formData.append('background', files.value[page]);
        }
        
        const s = settings.value[page];
        formData.append('colors[title]', s.colors.title);
        formData.append('colors[body]', s.colors.body);
        
        s.memo_format.forEach(m => formData.append('memo_format[]', m));
        formData.append('custom_text', s.custom_text_raw);

        await axios.post(`/principal/institute/${props.schoolId}/documents/settings`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        toastr.success(page.toUpperCase() + ' settings saved successfully');
    } catch (error) {
        console.error(error);
        toastr.error('Failed to save settings');
    } finally {
        loading.value[page] = false;
    }
};

onMounted(() => {
    props.pages.forEach(p => {
        loading.value[p] = false;
        getSetting(p);
    });
});
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.btn-xs { padding: 1px 5px; font-size: 10px; }
</style>
