<template>
  <div>
    <!-- Seasons View -->
    <div v-if="currentView === 'seasons'" class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">খেলার সিজন/মৌসুম</h3>
        <div class="card-tools">
          <button class="btn btn-primary btn-sm" @click="showAddSeasonModal">
            <i class="fas fa-plus"></i> নতুন সিজন যুক্ত করুন
          </button>
          <a :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/settings'" class="btn btn-info btn-sm ml-2">
            <i class="fas fa-cog"></i> ইভেন্ট সেটিংস
          </a>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3" v-for="season in seasons" :key="season.id">
            <div class="info-box bg-light position-relative" style="cursor: pointer" @click="selectSeason(season)">
              <span class="info-box-icon bg-primary"><i class="fas fa-trophy"></i></span>
              <div class="info-box-content">
                <span class="info-box-text text-lg font-weight-bold">{{ season.name }}</span>
                <span class="info-box-number text-sm font-weight-normal text-muted">বিস্তারিত দেখতে ক্লিক করুন</span>
              </div>
              <button class="btn btn-xs btn-outline-secondary position-absolute" style="top: 8px; right: 8px;" @click.stop="showEditSeasonModal(season)" title="সম্পাদনা">
                <i class="fas fa-edit"></i>
              </button>
            </div>
          </div>
          <div v-if="seasons.length === 0" class="col-12 text-center text-muted py-4">
            কোন সিজন পাওয়া যায়নি। নতুন সিজন যুক্ত করুন।
          </div>
        </div>
      </div>
    </div>

    <!-- Season Events View -->
    <div v-if="currentView === 'events'" class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">
          <button class="btn btn-sm btn-default mr-2" @click="currentView = 'seasons'">
            <i class="fas fa-arrow-left"></i>
          </button>
          ইভেন্ট সমূহ - {{ selectedSeason?.name }}
        </h3>
        <div class="card-tools">
          <a
            v-if="hasSingleEvents"
            :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/ka?season_id=' + selectedSeason.id"
            class="btn btn-success btn-sm mr-2"
            target="_blank"
          >
            <i class="fas fa-print"></i> পরিশিষ্ট-ক (একক ইভেন্ট)
          </a>
          <button class="btn btn-default btn-sm mr-2" @click="showEditSeasonModal(selectedSeason)" title="সিজন সম্পাদনা">
            <i class="fas fa-edit"></i> সিজন সম্পাদনা
          </button>
          <button class="btn btn-primary btn-sm" @click="showAddSeasonEventModal">
            <i class="fas fa-plus"></i> নতুন ইভেন্ট যুক্ত করুন
          </button>
        </div>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th>ইভেন্টের নাম</th>
              <th>সাব-ইভেন্ট</th>
              <th>খেলার ধরণ</th>
              <th>খেলোয়াড় সংখ্যা</th>
              <th>অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="se in seasonEvents" :key="se.id">
              <td>{{ se.event?.name }}</td>
              <td>{{ se.sub_event?.name || '-' }}</td>
              <td>{{ se.event?.type === 'single' ? 'একক' : 'দলীয়' }}</td>
              <td class="text-center">
                <span class="badge badge-info">{{ se.players_count ?? 0 }}</span>
              </td>
              <td>
                <button class="btn btn-info btn-sm mr-1" @click="selectSeasonEvent(se)">
                  <i class="fas fa-users"></i> খেলোয়াড় পরিচালনা
                </button>
                
                <div class="btn-group">
                  <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-print"></i> পরিশিষ্ট
                  </button>
                  <div class="dropdown-menu dropdown-menu-right">
                    <a
                      v-if="se.event?.type === 'team'"
                      class="dropdown-item"
                      :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/ka?season_event_id=' + se.id"
                      target="_blank"
                    >পরিশিষ্ট-ক (খেলোয়াড়দের তালিকা)</a>
                    <a class="dropdown-item" :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/umo?season_event_id=' + se.id" target="_blank">পরিশিষ্ট-ঙ (টিম এন্ট্রি ফর্ম)</a>
                  </div>
                </div>

                <button class="btn btn-danger btn-sm ml-1" @click="deleteSeasonEvent(se.id)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="seasonEvents.length === 0">
              <td colspan="5" class="text-center text-muted">কোন ইভেন্ট পাওয়া যায়নি</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Players View -->
    <div v-if="currentView === 'players'" class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">
          <button class="btn btn-sm btn-default mr-2" @click="currentView = 'events'">
            <i class="fas fa-arrow-left"></i>
          </button>
          খেলোয়াড় তালিকা - 
          {{ selectedSeasonEvent?.event?.name }} 
          <span v-if="selectedSeasonEvent?.sub_event"> ({{ selectedSeasonEvent.sub_event.name }})</span>
        </h3>
        <div class="card-tools">
          <button class="btn btn-primary btn-sm" @click="showAddPlayerModal">
            <i class="fas fa-plus"></i> খেলোয়াড় যুক্ত করুন
          </button>
        </div>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>নাম ও আইডি</th>
              <th>শ্রেণি ও রোল</th>
              <th>গ্রুপ</th>
              <th>উচ্চতা/ওজন</th>
              <th>অধিনায়ক?</th>
              <th>প্রিন্ট</th>
              <th>অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="player in players" :key="player.id">
              <td>
                {{ player.student?.student_name_bn || player.student?.student_name_en }}<br>
                <small class="text-muted">{{ player.student?.student_id }}</small>
              </td>
              <td>
                {{ player.student?.current_enrollment?.class?.name || '-' }}<br>
                <small class="text-muted">রোল: {{ player.student?.current_enrollment?.roll_no || '-' }}</small>
              </td>
              <td>{{ player.group_name || '-' }}</td>
              <td>
                <span v-if="player.height">উ: {{ player.height }}</span><br v-if="player.height">
                <span v-if="player.weight">ও: {{ player.weight }}</span>
              </td>
              <td>
                <span v-if="player.is_captain" class="badge badge-success">হ্যাঁ</span>
                <span v-else class="badge badge-secondary">না</span>
              </td>
              <td>
                <a
                  v-if="selectedSeasonEvent?.event?.type === 'single'"
                  :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/ka?season_id=' + selectedSeason.id + '&student_id=' + player.student_id"
                  class="btn btn-xs btn-outline-success mb-1 d-block"
                  target="_blank"
                >পরিশিষ্ট-ক</a>
                <a :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/kha?season_event_id=' + selectedSeasonEvent.id + '&player_id=' + player.id" class="btn btn-xs btn-outline-primary mb-1 d-block" target="_blank">পরিশিষ্ট-খ</a>
                <a :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/ga?season_event_id=' + selectedSeasonEvent.id + '&player_id=' + player.id" class="btn btn-xs btn-outline-primary mb-1 d-block" target="_blank">পরিশিষ্ট-গ</a>
                <a :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool/appendix/gha?season_event_id=' + selectedSeasonEvent.id + '&player_id=' + player.id" class="btn btn-xs btn-outline-primary d-block" target="_blank">পরিশিষ্ট-ঘ</a>
              </td>
              <td>
                <button class="btn btn-danger btn-sm" @click="deletePlayer(player.id)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="players.length === 0">
              <td colspan="7" class="text-center text-muted">কোন খেলোয়াড় পাওয়া যায়নি</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals -->

    <!-- Add/Edit Season Modal -->
    <div class="modal fade" id="addSeasonModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingSeasonId ? 'সিজন সম্পাদনা' : 'নতুন সিজন যুক্ত করুন' }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>সিজনের নাম</label>
              <input type="text" class="form-control" v-model="newSeason.name" placeholder="যেমন: গ্রীষ্মকালীন প্রতিযোগিতা ২০২৩">
            </div>
            <div class="form-group">
              <label>বয়স গণনার তারিখ</label>
              <input type="date" class="form-control" v-model="newSeason.age_date">
              <small class="text-muted">এই সিজনের সকল ইভেন্টের জন্য এটি ডিফল্ট বয়স গণনার তারিখ হিসেবে ব্যবহৃত হবে।</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
            <button type="button" class="btn btn-primary" @click="saveSeason" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm"></span> সেভ করুন
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Season Event Modal -->
    <div class="modal fade" id="addSeasonEventModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">ইভেন্ট যুক্ত করুন</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>ইভেন্ট নির্বাচন করুন</label>
              <select class="form-control" v-model="newSeasonEvent.interschool_event_id" @change="onEventSelect">
                <option :value="null">-- নির্বাচন করুন --</option>
                <option v-for="ev in eventSettings" :key="ev.id" :value="ev.id">{{ ev.name }} ({{ ev.type === 'single' ? 'একক' : 'দলীয়' }})</option>
              </select>
            </div>
            <div class="form-group" v-if="availableSubEvents.length > 0">
              <label>সাব-ইভেন্ট নির্বাচন করুন</label>
              <div v-for="sub in availableSubEvents" :key="sub.id" class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" :id="'sub_' + sub.id" :value="sub.id" v-model="newSeasonEvent.interschool_sub_event_ids">
                <label class="custom-control-label" :for="'sub_' + sub.id">{{ sub.name }}</label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
            <button type="button" class="btn btn-primary" @click="saveSeasonEvent" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm"></span> সেভ করুন
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Player Modal -->
    <div class="modal fade" id="addPlayerModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">খেলোয়াড় যুক্ত করুন</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-5">
                <div class="form-group">
                  <label>শ্রেণি</label>
                  <select class="form-control" v-model="searchParams.class_id" @change="onClassChange">
                    <option value="">-- নির্বাচন করুন --</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.bangla_name || cls.name }}</option>
                  </select>
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group">
                  <label>শাখা (ঐচ্ছিক)</label>
                  <select class="form-control" v-model="searchParams.section_id" @change="loadStudentList">
                    <option value="">-- সকল শাখা --</option>
                    <option v-for="sec in availableSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2 d-flex align-items-end pb-3">
                <button type="button" class="btn btn-secondary btn-sm btn-block" @click="loadStudentList">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>

            <div class="form-group" v-show="studentList.length > 0">
              <label>শিক্ষার্থী নির্বাচন করুন (রোল - নাম)</label>
              <select id="interschool-student-select" class="form-control" style="width:100%">
                <option value="">-- শিক্ষার্থী নির্বাচন করুন --</option>
              </select>
              <small class="text-muted">ক্লিক করে শিক্ষার্থী নির্বাচন করুন।</small>
            </div>
            <div v-if="searchParams.class_id && studentList.length === 0" class="alert alert-warning py-2">
              <i class="fas fa-spinner fa-spin mr-1" v-if="loadingStudents"></i>
              <span v-if="loadingStudents">লোড হচ্ছে...</span>
              <span v-else>কোনো শিক্ষার্থী পাওয়া যায়নি।</span>
            </div>
            
            <div v-if="newPlayer.student_id" class="alert alert-info py-2">
              <strong>নির্বাচিত:</strong>
              {{ studentList.find(s => s.id === newPlayer.student_id)?.roll_no }} -
              {{ studentList.find(s => s.id === newPlayer.student_id)?.name }}
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>গ্রুপের নাম</label>
                  <input type="text" class="form-control" v-model="newPlayer.group_name" placeholder="যেমন: বড় বালক, ছোট বালিকা">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>উচ্চতা</label>
                  <input type="text" class="form-control" v-model="newPlayer.height" placeholder="যেমন: ৫ ফুট ৬ ইঞ্চি">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>ওজন</label>
                  <input type="text" class="form-control" v-model="newPlayer.weight" placeholder="যেমন: ৫০ কেজি">
                </div>
              </div>
              <div class="col-md-4" v-if="selectedSeasonEvent?.event?.type === 'team'">
                <div class="form-group">
                  <label>অধিনায়ক?</label>
                  <select class="form-control" v-model="newPlayer.is_captain">
                    <option :value="false">না</option>
                    <option :value="true">হ্যাঁ</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>উপস্থিতির দিন</label>
                  <input type="text" class="form-control" v-model="newPlayer.attendance_days" placeholder="যেমন: ১২৮">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
            <button type="button" class="btn btn-primary" @click="savePlayer" :disabled="loading || !newPlayer.student_id">
              <span v-if="loading" class="spinner-border spinner-border-sm"></span> সেভ করুন
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- (Removed Appendix Ka Modal) -->

  </div>
