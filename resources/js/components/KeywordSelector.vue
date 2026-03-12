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
        { key: '[student_id]', label: 'আইডি', desc: 'শিক্ষার্থী আইডি (একই থাকবে)' },
        { key: '[roll_no_bn]', label: 'রোল (বাংলা)', desc: 'বর্তমান রোল নম্বর (বাংলায়)' },
        { key: '[roll_no_en]', label: 'রোল (ইংরেজি)', desc: 'বর্তমান রোল নম্বর (ইংরেজিতে)' },
        { key: '[date_of_birth]', label: 'জন্ম তারিখ', desc: 'ভাষা অনুযায়ী জন্ম তারিখ (স্বয়ংক্রিয়)' },
        { key: '[date_of_birth_bn]', label: 'জন্ম তারিখ (বাংলা)', desc: 'বাংলায় জন্ম তারিখ' },
        { key: '[date_of_birth_en]', label: 'Date of Birth (EN)', desc: 'ইংরেজিতে জন্ম তারিখ' },
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
        { key: '[present_village_bn]', label: 'গ্রাম (বাংলা)', desc: 'বর্তমান গ্রাম (বাংলায়)' },
        { key: '[present_village_en]', label: 'Village (EN)', desc: 'বর্তমান গ্রাম (ইংরেজিতে)' },
        { key: '[present_post_office_bn]', label: 'ডাকঘর (বাংলা)', desc: 'বর্তমান ডাকঘর (বাংলায়)' },
        { key: '[present_post_office_en]', label: 'Post Office (EN)', desc: 'বর্তমান ডাকঘর (ইংরেজিতে)' },
        { key: '[present_upazilla_bn]', label: 'উপজেলা (বাংলা)', desc: 'বর্তমান উপজেলা (বাংলায়)' },
        { key: '[present_upazilla_en]', label: 'Upazila (EN)', desc: 'বর্তমান উপজেলা (ইংরেজিতে)' },
        { key: '[present_district_bn]', label: 'জেলা (বাংলা)', desc: 'বর্তমান জেলা (বাংলায়)' },
        { key: '[present_district_en]', label: 'District (EN)', desc: 'বর্তমান জেলা (ইংরেজিতে)' },
    ],
    'স্থায়ী ঠিকানা': [
        { key: '[permanent_village_bn]', label: 'গ্রাম (বাংলা)', desc: 'স্থায়ী গ্রাম (বাংলায়)' },
        { key: '[permanent_village_en]', label: 'Village (EN)', desc: 'স্থায়ী গ্রাম (ইংরেজিতে)' },
        { key: '[permanent_post_office_bn]', label: 'ডাকঘর (বাংলা)', desc: 'স্থায়ী ডাকঘর (বাংলায়)' },
        { key: '[permanent_post_office_en]', label: 'Post Office (EN)', desc: 'স্থায়ী ডাকঘর (ইংরেজিতে)' },
        { key: '[permanent_upazilla_bn]', label: 'উপজেলা (বাংলা)', desc: 'স্থায়ী উপজেলা (বাংলায়)' },
        { key: '[permanent_upazilla_en]', label: 'Upazila (EN)', desc: 'স্থায়ী উপজেলা (ইংরেজিতে)' },
        { key: '[permanent_district_bn]', label: 'জেলা (বাংলা)', desc: 'স্থায়ী জেলা (বাংলায়)' },
        { key: '[permanent_district_en]', label: 'District (EN)', desc: 'স্থায়ী জেলা (ইংরেজিতে)' },
    ],
    'প্রতিষ্ঠান ও শ্রেণি': [
        { key: '[school_name_bn]', label: 'প্রতিষ্ঠানের নাম (বাংলা)', desc: 'প্রতিষ্ঠানের বাংলা নাম' },
        { key: '[school_name_en]', label: 'প্রতিষ্ঠানের নাম (ইংরেজি)', desc: 'প্রতিষ্ঠানের ইংরেজি নাম' },
        { key: '[class_name_bn]', label: 'শ্রেণি (বাংলা)', desc: 'বর্তমান শ্রেণির বাংলা নাম' },
        { key: '[class_name_en]', label: 'শ্রেণি (ইংরেজি)', desc: 'বর্তমান শ্রেণির ইংরেজি নাম' },
        { key: '[section_name_bn]', label: 'শাখা (বাংলা)', desc: 'বর্তমান শাখার বাংলা নাম' },
        { key: '[section_name_en]', label: 'শাখা (ইংরেজি)', desc: 'বর্তমান শাখার ইংরেজি নাম' },
        { key: '[session]', label: 'শিক্ষাবর্ষ', desc: 'বর্তমান শিক্ষাবর্ষ (সাধারণ)' },
        { key: '[session_bn]', label: 'শিক্ষাবর্ষ (বাংলা)', desc: 'বর্তমান শিক্ষাবর্ষ (বাংলায়)' },
        { key: '[session_en]', label: 'Session (EN)', desc: 'বর্তমান শিক্ষাবর্ষ (ইংরেজিতে)' },
        { key: '[date]', label: 'আজকের তারিখ', desc: 'প্রিন্ট করার তারিখ (ল্যাঙ্গুয়েজ অনুযায়ী)' },
    ]
};
</script>

<style scoped>
.btn-xs { padding: 1px 5px; font-size: 11px; }
</style>
