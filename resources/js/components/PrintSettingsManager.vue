<template>
    <div class="row mt-4">
        <div class="col-md-12">
            <h3 class="premium-title mb-4">
                <i class="fas fa-sliders-h mr-2"></i>প্রিন্ট কনফিগারেশন ও সেটিংস
            </h3>
        </div>
        
        <div v-for="pageKey in pages" :key="pageKey" class="col-lg-6 col-xl-4">
            <div class="card premium-card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 text-capitalize">
                        <i class="fas fa-file-alt mr-2 text-primary"></i>{{ pageKey }} Setup
                    </h5>
                    <span class="badge badge-pill badge-light border">{{ pageKey.toUpperCase() }}</span>
                </div>
                
                <form @submit.prevent="saveSettings(pageKey)">
                    <div class="card-body p-4">
                        <!-- Background Image -->
                        <div class="setting-section mb-4">
                            <label class="section-label">Background / Watermark</label>
                            <div class="background-preview-container d-flex align-items-center p-3 mb-2 rounded border">
                                <div class="preview-box mr-3">
                                    <!-- Show temporary preview if available -->
                                    <div v-if="previewUrls[pageKey]" class="img-wrapper">
                                        <img :src="previewUrls[pageKey]" class="img-fluid rounded">
                                    </div>
                                    <!-- Else show saved background -->
                                    <div v-else-if="getSetting(pageKey).background_path" class="img-wrapper">
                                        <img :src="'/storage/' + getSetting(pageKey).background_path" class="img-fluid rounded">
                                    </div>
                                    <div v-else class="empty-preview d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted opacity-50"></i>
                                    </div>
                                </div>
                                <div class="upload-controls flex-grow-1">
                                    <div class="custom-file custom-file-sm">
                                        <input type="file" @change="onFileChange($event, pageKey)" class="custom-file-input" :id="'bg-' + pageKey" accept="image/*">
                                        <label class="custom-file-label text-truncate" :for="'bg-' + pageKey">Choose image...</label>
                                    </div>
                                    <small class="text-muted xsmall mt-1 d-block">Recommended: PNG with low opacity</small>
                                </div>
                            </div>
                        </div>

                        <!-- Brand Colors -->
                        <div class="setting-section mb-4">
                            <label class="section-label">Aesthetic Colors</label>
                            <div class="row no-gutters">
                                <div class="col-6 pr-2">
                                    <div class="color-item p-2 border rounded d-flex align-items-center">
                                        <input type="color" v-model="getSetting(pageKey).colors.title" class="color-picker-input mr-2">
                                        <div class="color-info">
                                            <span class="d-block xsmall font-weight-bold">Title Color</span>
                                            <code class="xsmall text-uppercase">{{ getSetting(pageKey).colors.title }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 pl-2">
                                    <div class="color-item p-2 border rounded d-flex align-items-center">
                                        <input type="color" v-model="getSetting(pageKey).colors.body" class="color-picker-input mr-2">
                                        <div class="color-info">
                                            <span class="d-block xsmall font-weight-bold">Body Text</span>
                                            <code class="xsmall text-uppercase">{{ getSetting(pageKey).colors.body }}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Memo Number Format (Draggable) -->
                        <div class="setting-section mb-4">
                            <label class="section-label d-flex justify-content-between align-items-center">
                                Memo Number Format
                                <span class="badge badge-info xsmall">Drag to Reorder</span>
                            </label>
                            
                            <!-- Draggable Active Area -->
                            <div class="memo-drag-area p-2 border rounded bg-light mb-2 min-h-50">
                                <draggable 
                                    v-model="getSetting(pageKey).memo_format" 
                                    item-key="index"
                                    class="d-flex flex-wrap"
                                    animation="200"
                                    ghost-class="ghost-chip"
                                >
                                    <template #item="{element, index}">
                                        <div class="memo-chip mr-2 mb-1">
                                            <i class="fas fa-grip-vertical mr-1 opacity-50"></i>
                                            {{ formatKeyword(element) }}
                                            <button type="button" class="btn-remove-chip" @click="removeChip(pageKey, index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                </draggable>
                                <div v-if="getSetting(pageKey).memo_format.length === 0" class="text-muted text-center py-2 xsmall italic">
                                    Select components from below to build memo
                                </div>
                            </div>

                            <!-- Available Pool -->
                            <div class="available-chips d-flex flex-wrap p-1">
                                <button v-for="kw in getAvailableKw(pageKey)" 
                                        :key="kw" 
                                        type="button"
                                        class="btn-available-chip mr-1 mb-1"
                                        @click="addChip(pageKey, kw)"
                                        :disabled="getSetting(pageKey).memo_format.includes(kw)">
                                    <i class="fas fa-plus mr-1"></i> {{ formatKeyword(kw) }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Custom Text Aliases -->
                        <div class="setting-section mb-4">
                            <label class="section-label d-flex justify-content-between">
                                Custom Text Tokens
                                <span class="btn-token-info" @click="showKeywords = !showKeywords">
                                    <i class="fas fa-info-circle mr-1"></i>কি-ওয়ার্ড গাইড
                                </span>
                            </label>
                            <div v-show="showKeywords" class="mb-2 p-2 border rounded bg-white shadow-sm token-guide">
                                <code v-for="kw in ['[year]', '[class]', '[section]', '[type]', '[serial]']" 
                                      :key="kw" class="mr-2 mb-1 d-inline-block">{{ kw }}</code>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="token-input-wrapper">
                                        <span class="lang-tag">BN</span>
                                        <input type="text" v-model="getSetting(pageKey).custom_text_raw" 
                                               class="form-control form-control-sm modern-input" 
                                               placeholder="Comma separated: TEXT1, TEXT2...">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="token-input-wrapper">
                                        <span class="lang-tag">EN</span>
                                        <input type="text" v-model="getSetting(pageKey).custom_text_en_raw" 
                                               class="form-control form-control-sm modern-input" 
                                               placeholder="Comma separated: TEXT1, TEXT2...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Precision Margins -->
                        <div class="setting-section border-top pt-3">
                            <label class="section-label text-danger">
                                <i class="fas fa-border-style mr-1"></i>Page Margins (Inch)
                            </label>
                            <div class="row no-gutters">
                                <div v-for="side in ['top', 'right', 'bottom', 'left']" :key="side" class="col-3 px-1 text-center">
                                    <label class="xsmall text-muted text-uppercase mb-0">{{ side }}</label>
                                    <input type="number" step="0.1" v-model="getSetting(pageKey).margins[side]" 
                                           class="form-control form-control-sm border-0 bg-light text-center font-weight-bold" 
                                           style="border-radius: 4px;">
                                </div>
                            </div>
                            <p class="text-muted xsmall mt-2 mb-0 italic">Applied ONLY to 'Pad/No Header' layout for pre-printed paper.</p>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-top-0 px-4 pb-4">
                        <button type="submit" class="btn btn-primary btn-block premium-save-btn" :disabled="loading[pageKey]">
                            <i class="fas mr-2" :class="loading[pageKey] ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            Save Config for {{ pageKey.toUpperCase() }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import draggable from 'vuedraggable';
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
const previewUrls = ref({});

const availableKeywords = ref({});
props.pages.forEach(p => {
    availableKeywords.value[p] = ['institution_code', 'custom_text', 'academic_year', 'serial_no', 'class', 'type'];
});

const getAvailableKw = (page) => {
    return availableKeywords.value[page] || [];
};

const getSetting = (page) => {
    if (!settings.value[page]) {
        const initial = props.initialSettings[page] || {};
        settings.value[page] = {
            colors: initial.colors || { title: '#000000', body: '#333333' },
            memo_format: initial.memo_format || ['institution_code', 'serial_no'],
            custom_text_raw: Array.isArray(initial.custom_text) ? initial.custom_text.join(',') : (initial.custom_text || ''),
            custom_text_en_raw: Array.isArray(initial.custom_text_en) ? initial.custom_text_en.join(',') : (initial.custom_text_en || ''),
            background_path: initial.background_path || null,
            margins: initial.margins || { top: 1.5, right: 0.6, bottom: 0.8, left: 0.6 }
        };
    }
    return settings.value[page];
};

const formatKeyword = (kw) => {
    return kw.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const addChip = (page, kw) => {
    const s = getSetting(page);
    if (!s.memo_format.includes(kw)) {
        s.memo_format.push(kw);
    }
};

const removeChip = (page, index) => {
    getSetting(page).memo_format.splice(index, 1);
};

const onFileChange = (e, page) => {
    const file = e.target.files[0];
    files.value[page] = file;
    // Generate temporary URL for preview
    if (file) {
        previewUrls.value[page] = URL.createObjectURL(file);
    } else {
        previewUrls.value[page] = null;
    }
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
        formData.append('custom_text_en', s.custom_text_en_raw);

        // Append margins
        formData.append('margins[top]', s.margins.top);
        formData.append('margins[right]', s.margins.right);
        formData.append('margins[bottom]', s.margins.bottom);
        formData.append('margins[left]', s.margins.left);

        const response = await axios.post(`/principal/institute/${props.schoolId}/documents/settings`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        // Update local setting with returned background path if uploaded
        if (response.data && response.data.setting && response.data.setting.background_path) {
            getSetting(page).background_path = response.data.setting.background_path;
            // Clear temporary preview
            previewUrls.value[page] = null;
            // Reset file input
            files.value[page] = null;
        }
        
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
.premium-title { font-weight: 800; color: #2d3436; font-size: 1.7rem; letter-spacing: -0.5px; }
.premium-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); }
.premium-card:hover { transform: translateY(-5px); }
.card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 1.5rem; }
.section-label { font-size: 0.85rem; font-weight: 700; color: #636e72; text-transform: uppercase; margin-bottom: 0.75rem; display: block; letter-spacing: 0.5px; }

/* Colors */
.color-picker-input { -webkit-appearance: none; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; padding: 0; background: none; }
.color-picker-input::-webkit-color-swatch { border: none; border-radius: 6px; }

/* Memo Drag Area */
.memo-drag-area { min-height: 60px; display: flex; align-items: center; border-style: dashed !important; border-width: 2px !important; }
.memo-chip { background: #fff; border: 1px solid #dfe6e9; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #2d3436; display: flex; align-items: center; cursor: move; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
.ghost-chip { opacity: 0.4; background: #e0e0e0; }
.btn-remove-chip { border: none; background: transparent; color: #ff7675; margin-left: 6px; padding: 0; cursor: pointer; font-size: 0.7rem; }
.btn-available-chip { border: 1px solid #74b9ff; background: #f0f7ff; color: #0984e3; padding: 3px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-available-chip:hover:not(:disabled) { background: #0984e3; color: #fff; }
.btn-available-chip:disabled { border-color: #dfe6e9; color: #b2bec3; background: #f5f6fa; cursor: not-allowed; }

/* Inputs */
.modern-input { border-radius: 8px; border: 1px solid #dfe6e9; padding: 10px 12px; height: auto; transition: border-color 0.2s; min-height: 40px; }
select.modern-input { padding-top: 0; padding-bottom: 0; height: 40px; }
.modern-input:focus { border-color: #0984e3; box-shadow: none; outline: none; }
.token-input-wrapper { position: relative; }
.lang-tag { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 10px; font-weight: 900; color: #b2bec3; pointer-events: none; }

/* Token Guide */
.btn-token-info { font-size: 0.75rem; color: #0984e3; cursor: pointer; font-weight: 600; }
.token-guide code { background: #f5f6fa; padding: 2px 6px; border-radius: 4px; color: #d63031; }

/* Preview */
.background-preview-container { background: #fcfcfc; }
.preview-box { width: 50px; height: 50px; overflow: hidden; background: #fff; border: 1px solid #eee; border-radius: 8px; }
.empty-preview { height: 100%; font-size: 1.2rem; }

/* Save btn */
.premium-save-btn { border-radius: 12px; padding: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(9, 132, 227, 0.2); }

.italic { font-style: italic; }
.xsmall { font-size: 0.7rem; }
.opacity-50 { opacity: 0.5; }
</style>