</template>

<script>
import axios from 'axios';
import { debounce } from 'lodash';

export default {
  props: {
    schoolId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      currentView: 'seasons', // seasons, events, players
      loading: false,
      
      seasons: [],
      selectedSeason: null,
      editingSeasonId: null,
      newSeason: { name: '', age_date: '' },
      
      eventSettings: [],
      seasonEvents: [],
      selectedSeasonEvent: null,
      availableSubEvents: [],
      newSeasonEvent: {
        interschool_event_id: null,
        interschool_sub_event_ids: []
      },
      
      classes: [],
      availableSections: [],
      players: [],
      studentList: [],
      loadingStudents: false,
      searchParams: {
        class_id: '',
        section_id: ''
      },
      searchResults: [],
      selectedStudentName: '',
      newPlayer: {
        student_id: null,
        group_name: '',
        height: '',
        weight: '',
        is_captain: false,
        attendance_days: ''
      }
    };
  },
  computed: {
    hasSingleEvents() {
      return this.seasonEvents.some(se => se.event?.type === 'single');
    }
  },
  watch: {
    studentList(newList) {
      this.$nextTick(() => {
        if (window.initStudentSelect2) {
          window.initStudentSelect2(newList, this.newPlayer.student_id);
        }
      });
    }
  },
  created() {
    window.interschoolVue = this;
  },
  mounted() {
    this.fetchSeasons();
    this.fetchEventSettings();
    this.fetchClasses();
  },
  methods: {
    fetchClasses() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/classes`)
        .then(res => this.classes = res.data);
    },
    // --- Seasons ---
    fetchSeasons() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons`)
        .then(res => this.seasons = res.data)
        .catch(err => toastr.error('সিজন লোড করতে সমস্যা হয়েছে।'));
    },
    showAddSeasonModal() {
      this.editingSeasonId = null;
      this.newSeason.name = '';
      this.newSeason.age_date = '';
      $('#addSeasonModal').modal('show');
    },
    showEditSeasonModal(season) {
      this.editingSeasonId = season.id;
      this.newSeason.name = season.name;
      this.newSeason.age_date = season.age_date ? season.age_date.substring(0, 10) : '';
      $('#addSeasonModal').modal('show');
    },
    saveSeason() {
      if (!this.newSeason.name) return toastr.warning('নাম দিন');
      this.loading = true;
      const request = this.editingSeasonId
        ? axios.put(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons/${this.editingSeasonId}`, this.newSeason)
        : axios.post(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons`, this.newSeason);

      request
        .then(res => {
          if (this.editingSeasonId) {
            const index = this.seasons.findIndex(s => s.id === this.editingSeasonId);
            if (index !== -1) {
              this.seasons.splice(index, 1, res.data);
            }
            if (this.selectedSeason?.id === this.editingSeasonId) {
              this.selectedSeason = res.data;
            }
            toastr.success('সিজন আপডেট হয়েছে');
          } else {
            this.seasons.unshift(res.data);
            toastr.success('সিজন যুক্ত হয়েছে');
          }
          $('#addSeasonModal').modal('hide');
        })
        .catch(err => toastr.error('সমস্যা হয়েছে'))
        .finally(() => this.loading = false);
    },
    selectSeason(season) {
      this.selectedSeason = season;
      this.currentView = 'events';
      this.fetchSeasonEvents();
    },

    // --- Season Events ---
    fetchEventSettings() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/events-settings`)
        .then(res => this.eventSettings = res.data);
    },
    fetchSeasonEvents() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons/${this.selectedSeason.id}/events`)
        .then(res => this.seasonEvents = res.data);
    },
    showAddSeasonEventModal() {
      this.newSeasonEvent.interschool_event_id = null;
      this.newSeasonEvent.interschool_sub_event_ids = [];
      this.availableSubEvents = [];
      $('#addSeasonEventModal').modal('show');
    },
    onEventSelect() {
      const ev = this.eventSettings.find(e => e.id === this.newSeasonEvent.interschool_event_id);
      if (ev) {
        this.availableSubEvents = ev.sub_events || [];
      } else {
        this.availableSubEvents = [];
      }
      this.newSeasonEvent.interschool_sub_event_ids = [];
    },
    saveSeasonEvent() {
      if (!this.newSeasonEvent.interschool_event_id) return toastr.warning('ইভেন্ট নির্বাচন করুন');
      this.loading = true;
      axios.post(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons/${this.selectedSeason.id}/events`, this.newSeasonEvent)
        .then(res => {
          this.seasonEvents.push(...res.data);
          $('#addSeasonEventModal').modal('hide');
          toastr.success('যুক্ত হয়েছে');
        })
        .catch(err => toastr.error('সমস্যা হয়েছে'))
        .finally(() => this.loading = false);
    },
    deleteSeasonEvent(id) {
      if (confirm('নিশ্চিত?')) {
        axios.delete(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/seasons/${this.selectedSeason.id}/events/${id}`)
          .then(() => {
            this.seasonEvents = this.seasonEvents.filter(e => e.id !== id);
            toastr.success('ডিলিট হয়েছে');
          });
      }
    },
    selectSeasonEvent(se) {
      this.selectedSeasonEvent = se;
      this.currentView = 'players';
      this.fetchPlayers();
    },

    // --- Players ---
    fetchPlayers() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/season-events/${this.selectedSeasonEvent.id}/players`)
        .then(res => this.players = res.data);
    },
    showAddPlayerModal() {
      this.searchParams = { class_id: '', section_id: '' };
      this.studentList = [];
      this.availableSections = [];
      this.loadingStudents = false;
      this.newPlayer = {
        student_id: null,
        group_name: '',
        height: '',
        weight: '',
        is_captain: false,
        attendance_days: ''
      };
      this.selectedStudentName = '';
      $('#addPlayerModal').modal('show');
    },
    onClassChange() {
      this.searchParams.section_id = '';
      this.studentList = [];
      const cls = this.classes.find(c => c.id === this.searchParams.class_id);
      this.availableSections = cls?.sections || [];
      if (this.searchParams.class_id) {
        this.loadStudentList();
      }
    },
    loadStudentList() {
      if (!this.searchParams.class_id) return;
      this.loadingStudents = true;
      this.studentList = [];
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/search-students`, {
        params: { class_id: this.searchParams.class_id, section_id: this.searchParams.section_id }
      })
        .then(res => {
          this.studentList = res.data;
          this.$nextTick(() => {
            if (window.initStudentSelect2) {
              window.initStudentSelect2(res.data, null);
            }
          });
        })
        .catch(() => toastr.error('শিক্ষার্থী লোড করতে ব্যর্থ হয়েছে।'))
        .finally(() => this.loadingStudents = false);
    },
    searchStudents() {
      // legacy – not used anymore
    },
    selectStudent(student) {
      this.newPlayer.student_id = student.id;
    },
    savePlayer() {
      this.loading = true;
      axios.post(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/season-events/${this.selectedSeasonEvent.id}/players`, this.newPlayer)
        .then(res => {
          this.players.push(res.data);
          $('#addPlayerModal').modal('hide');
          toastr.success('খেলোয়াড় যুক্ত হয়েছে');
        })
        .catch(err => toastr.error('সমস্যা হয়েছে'))
        .finally(() => this.loading = false);
    },
    deletePlayer(id) {
      if (confirm('নিশ্চিত?')) {
        axios.delete(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/season-events/${this.selectedSeasonEvent.id}/players/${id}`)
          .then(() => {
            this.players = this.players.filter(p => p.id !== id);
            toastr.success('ডিলিট হয়েছে');
          });
      }
    },
    // --- Prints ---
    // (Modal methods removed)
  }
};
</script>
