<template>
  <div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-extrabold">কালেকশন রিপোর্ট</h1>
        <p class="text-sm text-slate-500">তারিখ, শ্রেণি, সেকশন, ফি ক্যাটেগরি ও মাস দিয়ে পরিশোধকৃত শিক্ষার্থীদের তালিকা দেখুন</p>
      </div>
      <div class="text-sm text-slate-400">পেজ লোড: {{ new Date().toLocaleString('bn-BD') }}</div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-400">From</label>
          <input type="date" v-model="filters.from_date" class="form-input mt-1" />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-400">To</label>
          <input type="date" v-model="filters.to_date" class="form-input mt-1" />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-400">Class</label>
          <select v-model="filters.class_id" @change="loadSections" class="form-input mt-1">
            <option value="">All</option>
            <option v-for="c in meta.classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-400">Section</label>
          <select v-model="filters.section_id" :disabled="!filters.class_id" class="form-input mt-1">
            <option value="">All</option>
            <option v-for="s in meta.sections" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-400">Fee Category</label>
          <select v-model="filters.fee_category_id" class="form-input mt-1">
            <option value="">All</option>
            <option v-for="cat in meta.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-400">Month</label>
          <input type="month" v-model="filters.month" class="form-input mt-1" />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3 mt-4">
        <button @click="fetch" :disabled="loading" class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 disabled:opacity-60">
          <span v-if="loading">লোড হচ্ছে...</span>
          <span v-else>Search</span>
        </button>
        <button @click="exportCSV" :disabled="!results.length" class="px-4 py-2 bg-white border rounded-lg text-sm hover:bg-slate-50 disabled:opacity-50">Export CSV</button>
        <button @click="printReport" :disabled="!results.length" class="px-4 py-2 bg-white border border-indigo-200 rounded-lg text-sm text-indigo-600 hover:bg-indigo-50 disabled:opacity-50">
          <i class="fas fa-print mr-1"></i> Print
        </button>
        <div class="ml-auto text-sm text-slate-500">Results: <strong class="text-slate-800">{{ totalResults }}</strong> · মোট আদায়: <strong class="text-indigo-600">৳ {{ formatNumber(totalCollected) }}</strong></div>
      </div>
    </div>

    <div v-if="loading" class="py-12">
      <div class="animate-pulse space-y-3">
        <div class="h-6 bg-slate-100 rounded w-1/3"></div>
        <div class="h-48 bg-slate-100 rounded"></div>
      </div>
    </div>

    <div v-else>
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold">Paid Students</h2>
          <div class="text-sm text-slate-400">Showing {{ pageStart }}–{{ pageEnd }} of {{ totalResults }}</div>
        </div>

        <div v-if="!results.length" class="py-12 text-center text-slate-400 italic">কোন ফলাফল পাওয়া যায়নি — বিভিন্ন ফিল্টার পরিবর্তন করে পুনরায় চেষ্টা করুন</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left table-auto">
            <thead>
              <tr class="text-slate-500 text-[12px] uppercase tracking-wider">
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Class</th>
                <th class="px-4 py-3">Section</th>
                <th class="px-4 py-3">Roll</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Month</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3">Paid At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in pagedResults" :key="r.payment_id + '-' + idx" class="border-t">
                <td class="px-4 py-3">{{ pageStart + idx }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ r.student_id || '-' }}</td>
                <td class="px-4 py-3 font-semibold">
                  <a v-if="r.payment_id" :href="'/billing/receipts/' + r.payment_id" target="_blank" class="text-indigo-600 hover:underline">
                    {{ r.student_name_bn || r.student_name_en || r.student_name || '-' }}
                  </a>
                  <span v-else>
                    {{ r.student_name_bn || r.student_name_en || r.student_name || '-' }}
                  </span>
                </td>
                <td class="px-4 py-3">{{ r.class_name_bn || r.class_name_en || r.class_name || '-' }}</td>
                <td class="px-4 py-3">{{ r.section_name_bn || r.section_name_en || r.section_name || '-' }}</td>
                <td class="px-4 py-3">{{ r.roll_no ?? '-' }}</td>
                <td class="px-4 py-3">{{ r.category_name_bn || r.category_name_en || r.category_name || 'General' }}</td>
                <td class="px-4 py-3">{{ r.fee_month ? formatFeeMonth(r.fee_month) : (r.paid_at ? formatMonth(r.paid_at) : '-') }}</td>
                <td class="px-4 py-3 text-right font-black num">৳ {{ formatNumber(r.amount) }}</td>
                <td class="px-4 py-3 text-sm text-slate-600 num">{{ r.paid_at ? formatDateTime(r.paid_at) : '-' }}</td>
              </tr>
            </tbody>
          </table>

          <div class="flex items-center justify-between mt-4">
            <div class="text-sm text-slate-500">Page {{ page }} / {{ totalPages }}</div>
            <div class="flex items-center gap-2">
              <button @click="prevPage" :disabled="page <= 1" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
              <button @click="nextPage" :disabled="page >= totalPages" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CollectionReport',
  data(){
    return {
      loading: false,
      filters: {
        from_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substr(0,10),
        to_date: new Date().toISOString().substr(0,10),
        class_id: '',
        section_id: '',
        fee_category_id: '',
        month: ''
      },
      meta: { classes: [], sections: [], categories: [] },
      school: {},
      results: [],
      // pagination
      page: 1,
      perPage: 25
    }
  },
  mounted(){ this.loadMeta(); },
  computed: {
    totalResults() { return this.results.length; },
    totalCollected() { return this.results.reduce((s, r) => s + (Number(r.amount) || 0), 0); },
    totalPages() { return Math.max(1, Math.ceil(this.results.length / this.perPage)); },
    pagedResults() {
      const start = (this.page - 1) * this.perPage;
      return this.results.slice(start, start + this.perPage);
    },
    pageStart() { return this.totalResults === 0 ? 0 : (this.page - 1) * this.perPage + 1; },
    pageEnd() { return Math.min(this.totalResults, this.page * this.perPage); }
  },
  methods: {
    loadMeta(){
      axios.get('/api/v1/meta/classes').then(r=> this.meta.classes = r.data).catch(()=>{});
      axios.get('/api/v1/billing/config').then(r=> { this.meta.categories = r.data.categories || []; }).catch(()=>{});
      axios.get('/api/v1/meta/school').then(r=> this.school = r.data).catch(()=>{});
    },
    loadSections(){
      if(!this.filters.class_id){ this.meta.sections = []; this.filters.section_id = ''; return; }
      axios.get('/api/v1/meta/sections', { params: { class_id: this.filters.class_id } }).then(r=> this.meta.sections = r.data).catch(()=> this.meta.sections = []);
    },
    async fetch(){
      this.loading = true; this.results = []; this.page = 1;
      try{
        const params = { ...this.filters };
        const res = await axios.get('/api/v1/billing/reports/collection-paid-students', { params });
        // Ensure we always keep an array
        if (Array.isArray(res.data)) {
          this.results = res.data;
        } else {
          console.warn('Unexpected report response', res.data);
          this.results = [];
          const msg = res.data?.message || 'রিপোর্ট লোডে সমস্যা হয়েছে';
          if (window.toastr) toastr.error(msg);
        }
      }catch(e){
        console.error(e);
        const serverMsg = e?.response?.data?.message;
        if (window.toastr) toastr.error(serverMsg || 'রিপোর্ট লোড করতে ব্যর্থ');
      }finally{ this.loading = false; }
    },
    printReport() {
      if (!this.results.length) return;

      const printWindow = window.open('', '_blank');
      const html = `
        <html>
          <head>
            <title>কালেকশন রিপোর্ট</title>
            <style>
              @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;700&display=swap');
              @font-face {
                font-family: 'Kalpurush';
                src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2');
              }
              body { font-family: 'Hind Siliguri', sans-serif; padding: 15px; color: #333; line-height: 1.2; }
              .header-container { display: flex; align-items: center; justify-content: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
              .school-logo { max-height: 80px; margin-right: 20px; }
              .header-text { text-align: left; }
              .header-text h1 { margin: 0; color: #1e293b; font-size: 24px; font-weight: 700; }
              .header-text .address { margin: 2px 0; color: #475569; font-size: 13px; }
              .report-info { text-align: center; margin-bottom: 15px; }
              .report-info .report-title { font-size: 18px; font-weight: bold; color: #4f46e5; margin: 0; }
              .report-info p { margin: 2px 0; font-size: 14px; color: #64748b; }
              .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; font-size: 12px; }
              table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 12px; }
              th { background: #f8fafc; color: #475569; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px; border: 1px solid #e2e8f0; text-align: left; }
              td { padding: 6px 8px; border: 1px solid #e2e8f0; }
              .num { font-family: 'Kalpurush', 'Hind Siliguri', sans-serif; }
              tr:nth-child(even) { background: #fdfdfd; }
              .text-right { text-align: right; }
              .footer { margin-top: 20px; text-align: right; font-size: 11px; color: #94a3b8; }
              .totals { margin-top: 10px; padding: 8px; background: #f1f5f9; border-radius: 4px; font-weight: bold; text-align: right; font-size: 14px; }
              @media print {
                button { display: none; }
                body { padding: 0; }
                .header-container { border-bottom-color: #333; }
              }
            </style>
          </head>
          <body>
            <div class="header-container">
              ${this.school.logo_url ? `<img src="${this.school.logo_url}" class="school-logo">` : ''}
              <div class="header-text">
                <h1>${this.school.name_bn || this.school.name || 'শিক্ষাপ্রতিষ্ঠানের নাম'}</h1>
                <div class="address">${this.school.address_bn || this.school.address || ''}</div>
                <div class="address">ফোন: <span class="num">${this.school.phone || ''}</span> | ইমেইল: ${this.school.email || ''}</div>
              </div>
            </div>

            <div class="report-info">
              <div class="report-title">কালেকশন রিপোর্ট</div>
              <p>তারিখ: <span class="num">${this.filters.from_date}</span> হতে <span class="num">${this.filters.to_date}</span></p>
            </div>

            <div class="meta-grid">
              <div>মোট রেকর্ড: <span class="num">${this.totalResults}</span></div>
              <div class="text-right">রিপোর্ট তৈরির সময়: <span class="num">${new Date().toLocaleString('bn-BD')}</span></div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>ID</th>
                  <th>শিক্ষার্থী</th>
                  <th>শ্রেণি/শাখা</th>
                  <th>ক্যাটাগরি</th>
                  <th>মাস</th>
                  <th class="text-right">পরিমাণ</th>
                  <th>তারিখ</th>
                </tr>
              </thead>
              <tbody>
                ${this.results.map((r, i) => `
                  <tr>
                    <td class="num">${i + 1}</td>
                    <td class="num">${r.student_id || '-'}</td>
                    <td>${r.student_name_bn || r.student_name_en || '-'}</td>
                    <td>${r.class_name_bn || r.class_name_en || '-'}${r.section_name_bn ? ' ('+r.section_name_bn+')' : ''}</td>
                    <td>${r.category_name_bn || r.category_name_en || 'সাধারণ'}</td>
                    <td class="num">${r.fee_month ? this.formatFeeMonth(r.fee_month) : (r.paid_at ? this.formatMonth(r.paid_at) : '-')}</td>
                    <td class="text-right num">৳ ${this.formatNumber(r.amount)}</td>
                    <td class="num">${r.paid_at ? this.formatDateTime(r.paid_at) : '-'}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>

            <div class="totals num">
              মোট সংগৃহীত টাকা: ৳ ${this.formatNumber(this.totalCollected)}
            </div>

            <div class="footer">
              Managed by Batighor EIMS
            </div>

            <script>
              window.onload = function() { window.print(); window.close(); };
            <\/script>
          </body>
        </html>
      `;
      printWindow.document.write(html);
      printWindow.document.close();
    },
    exportCSV(){
      if(!this.results.length) return;
      const rows = this.results.map(r => [
        r.student_id || '',
        (r.student_name_bn || r.student_name_en || r.student_name || ''),
        (r.class_name_bn || r.class_name_en || r.class_name || ''),
        (r.section_name_bn || r.section_name_en || r.section_name || ''),
        (r.roll_no ?? ''),
        (r.category_name_bn || r.category_name_en || r.category_name || ''),
        (r.paid_at ? new Date(r.paid_at).toLocaleString('bn-BD', { month: 'long', year: 'numeric' }) : ''),
        (r.amount || ''),
        (r.paid_at || '')
      ]);
      const header = ['Student ID','Name','Class','Section','Roll','Category','Month','Amount','Paid At'];
      const csv = [header, ...rows].map(r=> r.map(c => '"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
      const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href = url; a.download = `collection_paid_${Date.now()}.csv`; a.click(); URL.revokeObjectURL(url);
    },
    formatDateTime(dt){
      try{ return new Date(dt).toLocaleString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }catch(e){ return dt; }
    },
    formatMonth(dt){
      try{ return new Date(dt).toLocaleString('bn-BD', { month: 'long', year: 'numeric' }); }catch(e){ return dt; }
    },
    formatFeeMonth(val){
      if(!val) return '-';
      try {
        // val is YYYY-MM
        const [y, m] = val.split('-');
        const date = new Date(parseInt(y), parseInt(m)-1, 1);
        return date.toLocaleString('bn-BD', { month: 'long', year: 'numeric' });
      } catch(e) { return val; }
    },
    formatNumber(v){ return Number(v||0).toLocaleString('bn-BD'); },
    formatDate(dt){
      try{ return new Date(dt).toLocaleString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric' }); }catch(e){ return dt; }
    },
    nextPage(){ if(this.page < this.totalPages) this.page++; },
    prevPage(){ if(this.page > 1) this.page--; }
  }
}
</script>

<style scoped>
@font-face {
  font-family: 'Kalpurush';
  src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2');
}
.num { font-family: 'Kalpurush', 'Hind Siliguri', sans-serif !important; }

.form-input { width:100%; padding:0.5rem; border:1px solid #e5e7eb; border-radius:0.5rem }
.btn { padding:0.5rem 1rem; border-radius:0.5rem }
.btn-primary { background:#4f46e5; color:#fff }
.btn-secondary { background:#efefef }

/* Pagination helpers */
.page-controls button[disabled] { opacity: 0.45 }
</style>
