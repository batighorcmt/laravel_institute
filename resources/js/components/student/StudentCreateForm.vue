<template>
  <div class="student-create-wrapper font-hind">
    <!-- Page Header -->
    <div class="page-header-card">
      <div class="header-content">
        <div class="header-left">
          <div class="header-icon-wrap">
            <i class="fas fa-user-graduate"></i>
          </div>
          <div>
            <h1 class="page-title">নতুন শিক্ষার্থী ভর্তি</h1>
            <p class="page-subtitle">শিক্ষার্থীর সকল তথ্য পূরণ করে ভর্তি সম্পন্ন করুন</p>
          </div>
        </div>
        <a :href="backUrl" class="btn-back">
          <i class="fas fa-arrow-left"></i>
          <span>তালিকা</span>
        </a>
      </div>
    </div>

    <!-- Error Messages -->
    <transition name="slide-fade">
      <div v-if="serverErrors.length" class="error-alert">
        <div class="error-alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="error-alert-content">
          <strong>ত্রুটি সমূহ:</strong>
          <ul>
            <li v-for="(err, i) in serverErrors" :key="i">{{ err }}</li>
          </ul>
        </div>
        <button type="button" @click="serverErrors = []" class="error-close"><i class="fas fa-times"></i></button>
      </div>
    </transition>

    <!-- Success Message -->
    <transition name="slide-fade">
      <div v-if="successMsg" class="success-alert">
        <i class="fas fa-check-circle"></i>
        <span>{{ successMsg }}</span>
      </div>
    </transition>

    <!-- Form -->
    <form @submit.prevent="submitForm" enctype="multipart/form-data" ref="formEl" class="form-container">
      <input type="hidden" name="_token" :value="csrfToken">

      <div class="sections-grid">
        <!-- Section 1: ভর্তি তথ্য -->
        <div class="section-card theme-indigo">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>একাডেমিক ও ভর্তি তথ্য</h3>
          </div>
          <div class="section-body">
            <div class="form-grid cols-4">
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-calendar-alt"></i> শিক্ষাবর্ষ</label>
                <select v-model="form.enroll_academic_year_id" class="form-input" name="enroll_academic_year_id" required>
                  <option value="">-- নির্বাচন --</option>
                  <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-school"></i> শ্রেণি</label>
                <select v-model="form.enroll_class_id" @change="onClassChange" class="form-input" name="enroll_class_id" required>
                  <option value="">-- নির্বাচন --</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-code-branch"></i> শাখা</label>
                <select v-model="form.enroll_section_id" @change="loadNextRoll" class="form-input" name="enroll_section_id" required>
                  <option value="">--</option>
                  <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
              <div class="form-group" v-if="groups.length > 0">
                <label class="form-label required"><i class="fas fa-users"></i> গ্রুপ</label>
                <select v-model="form.enroll_group_id" @change="loadNextRoll" class="form-input" name="enroll_group_id" required>
                  <option value="">--</option>
                  <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.bangla_name }})</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-hashtag"></i> রোল</label>
                <input type="number" v-model="form.enroll_roll_no" class="form-input" name="enroll_roll_no" min="1" placeholder="রোল নম্বর" required>
                <small v-if="nextRollHint" class="field-hint hint-text">
                  <i class="fas fa-info-circle"></i> পরবর্তী রোল: {{ nextRollHint }}
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: ব্যক্তিগত তথ্য -->
        <div class="section-card theme-blue">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-user"></i></div>
            <h3>ব্যক্তিগত তথ্য</h3>
          </div>
          <div class="section-body">
            <div class="form-grid cols-2">
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-user-edit"></i> শিক্ষার্থীর নাম (English)</label>
                <input type="text" v-model="form.student_name_en" class="form-input" name="student_name_en" placeholder="Student Name" required>
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-user-edit"></i> শিক্ষার্থীর নাম (বাংলা)</label>
                <input type="text" v-model="form.student_name_bn" class="form-input" name="student_name_bn" placeholder="শিক্ষার্থীর নাম">
              </div>
            </div>

            <div class="form-grid cols-4 mt-3">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-birthday-cake"></i> জন্ম তারিখ</label>
                <input type="date" v-model="form.date_of_birth" class="form-input" name="date_of_birth">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-venus-mars"></i> লিঙ্গ</label>
                <select v-model="form.gender" class="form-input" name="gender">
                  <option value="male">ছেলে</option>
                  <option value="female">মেয়ে</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-praying-hands"></i> ধর্ম</label>
                <select v-model="form.religion" class="form-input" name="religion">
                  <option value="">-- নির্বাচন --</option>
                  <option v-for="(label, val) in religions" :key="val" :value="val">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-tint"></i> রক্তের গ্রুপ</label>
                <select v-model="form.blood_group" class="form-input" name="blood_group">
                  <option value="">-- নির্বাচন --</option>
                  <option v-for="bg in bloodGroups" :key="bg" :value="bg">{{ bg }}</option>
                </select>
              </div>
            </div>

            <div class="form-grid cols-2 mt-3">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-id-badge"></i> বোর্ড রেজিস্ট্রেশন নং</label>
                <input type="text" v-model="form.board_registration_no" class="form-input" name="board_registration_no" placeholder="ঐচ্ছিক">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-file-alt"></i> জন্ম সনদ নম্বর</label>
                <input type="text" v-model="form.birth_certificate_no" class="form-input" name="birth_certificate_no" placeholder="জন্ম সনদ নম্বর">
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: অভিভাবক ও ঠিকানা -->
        <div class="section-card theme-emerald">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-users"></i></div>
            <h3>অভিভাবক ও ঠিকানা</h3>
          </div>
          <div class="section-body">
            <div class="form-grid cols-2">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-male"></i> পিতার নাম (English)</label>
                <input type="text" v-model="form.father_name" class="form-input" name="father_name" placeholder="Father's name">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-male"></i> পিতার নাম (বাংলা)</label>
                <input type="text" v-model="form.father_name_bn" class="form-input" name="father_name_bn" placeholder="পিতার নাম">
              </div>
            </div>
            <div class="form-grid cols-2 mt-3">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-female"></i> মাতার নাম (English)</label>
                <input type="text" v-model="form.mother_name" class="form-input" name="mother_name" placeholder="Mother's name">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-female"></i> মাতার নাম (বাংলা)</label>
                <input type="text" v-model="form.mother_name_bn" class="form-input" name="mother_name_bn" placeholder="মাতার নাম">
              </div>
            </div>

            <div class="divider"></div>

            <div class="form-grid cols-3">
              <div class="form-group">
                <label class="form-label required"><i class="fas fa-phone"></i> অভিভাবকের ফোন</label>
                <input type="text" v-model="form.guardian_phone" class="form-input" name="guardian_phone" required placeholder="০১XXXXXXXXX">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-user-shield"></i> অভিভাবকের সম্পর্ক</label>
                <select v-model="form.guardian_relation" @change="applyGuardianBehavior" class="form-input" name="guardian_relation">
                  <option value="">-- নির্বাচন --</option>
                  <option value="father">পিতা</option>
                  <option value="mother">মাতা</option>
                  <option value="other">অন্যান্য</option>
                </select>
              </div>
            </div>
            <div class="form-grid cols-2 mt-3">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-user-tag"></i> অভিভাবকের নাম (English)</label>
                <input type="text" v-model="form.guardian_name_en" class="form-input" name="guardian_name_en" :readonly="guardianAutoFill" :class="{ 'readonly-field': guardianAutoFill }">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-user-tag"></i> অভিভাবকের নাম (বাংলা)</label>
                <input type="text" v-model="form.guardian_name_bn" class="form-input" name="guardian_name_bn" :readonly="guardianAutoFill" :class="{ 'readonly-field': guardianAutoFill }">
              </div>
            </div>

            <div class="divider"></div>

            <!-- Present Address -->
            <div class="address-section">
              <h4 class="address-title"><i class="fas fa-map-marker-alt"></i> বর্তমান ঠিকানা</h4>
              <div class="form-grid cols-2">
                <div class="form-group">
                  <label class="form-label">গ্রাম/এলাকা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.present_village" name="present_village" class="form-input" placeholder="বাংলায়" @input="onPresentChange">
                    <input type="text" v-model="form.present_village_en" name="present_village_en" class="form-input" placeholder="English" @input="onPresentChange">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">পাড়া/মহল্লা</label>
                  <input type="text" v-model="form.present_para_moholla" name="present_para_moholla" class="form-input" placeholder="বাংলায়" @input="onPresentChange">
                </div>
              </div>
              <div class="form-grid cols-3 mt-3">
                <div class="form-group">
                  <label class="form-label">পোস্ট অফিস</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.present_post_office" name="present_post_office" class="form-input" placeholder="বাংলায়" @input="onPresentChange">
                    <input type="text" v-model="form.present_post_office_en" name="present_post_office_en" class="form-input" placeholder="English" @input="onPresentChange">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">উপজেলা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.present_upazilla" name="present_upazilla" class="form-input" placeholder="বাংলায়" @input="onPresentChange">
                    <input type="text" v-model="form.present_upazilla_en" name="present_upazilla_en" class="form-input" placeholder="English" @input="onPresentChange">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">জেলা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.present_district" name="present_district" class="form-input" placeholder="বাংলায়" @input="onPresentChange">
                    <input type="text" v-model="form.present_district_en" name="present_district_en" class="form-input" placeholder="English" @input="onPresentChange">
                  </div>
                </div>
              </div>
            </div>

            <!-- Permanent Address -->
            <div class="address-section mt-4">
              <div class="address-header-row">
                <h4 class="address-title"><i class="fas fa-home"></i> স্থায়ী ঠিকানা</h4>
                <label class="checkbox-label">
                  <input type="checkbox" v-model="sameAsPresent" @change="copyPresentToPermanent">
                  <span class="checkmark"></span>
                  <span>বর্তমান ঠিকানা কপি করুন</span>
                </label>
              </div>
              <div class="form-grid cols-2">
                <div class="form-group">
                  <label class="form-label">গ্রাম/এলাকা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.permanent_village" name="permanent_village" class="form-input" placeholder="বাংলায়" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                    <input type="text" v-model="form.permanent_village_en" name="permanent_village_en" class="form-input" placeholder="English" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">পাড়া/মহল্লা</label>
                  <input type="text" v-model="form.permanent_para_moholla" name="permanent_para_moholla" class="form-input" placeholder="বাংলায়" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                </div>
              </div>
              <div class="form-grid cols-3 mt-3">
                <div class="form-group">
                  <label class="form-label">পোস্ট অফিস</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.permanent_post_office" name="permanent_post_office" class="form-input" placeholder="বাংলায়" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                    <input type="text" v-model="form.permanent_post_office_en" name="permanent_post_office_en" class="form-input" placeholder="English" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">উপজেলা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.permanent_upazilla" name="permanent_upazilla" class="form-input" placeholder="বাংলায়" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                    <input type="text" v-model="form.permanent_upazilla_en" name="permanent_upazilla_en" class="form-input" placeholder="English" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">জেলা</label>
                  <div class="dual-input">
                    <input type="text" v-model="form.permanent_district" name="permanent_district" class="form-input" placeholder="বাংলায়" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                    <input type="text" v-model="form.permanent_district_en" name="permanent_district_en" class="form-input" placeholder="English" :readonly="sameAsPresent" :class="{ 'readonly-field': sameAsPresent }">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: ছবি ও অফিসিয়াল তথ্য -->
        <div class="section-card theme-amber">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-camera"></i></div>
            <h3>ছবি ও পূর্ববর্তী শিক্ষা</h3>
          </div>
          <div class="section-body">
            <div class="photo-and-prev">
              <div class="photo-upload-area">
                <label class="form-label block text-center mb-3">শিক্ষার্থীর ছবি</label>
                <div class="photo-preview-box" @click="$refs.photoInput.click()">
                  <img v-if="photoFile" :src="photoPreview" alt="Student Photo">
                  <div v-else class="empty-photo">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <p class="upload-text">ক্লিক করে ছবি নির্বাচন করুন</p>
                    <span class="upload-btn-fake">Browse...</span>
                  </div>
                </div>
                <input type="file" ref="photoInput" name="photo" accept="image/*" @change="handlePhotoUpload" class="hidden-input">
                <p class="photo-hint">35×45mm (~413×531px), ≤1MB</p>
              </div>
              <div class="prev-education">
                <div class="form-grid cols-3">
                  <div class="form-group">
                    <label class="form-label">পূর্ববর্তী প্রতিষ্ঠান</label>
                    <input type="text" v-model="form.previous_school" name="previous_school" class="form-input" placeholder="প্রতিষ্ঠানের নাম">
                  </div>
                  <div class="form-group">
                    <label class="form-label">পাসের বছর</label>
                    <input type="text" v-model="form.pass_year" name="pass_year" class="form-input" placeholder="যেমন: ২০২৫">
                  </div>
                  <div class="form-group">
                    <label class="form-label">ফলাফল/গ্রেড</label>
                    <input type="text" v-model="form.previous_result" name="previous_result" class="form-input" placeholder="A+, GPA 5.00">
                  </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="form-grid cols-2 mt-4">
                  <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-check"></i> ভর্তি তারিখ</label>
                    <input type="date" :value="todayDate" class="form-input readonly-field" name="admission_date" readonly>
                    <small class="field-hint"><i class="fas fa-lock"></i> ভর্তি তারিখ স্বয়ংক্রিয়ভাবে আজকের তারিখ</small>
                  </div>
                  <div class="form-group">
                    <label class="form-label required"><i class="fas fa-toggle-on"></i> স্ট্যাটাস</label>
                    <select v-model="form.status" class="form-input" name="status" required>
                      <option value="active">সক্রিয়</option>
                      <option value="inactive">নিষ্ক্রিয়</option>
                      <option value="graduated">পাশ করেছে</option>
                      <option value="transferred">বদলি হয়েছে</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Action Footer -->
      <div class="form-action-footer">
        <button type="submit" :disabled="isSubmitting" class="btn-submit-solid">
          <i :class="isSubmitting ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ isSubmitting ? 'সংরক্ষণ হচ্ছে...' : 'শিক্ষার্থীর তথ্য সংরক্ষণ করুন' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'StudentCreateForm',
  props: {
    schoolId: { type: Number, required: true },
    storeUrl: { type: String, required: true },
    backUrl: { type: String, required: true },
    metaSectionsUrl: { type: String, required: true },
    metaGroupsUrl: { type: String, required: true },
    metaNextRollUrl: { type: String, required: true },
    initialYears: { type: Array, default: () => [] },
    initialClasses: { type: Array, default: () => [] },
    currentYearId: { type: Number, default: null },
    csrfToken: { type: String, required: true },
    oldInput: { type: Object, default: () => ({}) },
    validationErrors: { type: Array, default: () => [] },
  },
  data() {
    const today = new Date().toISOString().split('T')[0];
    return {
      isSubmitting: false,
      serverErrors: [...this.validationErrors],
      successMsg: '',
      sections: [],
      groups: [],
      nextRollHint: '',
      sameAsPresent: false,
      photoPreview: null,
      photoFile: null,
      todayDate: today,
      years: this.initialYears,
      classes: this.initialClasses,
      religions: { Islam: 'ইসলাম', Hindu: 'হিন্দু', Buddhist: 'বৌদ্ধ', Christian: 'খ্রিস্টান', Other: 'অন্যান্য' },
      bloodGroups: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
      form: {
        enroll_academic_year_id: this.currentYearId || '',
        enroll_class_id: '',
        enroll_section_id: '',
        enroll_group_id: '',
        enroll_roll_no: '',
        student_name_en: '',
        student_name_bn: '',
        date_of_birth: '',
        gender: 'male',
        religion: '',
        blood_group: '',
        board_registration_no: '',
        birth_certificate_no: '',
        father_name: '',
        father_name_bn: '',
        mother_name: '',
        mother_name_bn: '',
        guardian_phone: '',
        guardian_relation: '',
        guardian_name_en: '',
        guardian_name_bn: '',
        present_village: '',
        present_village_en: '',
        present_para_moholla: '',
        present_post_office: '',
        present_post_office_en: '',
        present_upazilla: '',
        present_upazilla_en: '',
        present_district: '',
        present_district_en: '',
        permanent_village: '',
        permanent_village_en: '',
        permanent_para_moholla: '',
        permanent_post_office: '',
        permanent_post_office_en: '',
        permanent_upazilla: '',
        permanent_upazilla_en: '',
        permanent_district: '',
        permanent_district_en: '',
        previous_school: '',
        pass_year: '',
        previous_result: '',
        status: 'active',
        admission_date: today,
      },
    };
  },
  computed: {
    guardianAutoFill() {
      return this.form.guardian_relation === 'father' || this.form.guardian_relation === 'mother';
    },
  },
  watch: {
    oldInput: {
      handler(val) {
        if (val && Object.keys(val).length) {
          Object.keys(val).forEach(k => {
            if (k in this.form) {
              this.form[k] = val[k];
            }
          });
        }
      },
      immediate: true,
    },
  },
  methods: {
    async fetchJSON(url, params) {
      const usp = new URLSearchParams(params);
      try {
        const resp = await fetch(url + '?' + usp.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        return await resp.json();
      } catch {
        return [];
      }
    },
    async onClassChange() {
      this.form.enroll_section_id = '';
      this.form.enroll_group_id = '';
      this.sections = [];
      this.groups = [];
      this.nextRollHint = '';

      if (!this.form.enroll_class_id) return;

      const [sectData, grpData] = await Promise.all([
        this.fetchJSON(this.metaSectionsUrl, { class_id: this.form.enroll_class_id }),
        this.fetchJSON(this.metaGroupsUrl, { class_id: this.form.enroll_class_id }),
      ]);

      this.sections = sectData || [];
      this.groups = (grpData && grpData.length) ? grpData : [];
      setTimeout(() => this.loadNextRoll(), 200);
    },
    async loadNextRoll() {
      if (!this.form.enroll_academic_year_id || !this.form.enroll_class_id) {
        this.nextRollHint = '';
        return;
      }
      const data = await this.fetchJSON(this.metaNextRollUrl, {
        year_id: this.form.enroll_academic_year_id,
        class_id: this.form.enroll_class_id,
        section_id: this.form.enroll_section_id || '',
        group_id: this.form.enroll_group_id || '',
      });
      if (data && data.next) {
        this.nextRollHint = data.next;
        if (!this.form.enroll_roll_no) {
          this.form.enroll_roll_no = data.next;
        }
      }
    },
    applyGuardianBehavior() {
      if (this.form.guardian_relation === 'father') {
        this.form.guardian_name_en = this.form.father_name;
        this.form.guardian_name_bn = this.form.father_name_bn;
      } else if (this.form.guardian_relation === 'mother') {
        this.form.guardian_name_en = this.form.mother_name;
        this.form.guardian_name_bn = this.form.mother_name_bn;
      }
    },
    onPresentChange() {
      if (this.sameAsPresent) {
        this.copyPresentToPermanent();
      }
    },
    copyPresentToPermanent() {
      const fields = [
        'village', 'village_en', 'para_moholla',
        'post_office', 'post_office_en',
        'upazilla', 'upazilla_en',
        'district', 'district_en',
      ];
      if (this.sameAsPresent) {
        fields.forEach(f => {
          this.form['permanent_' + f] = this.form['present_' + f];
        });
      }
    },
    async handlePhotoUpload(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      try {
        const processed = await this.processPassportPhoto(file, 413, 531, 1024 * 1024);
        this.photoFile = processed;
        // Update preview
        const reader = new FileReader();
        reader.onload = (ev) => {
          this.photoPreview = ev.target.result;
        };
        reader.readAsDataURL(processed);
        // Replace file input
        const dt = new DataTransfer();
        dt.items.add(processed);
        this.$refs.photoInput.files = dt.files;
      } catch (err) {
        console.error('Photo process failed', err);
      }
    },
    processPassportPhoto(file, targetW, targetH, maxBytes) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = async () => {
          const srcW = img.naturalWidth || img.width;
          const srcH = img.naturalHeight || img.height;
          const targetAspect = targetW / targetH;
          const srcAspect = srcW / srcH;
          let sx, sy, sw, sh;
          if (srcAspect > targetAspect) {
            sh = srcH; sw = Math.round(srcH * targetAspect);
            sx = Math.round((srcW - sw) / 2); sy = 0;
          } else {
            sw = srcW; sh = Math.round(srcW / targetAspect);
            sx = 0; sy = Math.round((srcH - sh) / 2);
          }
          const canvas = document.createElement('canvas');
          canvas.width = targetW; canvas.height = targetH;
          const ctx = canvas.getContext('2d');
          ctx.imageSmoothingEnabled = true;
          ctx.imageSmoothingQuality = 'high';
          ctx.drawImage(img, sx, sy, sw, sh, 0, 0, targetW, targetH);

          let quality = 0.85;
          let blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', quality));
          while (blob && blob.size > maxBytes && quality > 0.6) {
            quality -= 0.05;
            blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', quality));
          }
          if (!blob) { reject(new Error('Failed to compress')); return; }
          resolve(new File([blob], file.name || 'photo.jpg', { type: 'image/jpeg' }));
        };
        img.onerror = reject;
        img.src = URL.createObjectURL(file);
      });
    },
    async submitForm() {
      if (this.isSubmitting) return;
      this.isSubmitting = true;
      this.serverErrors = [];

      // Build FormData
      const fd = new FormData();
      fd.append('_token', this.csrfToken);

      // Force admission_date to today
      this.form.admission_date = this.todayDate;

      Object.keys(this.form).forEach(k => {
        if (this.form[k] !== null && this.form[k] !== undefined && this.form[k] !== '') {
          fd.append(k, this.form[k]);
        }
      });

      // Add photo if present
      if (this.$refs.photoInput && this.$refs.photoInput.files[0]) {
        fd.append('photo', this.$refs.photoInput.files[0]);
      }

      try {
        const resp = await fetch(this.storeUrl, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: fd,
        });

        if (resp.redirected) {
          window.location.href = resp.url;
          return;
        }

        if (resp.ok) {
          // Follow redirect
          window.location.href = resp.url || this.backUrl;
          return;
        }

        if (resp.status === 422) {
          const data = await resp.json();
          this.serverErrors = Object.values(data.errors || {}).flat();
          // scroll to top to see errors
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          this.serverErrors = ['সার্ভারে সমস্যা হয়েছে। আবার চেষ্টা করুন।'];
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      } catch (err) {
        this.serverErrors = ['নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।'];
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } finally {
        this.isSubmitting = false;
      }
    },
  },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap');

