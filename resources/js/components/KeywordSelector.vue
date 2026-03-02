<template>
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">কিওয়ার্ডসমূহ (Keywords)</h3>
        </div>
        <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
            <div v-for="(group, label) in keywordGroups" :key="label" class="mb-3">
                <h6 class="border-bottom pb-1 text-primary small font-weight-bold">{{ label }}</h6>
                <div class="d-flex flex-wrap">
                    <button v-for="kw in group" :key="kw.key" 
                            type="button" 
                            class="btn btn-xs btn-outline-secondary m-1" 
                            @click="$emit('select', kw.key)"
                            :title="kw.desc">
                        {{ kw.label }}
                    </button>
                </div>
            </div>
            <div class="alert alert-light border small mt-2">
                কিওয়ার্ডে ক্লিক করলে সেটি টেম্পলেট বডিতে যুক্ত হবে।
            </div>
        </div>
    </div>
</template>

<script setup>
const emit = defineEmits(['select']);

const keywordGroups = {
    'শিক্ষার্থীর তথ্য': [
        { key: '[student_name_bn]', label: 'নাম (বাংলা)', desc: 'শিক্ষার্থীর বাংলা নাম' },
        { key: '[student_name_en]', label: 'নাম (ইংরেজি)', desc: 'শিক্ষার্থীর ইংরেজি নাম' },
        { key: '[student_id]', label: 'আইডি', desc: 'শিক্ষার্থী আইডি' },
        { key: '[roll_no]', label: 'রোল', desc: 'বর্তমান রোল নম্বর' },
        { key: '[date_of_birth]', label: 'জন্ম তারিখ', desc: 'জন্ম তারিখ' },
        { key: '[gender]', label: 'লিঙ্গ', desc: 'লিঙ্গ' },
        { key: '[blood_group]', label: 'রক্তের গ্রুপ', desc: 'রক্তের গ্রুপ' },
    ],
    'অভিভাবকের তথ্য': [
        { key: '[father_name_bn]', label: 'পিতার নাম (বাংলা)', desc: 'পিতার বাংলা নাম' },
        { key: '[father_name_en]', label: 'পিতার নাম (ইংরেজি)', desc: 'পিতার ইংরেজি নাম' },
        { key: '[mother_name_bn]', label: 'মাতার নাম (বাংলা)', desc: 'মাতার বাংলা নাম' },
        { key: '[mother_name_en]', label: 'মাতার নাম (ইংরেজি)', desc: 'মাতার ইংরেজি নাম' },
        { key: '[guardian_phone]', label: 'ফোন', desc: 'অভিভাবকের ফোন নম্বর' },
    ],
    'বর্তমান ঠিকানা': [
        { key: '[present_village]', label: 'গ্রাম', desc: 'বর্তমান গ্রাম' },
        { key: '[present_post_office]', label: 'ডাকঘর', desc: 'বর্তমান ডাকঘর' },
        { key: '[present_upazilla]', label: 'উপজেলা', desc: 'বর্তমান উপজেলা' },
        { key: '[present_district]', label: 'জেলা', desc: 'বর্তমান জেলা' },
    ],
    'স্থায়ী ঠিকানা': [
        { key: '[permanent_village]', label: 'গ্রাম', desc: 'স্থায়ী গ্রাম' },
        { key: '[permanent_post_office]', label: 'ডাকঘর', desc: 'স্থায়ী ডাকঘর' },
        { key: '[permanent_upazilla]', label: 'উপজেলা', desc: 'স্থায়ী উপজেলা' },
        { key: '[permanent_district]', label: 'জেলা', desc: 'স্থায়ী জেলা' },
    ],
    'প্রতিষ্ঠান ও শ্রেণি': [
        { key: '[school_name]', label: 'প্রতিষ্ঠানের নাম', desc: 'প্রতিষ্ঠানের নাম' },
        { key: '[class_name]', label: 'শ্রেণি', desc: 'বর্তমান শ্রেণি' },
        { key: '[section_name]', label: 'শাখা', desc: 'বর্তমান শাখা' },
        { key: '[session]', label: 'শিক্ষা বর্ষ', desc: 'বর্তমান সেশন/শিক্ষা বর্ষ' },
        { key: '[date]', label: 'আজকের তারিখ', desc: 'প্রিন্ট করার তারিখ' },
    ]
};
</script>

<style scoped>
.btn-xs { padding: 1px 5px; font-size: 11px; }
</style>
