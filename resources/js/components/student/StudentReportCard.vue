<template>
  <div class="report-card p-4 bg-white shadow-sm">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h4 class="mb-0">{{ student.name }}</h4>
        <div class="text-muted">ID: {{ student.student_id }}</div>
        <div class="text-muted">{{ enrollment.class }} - {{ enrollment.section }} | Roll: {{ enrollment.roll_no }}</div>
      </div>
      <div class="no-print">
        <button class="btn btn-sm btn-outline-secondary mr-2" @click="window.print()">Print</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else>
      <div v-if="Object.keys(marks).length === 0" class="alert alert-info">No marks available.</div>

      <div v-for="(subjects, examId) in marks" :key="examId" class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Exam: {{ examLabel(examId) }}</h5>
          <div class="small text-muted">Total Subjects: {{ subjects.length }}</div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Subject</th>
                <th class="text-right">Creative</th>
                <th class="text-right">MCQ</th>
                <th class="text-right">Practical</th>
                <th class="text-right">Total</th>
                <th class="text-right">Grade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(s, idx) in subjects" :key="idx">
                <td>{{ s.subject }}</td>
                <td class="text-right">{{ formatNum(s.creative_marks) }}</td>
                <td class="text-right">{{ formatNum(s.mcq_marks) }}</td>
                <td class="text-right">{{ formatNum(s.practical_marks) }}</td>
                <td class="text-right font-weight-bold">{{ formatNum(s.total_marks) }}</td>
                <td class="text-right">{{ s.letter_grade || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  name: 'StudentReportCard',
  props: [],
  setup(props, { attrs }) {
    const dataUrl = attrs['data-url'] || attrs['dataUrl'] || '';
    const loading = ref(true);
    const student = ref({});
    const enrollment = ref({});
    const marks = ref({});

    const load = async () => {
      if (!dataUrl) return;
      loading.value = true;
      try {
        const resp = await fetch(dataUrl, { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('Network');
        const json = await resp.json();
        student.value = json.student || {};
        enrollment.value = json.enrollment || {};
        marks.value = json.marks || {};
      } catch (e) {
        console.error('Failed to load report card:', e);
      } finally {
        loading.value = false;
      }
    };

    const formatNum = (v) => (v === null || v === undefined ? '-' : Number(v).toFixed(2));
    const examLabel = (examId) => (examId ? `#${examId}` : 'Exam');

    onMounted(load);

    return { loading, student, enrollment, marks, formatNum, examLabel };
  }
};
</script>

<style scoped>
  .report-card { max-width: 980px; margin: 0 auto; }
  @media (min-width: 768px) {
    .report-card { padding: 2rem; border-radius: .5rem; }
  }
</style>
