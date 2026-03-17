<template>
  <div class="fee-collection-container p-6 bg-gray-50 min-h-screen">
    <div class="container-fluid">
      <div class="w-full px-4">
        <h1 class="text-3xl font-bold text-gray-800">স্টেটমেন্ট</h1>
        <p class="text-gray-500 mt-1">শিক্ষার্থীর মাসিক স্টেটমেন্ট দেখুন ও প্রিন্ট করুন</p>

        <div class="mt-6 bg-white p-4 rounded shadow">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Student ID / Code</label>
              <input v-model="student" type="text" placeholder="শিক্ষার্থী আইডি বা কোড" class="mt-1 form-input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Class ID (optional)</label>
              <input v-model="classId" type="text" placeholder="Class ID" class="mt-1 form-input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Month</label>
              <input v-model="month" type="month" class="mt-1 form-input" />
            </div>
          </div>

          <div class="mt-4 space-x-2">
            <button @click="loadStatement" :disabled="loading" class="btn btn-primary">
              <span v-if="loading">লোড হচ্ছে...</span>
              <span v-else>লোড করুন</span>
            </button>
            <button @click="printStatement" :disabled="!hasResult" class="btn btn-secondary">প্রিন্ট</button>
          </div>

          <div v-if="error" class="mt-4 text-red-600">{{ error }}</div>
        </div>

        <div v-if="loading" class="mt-6">লোড হচ্ছে...</div>

        <div v-if="hasResult" class="mt-6 bg-white p-4 rounded shadow">
          <div class="flex justify-between items-start">
            <div>
              <h2 class="text-xl font-semibold">স্টেটমেন্ট — {{ student }}</h2>
              <div class="text-sm text-gray-600">তারিখ: {{ humanDate }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm">Total Due: <strong>{{ formatNumber(statement.total_due) }}</strong></div>
              <div class="text-sm">Paid: <strong>{{ formatNumber(statement.paid) }}</strong></div>
              <div class="text-sm">Outstanding: <strong>{{ formatNumber(statement.outstanding) }}</strong></div>
            </div>
          </div>

          <table class="w-full mt-4 table-auto text-sm">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="px-2 py-1">Date</th>
                <th class="px-2 py-1">Description</th>
                <th class="px-2 py-1">Debit</th>
                <th class="px-2 py-1">Credit</th>
                <th class="px-2 py-1">Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, idx) in statement.lines" :key="idx" class="border-t">
                <td class="px-2 py-2">{{ line.date || '' }}</td>
                <td class="px-2 py-2">{{ line.description || line.title || '-' }}</td>
                <td class="px-2 py-2">{{ formatNumber(line.debit || 0) }}</td>
                <td class="px-2 py-2">{{ formatNumber(line.credit || 0) }}</td>
                <td class="px-2 py-2">{{ formatNumber(line.balance || '') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'FeeStatement',
  props: { academicYearId: { type: [Number, String], default: 0 } },
  data() {
    return {
      student: '',
      classId: '',
      month: new Date().toISOString().substr(0,7),
      loading: false,
      error: null,
      statement: { total_due:0, paid:0, outstanding:0, lines: [] },
    }
  },
  computed: {
    hasResult() { return !!(this.statement && (this.statement.lines && this.statement.lines.length)); },
    humanDate() { return new Date().toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }); }
  },
  mounted(){
    try{
      const params = new URLSearchParams(window.location.search);
      const s = params.get('student');
      const m = params.get('month');
      if(s){ this.student = s; if(m) this.month = m; this.loadStatement(); }
    }catch(e){ console.debug('FeeStatement mounted parse error', e); }
  },
  methods: {
    formatNumber(v){ return Number(v||0).toLocaleString(); },
    async loadStatement(){
      this.error = null;
      if(!this.student){ this.error = 'শিক্ষার্থী আইডি বা কোড আবশ্যক'; return; }
      this.loading = true;
      try{
        const url = `/api/v1/billing/students/${encodeURIComponent(this.student)}/statement`;
        const params = { class_id: this.classId, month: this.month };
        const res = await axios.get(url, { params });
        this.statement = res.data || { total_due:0, paid:0, outstanding:0, lines: [] };
      }catch(err){
        console.error(err);
        this.error = 'স্টেটমেন্ট লোড করতে ত্রুটি হয়েছে';
      }finally{ this.loading = false; }
    },
    printStatement(){
      const content = this.$el.querySelector('.fee-collection-container').innerHTML;
      const w = window.open('', '_blank');
      w.document.write(`<html><head><title>Statement</title><link rel="stylesheet" href="/css/app.css"></head><body>${content}</body></html>`);
      w.document.close();
      w.print();
    }
  }
}
</script>

<style scoped>
.fee-collection-container .card { border-radius: 0.8rem; }
/* Ensure the statement area uses full available width and form controls expand */
.fee-collection-container { max-width: 100% !important; width: 100% !important; }
.fee-collection-container .w-full { max-width: 100% !important; }
.fee-collection-container input, .fee-collection-container select, .fee-collection-container .form-control {
  width: 100% !important;
}
</style>

