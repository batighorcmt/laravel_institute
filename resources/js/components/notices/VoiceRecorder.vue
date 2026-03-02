<template>
  <div class="voice-recorder d-flex flex-column align-items-center p-3 border rounded bg-light">
    <div class="timer mb-2 h4 font-weight-bold" :class="{'text-danger': recording}">
      {{ formatTime(currentTime) }} / 00:30
    </div>

    <div class="controls d-flex gap-2">
      <button v-if="!recording && !audioUrl" @click="startRecording" class="btn btn-danger rounded-circle p-3" style="width: 60px; height: 60px">
        <i class="fas fa-microphone fa-lg"></i>
      </button>

      <button v-if="recording" @click="stopRecording" class="btn btn-outline-danger rounded-circle p-3 shadow-pulse" style="width: 60px; height: 60px">
        <i class="fas fa-stop fa-lg"></i>
      </button>

      <div v-if="audioUrl" class="d-flex align-items-center">
        <audio :src="audioUrl" controls class="mr-2"></audio>
        <button @click="resetRecorder" class="btn btn-sm btn-link text-danger">রিসেট</button>
      </div>
    </div>

    <div v-if="recording" class="mt-2 small text-muted">রেকর্ডিং হচ্ছে... (সর্বোচ্চ ৩০ সেকেন্ড)</div>
    
    <button v-if="audioUrl" @click="emitSubmit" :disabled="submitting" class="btn btn-success btn-block mt-3">
       <span v-if="submitting" class="spinner-border spinner-border-sm mr-1"></span>
       রিপ্লাই পাঠান
    </button>
  </div>
</template>

<script>
export default {
  props: {
    submitting: Boolean
  },
  data() {
    return {
      recording: false,
      mediaRecorder: null,
      audioChunks: [],
      audioUrl: null,
      audioBlob: null,
      currentTime: 0,
      timer: null
    }
  },
  methods: {
    async startRecording() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        this.mediaRecorder = new MediaRecorder(stream);
        this.audioChunks = [];

        this.mediaRecorder.ondataavailable = (event) => {
          this.audioChunks.push(event.data);
        };

        this.mediaRecorder.onstop = () => {
          this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
          this.audioUrl = URL.createObjectURL(this.audioBlob);
          this.recording = false;
        };

        this.mediaRecorder.start();
        this.recording = true;
        this.startTimer();
      } catch (err) {
        alert('মাইক্রোফোন এক্সেস পাওয়া যায়নি। আপনার ব্রাউজার সেটিংস চেক করুন।');
      }
    },
    stopRecording() {
      if (this.mediaRecorder && this.recording) {
        this.mediaRecorder.stop();
        this.stopTimer();
      }
    },
    startTimer() {
      this.currentTime = 0;
      this.timer = setInterval(() => {
        this.currentTime++;
        if (this.currentTime >= 30) {
          this.stopRecording();
        }
      }, 1000);
    },
    stopTimer() {
      clearInterval(this.timer);
    },
    formatTime(seconds) {
      const s = seconds % 60;
      return `00:${s.toString().padStart(2, '0')}`;
    },
    resetRecorder() {
      this.audioUrl = null;
      this.audioBlob = null;
      this.currentTime = 0;
    },
    emitSubmit() {
      this.$emit('submit', {
        blob: this.audioBlob,
        duration: this.currentTime
      });
    }
  },
  beforeUnmount() {
    this.stopTimer();
  }
}
</script>

<style scoped>
.shadow-pulse {
  animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
  100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
</style>
