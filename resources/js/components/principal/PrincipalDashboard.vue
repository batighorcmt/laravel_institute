<template>
    <div class="pb-10">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <img v-if="overview?.school?.logo" :src="overview.school.logo" class="w-14 h-14 rounded-2xl object-cover border border-slate-200 shadow-sm" alt="logo">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200" v-else>
                    <i class="fas fa-school text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                        {{ overview?.school?.name_bn || overview?.school?.name || 'ড্যাশবোর্ড' }}
                    </h1>
                    <p class="text-slate-500 text-sm font-medium mt-0.5 flex items-center gap-2 flex-wrap">
                        <span><i class="far fa-calendar-alt mr-1"></i>{{ todayLabel }}</span>
                        <span v-if="overview?.academic_year" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full text-xs font-bold">
                            <i class="fas fa-graduation-cap"></i> {{ overview.academic_year.name }}
                        </span>
                    </p>
                </div>
            </div>
            <button @click="loadAll" :disabled="loading" class="self-start lg:self-auto inline-flex items-center gap-2 bg-white border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 text-slate-600 font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all text-sm">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i> রিফ্রেশ
            </button>
        </div>

        <!-- Initial loader -->
        <div v-if="loading && !overview" class="text-center py-24 text-slate-400 font-bold">
            <svg class="animate-spin h-9 w-9 text-indigo-500 mx-auto mb-4" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ড্যাশবোর্ড লোড হচ্ছে...
        </div>

        <div v-else-if="loadError" class="bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl p-6 font-bold text-center">
            <i class="fas fa-triangle-exclamation mr-2"></i>{{ loadError }}
        </div>

        <template v-else>
            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
                <div class="kpi-card">
                    <div class="kpi-icon bg-indigo-50 text-indigo-600"><i class="fas fa-user-graduate"></i></div>
                    <div class="kpi-label">মোট শিক্ষার্থী</div>
                    <div class="kpi-value">{{ bn(counts.students_total) }}</div>
                    <div class="kpi-meta">ছেলে {{ bn(counts.students_male) }} · মেয়ে {{ bn(counts.students_female) }}</div>
                </div>
                <div class="kpi-card cursor-pointer" @click="goTo('teacherAttendance')">
                    <div class="kpi-icon bg-emerald-50 text-emerald-600"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="kpi-label">সক্রিয় শিক্ষক</div>
                    <div class="kpi-value">{{ bn(counts.teachers_active) }}</div>
                    <div class="kpi-meta">বর্তমানে কর্মরত</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon bg-cyan-50 text-cyan-600"><i class="fas fa-id-badge"></i></div>
                    <div class="kpi-label">সক্রিয় কর্মচারী</div>
                    <div class="kpi-value">{{ bn(counts.staff_active) }}</div>
                    <div class="kpi-meta">বর্তমানে কর্মরত</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon bg-amber-50 text-amber-600"><i class="fas fa-layer-group"></i></div>
                    <div class="kpi-label">শ্রেণি ও শাখা</div>
                    <div class="kpi-value">{{ bn(counts.classes_total) }}</div>
                    <div class="kpi-meta">{{ bn(counts.sections_total) }}টি শাখা</div>
                </div>
                <div class="kpi-card cursor-pointer" @click="goTo('attendanceDashboard')">
                    <div class="kpi-icon bg-sky-50 text-sky-600"><i class="fas fa-calendar-check"></i></div>
                    <div class="kpi-label">আজকের শিক্ষার্থী উপস্থিতি</div>
                    <div class="kpi-value">{{ attSummaryFailed ? '—' : bn(attSummary?.class_attendance?.percentage ?? 0) + '%' }}</div>
                    <div class="kpi-meta">
                        <template v-if="attSummaryFailed">লোড করা যায়নি</template>
                        <template v-else>উপস্থিত {{ bn(attSummary?.class_attendance?.present ?? 0) }}/{{ bn(attSummary?.class_attendance?.total ?? 0) }}</template>
                    </div>
                </div>
                <div class="kpi-card cursor-pointer" @click="goTo('teacherAttendance')">
                    <div class="kpi-icon bg-violet-50 text-violet-600"><i class="fas fa-user-clock"></i></div>
                    <div class="kpi-label">আজকের শিক্ষক উপস্থিতি</div>
                    <div class="kpi-value">{{ teacherAttendance?.percentage !== null && teacherAttendance?.percentage !== undefined ? bn(teacherAttendance.percentage) + '%' : '—' }}</div>
                    <div class="kpi-meta">উপস্থিত {{ bn(teacherAttendance?.present ?? 0) }}/{{ bn(teacherAttendance?.total ?? 0) }}</div>
                </div>
                <div v-if="modules.lesson_evaluation" class="kpi-card cursor-pointer" @click="goTo('lessonEvaluations')">
                    <div class="kpi-icon bg-fuchsia-50 text-fuchsia-600"><i class="fas fa-clipboard-check"></i></div>
                    <div class="kpi-label">পাঠ মূল্যায়ন</div>
                    <div class="kpi-value">{{ lessonEvalRate === null ? '—' : bn(lessonEvalRate) + '%' }}</div>
                    <div class="kpi-meta">
                        <template v-if="attSummaryFailed">লোড করা যায়নি</template>
                        <template v-else>সম্পন্ন {{ bn(attSummary?.lesson_evaluation?.completed ?? 0) }}/{{ bn(attSummary?.lesson_evaluation?.total_expected ?? 0) }}</template>
                    </div>
                </div>
                <div v-if="modules.accounts" class="kpi-card">
                    <div class="kpi-icon bg-rose-50 text-rose-600"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="kpi-label">আজকের ফি আদায়</div>
                    <div class="kpi-value text-xl">৳{{ bn(formatNumber(overview?.fees?.today ?? 0)) }}</div>
                    <div class="kpi-meta">মাসিক: ৳{{ bn(formatNumber(overview?.fees?.month ?? 0)) }}</div>
                </div>
                <div v-else class="kpi-card">
                    <div class="kpi-icon bg-teal-50 text-teal-600"><i class="fas fa-child"></i></div>
                    <div class="kpi-label">এক্সট্রা ক্লাস উপস্থিতি</div>
                    <div class="kpi-value">{{ attSummaryFailed ? '—' : bn(attSummary?.extra_class_attendance?.percentage ?? 0) + '%' }}</div>
                    <div class="kpi-meta">
                        <template v-if="attSummaryFailed">লোড করা যায়নি</template>
                        <template v-else>{{ bn(attSummary?.extra_class_attendance?.present ?? 0) }}/{{ bn(attSummary?.extra_class_attendance?.total ?? 0) }}</template>
                    </div>
                </div>
            </div>

            <!-- Main grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                <!-- Left: 2 cols -->
                <div class="xl:col-span-2 space-y-5">
                    <!-- Attendance by class chart -->
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-chart-bar text-sky-500 mr-2"></i>শ্রেণি-শাখা ভিত্তিক উপস্থিতি</h3>
                            <a v-if="links.attendanceDashboard" :href="links.attendanceDashboard" class="panel-link">বিস্তারিত <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                        <div v-if="classWise.length" class="p-4">
                            <div class="relative" style="height: 280px;">
                                <canvas ref="attendanceChartEl"></canvas>
                            </div>
                            <div class="overflow-x-auto mt-4">
                                <table class="mini-table">
                                    <thead>
                                        <tr>
                                            <th class="text-left">শ্রেণি</th>
                                            <th>মোট</th>
                                            <th>উপস্থিত</th>
                                            <th>অনুপস্থিত</th>
                                            <th>হার</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="c in classWise" :key="c.class_id">
                                            <td class="text-left font-bold text-slate-700">{{ c.class_name }}</td>
                                            <td>{{ bn(c.total) }}</td>
                                            <td class="text-emerald-600 font-bold">{{ bn(c.present_total) }}</td>
                                            <td class="text-rose-500 font-bold">{{ bn(c.absent_total) }}</td>
                                            <td>
                                                <span class="rate-pill" :class="ratePillClass(c.percentage)">{{ c.percentage !== null ? bn(c.percentage) + '%' : '—' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="empty-state">
                            {{ attDetailsFailed ? 'উপস্থিতি তথ্য এই মুহূর্তে লোড করা যায়নি' : 'আজকের কোনো উপস্থিতি ডেটা পাওয়া যায়নি' }}
                        </div>
                    </div>

                    <!-- Lesson evaluation today -->
                    <div v-if="modules.lesson_evaluation" class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-clipboard-check text-fuchsia-500 mr-2"></i>আজকের পাঠ মূল্যায়ন</h3>
                            <a v-if="links.lessonEvaluations" :href="links.lessonEvaluations" class="panel-link">সম্পূর্ণ রিপোর্ট <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                        <div v-if="periods.length" class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                            <div v-for="p in periods" :key="p.routine_entry_id" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black shrink-0"
                                     :class="p.evaluated ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400'">
                                    {{ bn(p.period_number) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-slate-800 text-sm truncate">
                                        {{ p.class_name }}{{ p.section_name ? ' - ' + p.section_name : '' }}
                                        <span class="text-slate-400 font-medium">· {{ p.subject_name }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 truncate">
                                        {{ p.teacher_name || 'শিক্ষক নির্ধারিত নেই' }}
                                        <span v-if="p.start_time">· {{ p.start_time }}{{ p.end_time ? ' - ' + p.end_time : '' }}</span>
                                    </div>
                                </div>
                                <span v-if="p.evaluated" class="status-badge status-done">
                                    <i class="fas fa-check-circle"></i> সম্পন্ন
                                </span>
                                <span v-else class="status-badge status-pending">
                                    <i class="fas fa-clock"></i> বাকি
                                </span>
                            </div>
                        </div>
                        <div v-else class="empty-state">
                            {{ lessonEvalFailed ? 'পাঠ মূল্যায়ন তথ্য এই মুহূর্তে লোড করা যায়নি' : 'আজ কোনো রুটিন পিরিয়ড পাওয়া যায়নি' }}
                        </div>
                    </div>

                    <!-- Recent lesson evaluations -->
                    <div v-if="modules.lesson_evaluation && recentEvaluations.length" class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-history text-indigo-500 mr-2"></i>সাম্প্রতিক পাঠ মূল্যায়ন এন্ট্রি</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="mini-table">
                                <thead>
                                    <tr>
                                        <th class="text-left">তারিখ</th>
                                        <th class="text-left">শ্রেণি/শাখা</th>
                                        <th class="text-left">বিষয়</th>
                                        <th class="text-left">শিক্ষক</th>
                                        <th>সম্পন্নের হার</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="e in recentEvaluations" :key="e.id">
                                        <td class="text-left text-slate-500">{{ e.evaluation_date }}</td>
                                        <td class="text-left font-bold text-slate-700">{{ e.class_name }}{{ e.section_name ? ' - ' + e.section_name : '' }}</td>
                                        <td class="text-left">{{ e.subject_name }}</td>
                                        <td class="text-left">{{ e.teacher_name || '—' }}</td>
                                        <td>
                                            <span class="rate-pill" :class="ratePillClass(e.stats?.completion_rate)">{{ bn(e.stats?.completion_rate ?? 0) }}%</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right: 1 col -->
                <div class="space-y-5">
                    <!-- Teacher & staff -->
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-users-cog text-emerald-500 mr-2"></i>শিক্ষক ও কর্মচারী</h3>
                            <a v-if="links.teacherAttendance" :href="links.teacherAttendance" class="panel-link">রিপোর্ট <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-black text-slate-800">{{ bn(counts.teachers_active) }}</div>
                                <div class="text-xs font-bold text-slate-500 mt-0.5">সক্রিয় শিক্ষক</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-black text-slate-800">{{ bn(counts.staff_active) }}</div>
                                <div class="text-xs font-bold text-slate-500 mt-0.5">সক্রিয় কর্মচারী</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-black text-emerald-600">{{ bn(teacherAttendance?.present ?? 0) }}</div>
                                <div class="text-xs font-bold text-slate-500 mt-0.5">শিক্ষক উপস্থিত (আজ)</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-black text-rose-500">{{ bn(teacherAttendance?.absent ?? 0) }}</div>
                                <div class="text-xs font-bold text-slate-500 mt-0.5">শিক্ষক অনুপস্থিত (আজ)</div>
                            </div>
                        </div>
                        <div class="px-5 pb-4 text-xs text-slate-400 font-medium">
                            <i class="fas fa-circle-info mr-1"></i>কর্মচারীর দৈনিক উপস্থিতি এই মুহূর্তে সিস্টেমে ট্র্যাক করা হয় না।
                        </div>
                    </div>

                    <!-- Gender distribution -->
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-venus-mars text-violet-500 mr-2"></i>লিঙ্গভিত্তিক শিক্ষার্থী</h3>
                        </div>
                        <div class="p-5">
                            <div class="relative mx-auto" style="height: 190px; max-width: 220px;">
                                <canvas ref="genderChartEl"></canvas>
                            </div>
                            <div class="flex justify-center gap-6 mt-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-sky-500 inline-block"></span>
                                    <span class="text-slate-600 font-bold">ছেলে {{ bn(counts.students_male) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-pink-500 inline-block"></span>
                                    <span class="text-slate-600 font-bold">মেয়ে {{ bn(counts.students_female) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick links -->
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fas fa-bolt text-amber-500 mr-2"></i>দ্রুত লিংক</h3>
                        </div>
                        <div class="p-3 grid grid-cols-1 gap-1.5">
                            <a v-for="link in quickLinks" :key="link.href" :href="link.href" class="quick-link">
                                <span class="quick-link-icon"><i :class="link.icon"></i></span>
                                <span class="font-bold text-sm text-slate-700">{{ link.label }}</span>
                                <i class="fas fa-chevron-left ml-auto text-slate-300 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    quickLinks: { type: Array, default: () => [] },
    links: { type: Object, default: () => ({}) },
});

const loading = ref(true);
const loadError = ref('');
const overview = ref(null);
const attSummary = ref(null);
const attDetails = ref(null);
const periods = ref([]);
const recentEvaluations = ref([]);
const attSummaryFailed = ref(false);
const attDetailsFailed = ref(false);
const lessonEvalFailed = ref(false);

const attendanceChartEl = ref(null);
const genderChartEl = ref(null);
let attendanceChart = null;
let genderChart = null;

const counts = computed(() => overview.value?.counts || {
    students_total: 0, students_male: 0, students_female: 0,
    teachers_active: 0, staff_active: 0, classes_total: 0, sections_total: 0,
});
const teacherAttendance = computed(() => overview.value?.teacher_attendance || { total: 0, present: 0, absent: 0, percentage: null });
const modules = computed(() => overview.value?.modules || {});
const classWise = computed(() => attDetails.value?.class_wise || []);
const lessonEvalRate = computed(() => {
    if (attSummaryFailed.value) return null;
    const s = attSummary.value?.lesson_evaluation;
    if (!s || !s.total_expected) return 0;
    return Math.round((s.completed / s.total_expected) * 1000) / 10;
});

const todayLabel = new Date().toLocaleDateString('bn-BD', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const bn = (num) => {
    if (num === null || num === undefined || num === '') return '০';
    const digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return num.toString().replace(/\d/g, d => digits[d]);
};

const formatNumber = (n) => {
    const num = Number(n) || 0;
    return num.toLocaleString('en-US', { maximumFractionDigits: 0 });
};

const ratePillClass = (rate) => {
    if (rate === null || rate === undefined) return 'bg-slate-100 text-slate-400';
    if (rate >= 90) return 'bg-emerald-50 text-emerald-600';
    if (rate >= 60) return 'bg-amber-50 text-amber-600';
    return 'bg-rose-50 text-rose-600';
};

const goTo = (key) => {
    if (props.links[key]) window.location.href = props.links[key];
};

const links = props.links;
const quickLinks = props.quickLinks;

const renderCharts = () => {
    if (!window.Chart) return;

    if (attendanceChart) { attendanceChart.destroy(); attendanceChart = null; }
    if (attendanceChartEl.value && classWise.value.length) {
        attendanceChart = new window.Chart(attendanceChartEl.value.getContext('2d'), {
            type: 'bar',
            data: {
                labels: classWise.value.map(c => c.class_name),
                datasets: [
                    { label: 'উপস্থিত', data: classWise.value.map(c => c.present_total), backgroundColor: 'rgba(16,185,129,0.85)', borderRadius: 6 },
                    { label: 'অনুপস্থিত', data: classWise.value.map(c => c.absent_total), backgroundColor: 'rgba(244,63,94,0.75)', borderRadius: 6 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    if (genderChart) { genderChart.destroy(); genderChart = null; }
    if (genderChartEl.value) {
        genderChart = new window.Chart(genderChartEl.value.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['ছেলে', 'মেয়ে'],
                datasets: [{
                    data: [counts.value.students_male, counts.value.students_female],
                    backgroundColor: ['#0ea5e9', '#ec4899'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '68%',
            },
        });
    }
};

const fetchAttendanceSummary = async () => {
    try {
        const res = await axios.get('/api/v1/principal/reports/attendance-summary');
        attSummary.value = res.data.data;
        attSummaryFailed.value = false;
    } catch (e) {
        attSummary.value = null;
        attSummaryFailed.value = true;
    }
};

const fetchAttendanceDetails = async () => {
    try {
        const res = await axios.get('/api/v1/principal/reports/attendance-details');
        attDetails.value = res.data.data;
        attDetailsFailed.value = false;
    } catch (e) {
        attDetails.value = null;
        attDetailsFailed.value = true;
    }
};

const fetchLessonEvaluationData = async () => {
    try {
        const [periodsRes, listRes] = await Promise.all([
            axios.get('/api/v1/principal/reports/lesson-evaluations/periods'),
            axios.get('/api/v1/principal/reports/lesson-evaluations', { params: { per_page: 6 } }),
        ]);
        periods.value = periodsRes.data.items || [];
        recentEvaluations.value = (listRes.data.data || []).slice(0, 6);
        lessonEvalFailed.value = false;
    } catch (e) {
        periods.value = [];
        recentEvaluations.value = [];
        lessonEvalFailed.value = true;
    }
};

const loadAll = async () => {
    loading.value = true;
    loadError.value = '';

    try {
        const overviewRes = await axios.get('/api/v1/principal/reports/dashboard-overview');
        overview.value = overviewRes.data.data;
    } catch (e) {
        loadError.value = e?.response?.data?.message || 'ড্যাশবোর্ড ডেটা লোড করা যায়নি।';
        loading.value = false;
        return;
    }

    const tasks = [fetchAttendanceSummary(), fetchAttendanceDetails()];
    if (overview.value.modules?.lesson_evaluation) {
        tasks.push(fetchLessonEvaluationData());
    }
    await Promise.allSettled(tasks);

    await nextTick();
    renderCharts();
    loading.value = false;
};

onMounted(loadAll);
</script>

<style scoped>
.kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    padding: 1.1rem;
    transition: box-shadow .2s ease, transform .15s ease;
}
.kpi-card:hover {
    box-shadow: 0 10px 24px -8px rgba(15, 23, 42, 0.12);
    transform: translateY(-2px);
}
.kpi-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
}
.kpi-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.kpi-value {
    font-size: 1.6rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.3;
}
.kpi-meta {
    font-size: 0.72rem;
    color: #94a3b8;
    font-weight: 700;
    margin-top: 0.15rem;
}

.panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    overflow: hidden;
}
.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.panel-head h3 {
    font-size: 0.95rem;
    font-weight: 900;
    color: #1e293b;
    margin: 0;
}
.panel-link {
    font-size: 0.78rem;
    font-weight: 800;
    color: #6366f1;
    white-space: nowrap;
}
.panel-link:hover { color: #4338ca; }

.empty-state {
    padding: 2.5rem 1rem;
    text-align: center;
    color: #94a3b8;
    font-weight: 700;
    font-size: 0.85rem;
}

.mini-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}
.mini-table th {
    color: #94a3b8;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.65rem;
    letter-spacing: .04em;
    padding: 0.5rem 0.6rem;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
}
.mini-table td {
    padding: 0.55rem 0.6rem;
    text-align: center;
    border-bottom: 1px solid #f8fafc;
}

.rate-pill {
    display: inline-block;
    padding: 0.15rem 0.6rem;
    border-radius: 999px;
    font-weight: 900;
    font-size: 0.75rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    white-space: nowrap;
}
.status-done { background: #ecfdf5; color: #059669; }
.status-pending { background: #fef2f2; color: #e11d48; }

.quick-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 0.7rem;
    border-radius: 0.85rem;
    text-decoration: none;
    transition: background .15s ease;
}
.quick-link:hover { background: #f8fafc; }
.quick-link-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.65rem;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}
</style>
