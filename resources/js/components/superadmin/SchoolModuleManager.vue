<template>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">মডিউল পারমিশন</h3>
            <div class="card-tools">
                <button @click="saveModules" class="btn btn-primary btn-sm" :disabled="saving">
                    <i v-if="saving" class="fas fa-spinner fa-spin mr-1"></i>
                    <i v-else class="fas fa-save mr-1"></i>
                    পছন্দসমূহ সংরক্ষণ করুন
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div v-if="loading" class="text-center p-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2 text-muted">লোড হচ্ছে...</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>মডিউল নাম</th>
                            <th>বিবরণ</th>
                            <th style="width: 150px;" class="text-center">অনুমতি</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(module, index) in modules" :key="module.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <strong>{{ module.name }}</strong>
                                <br>
                                <small class="text-muted">{{ module.slug }}</small>
                            </td>
                            <td>{{ module.description }}</td>
                            <td class="text-center">
                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                    <input 
                                        type="checkbox" 
                                        class="custom-control-input" 
                                        :id="'module-switch-' + module.id"
                                        v-model="module.is_enabled"
                                    >
                                    <label class="custom-control-label" :for="'module-switch-' + module.id">
                                        {{ module.is_enabled ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="modules.length === 0">
                            <td colspan="4" class="text-center p-4 text-muted">কোন মডিউল পাওয়া যায়নি।</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-right" v-if="!loading && modules.length > 0">
            <button @click="saveModules" class="btn btn-primary" :disabled="saving">
                <i v-if="saving" class="fas fa-spinner fa-spin mr-1"></i>
                    <i v-else class="fas fa-save mr-1"></i>
                পরিবর্তন সংরক্ষণ করুন
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    schoolId: {
        type: [Number, String],
        required: true
    },
    fetchUrl: {
        type: String,
        required: true
    },
    updateUrl: {
        type: String,
        required: true
    }
});

const modules = ref([]);
const loading = ref(true);
const saving = ref(false);

const fetchModules = async () => {
    try {
        const response = await axios.get(props.fetchUrl);
        modules.ref = response.data;
        // Wait, modules.value = ...
        modules.value = response.data;
    } catch (error) {
        console.error('Error fetching modules:', error);
        if (window.toastr) {
            window.toastr.error('মডিউল লোড করতে ব্যর্থ হয়েছে।');
        }
    } finally {
        loading.value = false;
    }
};

const saveModules = async () => {
    saving.value = true;
    try {
        const response = await axios.post(props.updateUrl, {
            modules: modules.value
        });
        if (window.toastr) {
            window.toastr.success(response.data.message || 'সফলভাবে সংরক্ষিত হয়েছে।');
        }
    } catch (error) {
        console.error('Error updating modules:', error);
        if (window.toastr) {
            window.toastr.error(error.response?.data?.message || 'আপডেট করতে ব্যর্থ হয়েছে।');
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchModules();
});
</script>

<style scoped>
.custom-control-label {
    cursor: pointer;
}
</style>