/* === Root Variables for Clean Minimalist Design === */
:root {
  --primary: #4f46e5;
  --primary-hover: #4338ca;
  --success: #10b981;
  --danger: #dc2626;
  --warning: #f59e0b;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --radius: 12px;
  --radius-sm: 6px;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
}

.font-hind {
  font-family: 'Hind Siliguri', 'Inter', -apple-system, sans-serif;
  color: var(--gray-800);
}

.student-create-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 16px 60px;
}

/* Page Header */
.page-header-card {
  background: #ffffff;
  border-radius: var(--radius);
  padding: 24px 32px;
  margin-bottom: 32px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  border-left: 6px solid var(--primary);
}
.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.header-icon-wrap {
  width: 50px;
  height: 50px;
  background: var(--gray-100);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: var(--primary);
}
.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: var(--gray-900);
  letter-spacing: -0.3px;
}
.page-subtitle {
  margin: 4px 0 0;
  font-size: 14px;
  color: var(--gray-500);
}
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #ffffff;
  color: var(--gray-700);
  border-radius: var(--radius-sm);
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  border: 1px solid var(--gray-300);
  transition: all .2s;
}
.btn-back:hover {
  background: var(--gray-50);
  color: var(--gray-900);
  border-color: var(--gray-400);
  text-decoration: none;
}

