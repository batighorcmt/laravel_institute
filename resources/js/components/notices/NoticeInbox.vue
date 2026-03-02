<template>
  <div class="notice-inbox">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h4 mb-0">আমার নোটিশ</h2>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="notices.length === 0" class="text-center py-5 bg-white rounded">
      <i class="fas fa-envelope-open text-muted fa-3x mb-3"></i>
      <p class="text-muted">কোনও নতুন নোটিশ নেই</p>
    </div>

    <div v-else class="notice-list">
      <div v-for="notice in notices" :key="notice.id" 
           class="card mb-3 border-left-info shadow-sm hover-shadow transition"
           :class="{'bg-unread': !notice.is_read}">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div @click="viewDetails(notice)" class="cursor-pointer flex-grow-1">
            <h5 class="card-title text-primary mb-1">
              {{ notice.title }}
              <span v-if="!notice.is_read" class="badge badge-danger ml-2 font-weight-normal">নতুন</span>
            </h5>
            <small class="text-muted">
              <i class="far fa-calendar-alt mr-1"></i> {{ formatDate(notice.publish_at) }} 
              <i class="far fa-clock ml-2 mr-1"></i> {{ formatTime(notice.publish_at) }}
            </small>
          </div>
          <button @click="viewDetails(notice)" class="btn btn-sm btn-outline-primary ml-3 px-3">
             বিস্তারিত দেখুন
          </button>
        </div>
      </div>
    </div>

    <!-- Detailed View Modal -->
    <div v-if="selectedNotice" class="modal d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title font-weight-bold">নোটিশ বিস্তারিত</h5>
            <button @click="selectedNotice = null" class="btn text-white">&times;</button>
          </div>
          <div class="modal-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
               <div>
                  <h3 class="h4 text-primary font-weight-bold mb-1">{{ selectedNotice.title }}</h3>
                  <div class="text-muted small">
                     <i class="far fa-calendar-alt mr-1"></i> প্রকাশিত: {{ formatDate(selectedNotice.publish_at) }} 
                     <i class="far fa-clock ml-2 mr-1"></i> {{ formatTime(selectedNotice.publish_at) }}
                  </div>
               </div>
               <span class="badge" :class="selectedNotice.is_read ? 'badge-light border' : 'badge-primary'">
                  {{ selectedNotice.is_read ? 'পড়া হয়েছে' : 'নতুন' }}
               </span>
            </div>
            
            <div class="notice-content py-3 mb-4" v-html="formattedBody(selectedNotice.body)"></div>

            <!-- Voice Reply Section -->
            <div v-if="selectedNotice.reply_required" class="reply-section mt-4 p-4 bg-light rounded border-success border-left-strong">
              <h5 class="h6 mb-3 text-success font-weight-bold">
                <i class="fas fa-microphone mr-2"></i> এই নোটিশের জন্য একটি ভয়েস রিপ্লাই দিন:
              </h5>
              <VoiceRecorder @submit="handleReplySubmit" :disabled="replying" />
              <div v-if="replying" class="text-success small mt-3 font-italic">
                <span class="spinner-border spinner-border-sm mr-1"></span> রিপ্লাই পাঠানো হচ্ছে...
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light">
             <button @click="selectedNotice = null" class="btn btn-secondary px-4">বন্ধ করুন</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import VoiceRecorder from './VoiceRecorder.vue';

export default {
  components: { VoiceRecorder },
  props: {
    schoolId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      notices: [],
      loading: false,
      selectedNotice: null,
      replying: false,
    }
  },
  mounted() {
    this.fetchNotices();
  },
  methods: {
    async fetchNotices() {
      this.loading = true;
      try {
        const res = await axios.get('/api/v1/notices', {
          params: { school_id: this.schoolId }
        });
        this.notices = res.data.data;
      } catch (err) {
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    async viewDetails(notice) {
        this.selectedNotice = notice;
        if (!notice.is_read) {
            this.markAsRead(notice);
        }
    },
    async markAsRead(notice) {
      try {
        await axios.post(`/api/v1/notices/${notice.id}/read`, {
          school_id: this.schoolId
        });
        notice.is_read = true;
      } catch (err) {
        console.error(err);
      }
    },
    async handleReplySubmit({ blob, duration }) {
      this.replying = true;
      const formData = new FormData();
      formData.append('voice', blob, 'reply.webm');
      formData.append('duration', duration);
      formData.append('school_id', this.schoolId);

      try {
        await axios.post(`/api/v1/notices/${this.selectedNotice.id}/reply`, formData);
        alert('আপনার রিপ্লাই সফলভাবে পাঠানো হয়েছে।');
        this.selectedNotice = null;
        this.fetchNotices();
      } catch (err) {
        alert('সমস্যা হয়েছে: ' + (err.response?.data?.message || err.message));
      } finally {
        this.replying = false;
      }
    },
    formattedBody(body) {
      if (!body) return '';
      return body.replace(/\n/g, '<br>');
    },
    formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('bn-BD');
    },
    formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleTimeString('bn-BD', { hour: '2-digit', minute: '2-digit' });
    }
  }
}
</script>

<style scoped>
.notice-inbox { min-height: 500px; }
.transition { transition: all 0.2s ease-in-out; }
.hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important; }
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: rgba(0,0,0,0.02); }
.border-left-info { border-left: 5px solid #36b9cc !important; }
.bg-unread { background-color: #fdfdfe; border-left: 5px solid #e74a3b !important; }
.notice-content { font-size: 1.1rem; line-height: 1.7; white-space: pre-line; }
.border-left-strong { border-left: 4px solid #1cc88a !important; }
</style>