/* Alerts */
.error-alert {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 20px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: var(--radius-sm);
  margin-bottom: 24px;
}
.error-alert-icon {
  color: var(--danger);
  font-size: 20px;
  margin-top: 2px;
}
.error-alert-content {
  flex: 1;
}
.error-alert-content strong {
  color: var(--danger);
  display: block;
  margin-bottom: 6px;
  font-size: 15px;
}
.error-alert-content ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.error-alert-content li {
  font-size: 14px;
  color: #991b1b;
  padding: 2px 0;
}
.error-alert-content li::before {
  content: '•';
  margin-right: 6px;
  color: var(--danger);
}
.error-close {
  background: none;
  border: none;
  color: #991b1b;
  cursor: pointer;
  font-size: 18px;
  padding: 4px;
}
.success-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: var(--radius-sm);
  margin-bottom: 24px;
  color: #166534;
  font-weight: 600;
  font-size: 15px;
}

/* Grid Layout for Single Page */
.sections-grid {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Minimalist Section Card */
.section-card {
  background: #ffffff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 24px;
  background: #ffffff;
  border-bottom: 1px solid var(--gray-200);
}
.section-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--gray-800);
}
.section-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #ffffff;
}

/* Theme Accents (Top border & Icon background) */
.theme-indigo { border-top: 4px solid #4f46e5; }
.theme-indigo .section-icon { background: #4f46e5; }

.theme-blue { border-top: 4px solid #0ea5e9; }
.theme-blue .section-icon { background: #0ea5e9; }

.theme-emerald { border-top: 4px solid #10b981; }
.theme-emerald .section-icon { background: #10b981; }

.theme-amber { border-top: 4px solid #f59e0b; }
.theme-amber .section-icon { background: #f59e0b; }

.section-body {
  padding: 24px;
  background: #ffffff;
}

/* Form Grid */
.form-grid {
  display: grid;
  gap: 20px;
}
.form-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.form-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 992px) {
  .form-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
  .form-grid.cols-3 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .form-grid.cols-2, .form-grid.cols-3, .form-grid.cols-4 {
    grid-template-columns: 1fr;
  }
}

.form-group {
  margin-bottom: 8px;
}
.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 8px;
}
.form-label i {
  margin-right: 6px;
  color: var(--gray-400);
  font-size: 13px;
}
.form-label.required::after {
  content: ' *';
  color: var(--danger);
  font-weight: 700;
}
/* Enhanced Input Styling for Visibility */
.form-input {
  width: 100%;
  padding: 10px 14px;
  font-size: 15px;
  border: 1px solid #9ca3af !important; /* Darker gray border for better visibility */
  border-radius: var(--radius-sm);
  background-color: #ffffff;
  color: var(--gray-900);
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  outline: none;
  font-family: inherit;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
}
.form-input:focus {
  border-color: #86b7fe;
  background-color: #ffffff;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.form-input::placeholder {
  color: var(--gray-400);
}
.readonly-field {
  background-color: var(--gray-100) !important;
  cursor: not-allowed;
  color: var(--gray-500) !important;
}
.hint-text {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  color: var(--gray-500);
}

/* Dual Input */
.dual-input {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* Divider */
.divider {
  height: 1px;
  background: var(--gray-200);
  margin: 24px 0;
}

/* Address Box */
.address-section {
  background: #ffffff;
  padding: 20px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 2px 0 rgba(0,0,0,.03);
}
.address-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
}
.address-title i {
  margin-right: 8px;
  color: var(--primary);
}
.address-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

/* Checkbox */
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-700);
  cursor: pointer;
  user-select: none;
  background: var(--gray-50);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--gray-200);
  transition: all 0.2s;
}
.checkbox-label:hover {
  background: var(--gray-100);
}
.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--primary);
  cursor: pointer;
}

/* Clear Photo Upload Area */
.photo-and-prev {
  display: flex;
  gap: 40px;
  align-items: flex-start;
}
@media (max-width: 768px) {
  .photo-and-prev {
    flex-direction: column;
    align-items: stretch;
  }
}
.photo-upload-area {
  flex-shrink: 0;
  width: 200px;
  margin: 0 auto;
}
.photo-preview-box {
  width: 100%;
  height: 240px;
  border-radius: 8px;
  border: 2px dashed var(--gray-400);
  background-color: var(--gray-50);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  transition: all .2s;
}
.photo-preview-box:hover {
  border-color: var(--primary);
  background-color: #f5f3ff;
}
.photo-preview-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.empty-photo {
  text-align: center;
  padding: 20px;
  color: var(--gray-500);
}
.empty-photo .upload-icon {
  font-size: 40px;
  color: var(--gray-400);
  margin-bottom: 12px;
}
.empty-photo .upload-text {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
}
.upload-btn-fake {
  display: inline-block;
  padding: 6px 12px;
  background-color: #ffffff;
  border: 1px solid var(--gray-300);
  border-radius: 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-700);
  box-shadow: var(--shadow-sm);
}
.photo-preview-box:hover .empty-photo .upload-icon {
  color: var(--primary);
}
.photo-hint {
  font-size: 12px;
  color: var(--gray-500);
  margin-top: 10px;
  font-weight: 500;
  text-align: center;
}
.hidden-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}
.prev-education {
  flex: 1;
  width: 100%;
}

/* Form Action Footer - Clear Solid Button */
.form-action-footer {
  margin-top: 32px;
  padding: 24px;
  background: #ffffff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  display: flex;
  justify-content: flex-end;
}

.btn-submit-solid {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 32px;
  font-size: 16px;
  font-weight: 600;
  border-radius: var(--radius-sm);
  border: none;
  cursor: pointer;
  background-color: var(--primary);
  color: #ffffff !important;
  box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
  transition: all .2s;
  min-width: 250px;
}
.btn-submit-solid:hover:not(:disabled) {
  background-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3);
}
.btn-submit-solid:disabled {
  opacity: .7;
  cursor: not-allowed;
}

.block { display: block; }
.text-center { text-align: center; }
.mb-3 { margin-bottom: 12px; }
.mt-3 { margin-top: 12px; }
.mt-4 { margin-top: 16px; }

/* Transitions */
.slide-fade-enter-active, .slide-fade-leave-active {
  transition: all .3s ease;
}
.slide-fade-enter-from, .slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
