<template>
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">ইভেন্ট এবং সাব-ইভেন্ট সেটিংস</h3>
      <div class="card-tools">
        <button class="btn btn-primary btn-sm" @click="showAddEventModal">
          <i class="fas fa-plus"></i> নতুন ইভেন্ট যুক্ত করুন
        </button>
        <a :href="'/principal/institute/' + schoolId + '/game-and-sports/interschool'" class="btn btn-default btn-sm ml-2">
          <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-12">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ইভেন্টের নাম</th>
                <th>ধরণ (একক/দলীয়)</th>
                <th>সাব-ইভেন্ট সমূহ</th>
                <th width="200">অ্যাকশন</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="event in events" :key="event.id">
                <td>{{ event.name }}</td>
                <td>{{ event.type === 'single' ? 'একক' : 'দলীয়' }}</td>
                <td>
                  <ul class="mb-0 pl-3">
                    <li v-for="sub in event.sub_events" :key="sub.id">
                      {{ sub.name }}
                      <a href="#" class="text-primary ml-2" @click.prevent="showEditSubEventModal(sub)" title="সম্পাদনা"><i class="fas fa-edit"></i></a>
                      <a href="#" class="text-danger ml-2" @click.prevent="deleteSubEvent(sub.id)" title="Delete"><i class="fas fa-times"></i></a>
                    </li>
                  </ul>
                  <button class="btn btn-xs btn-outline-success mt-2" @click="showAddSubEventModal(event.id)">
                    <i class="fas fa-plus"></i> সাব-ইভেন্ট যুক্ত করুন
                  </button>
                </td>
                <td>
                  <button class="btn btn-xs btn-outline-primary mr-1" @click="showEditEventModal(event)" title="সম্পাদনা">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-danger btn-sm" @click="deleteEvent(event.id)">
                    <i class="fas fa-trash"></i> ডিলিট
                  </button>
                </td>
              </tr>
              <tr v-if="events.length === 0">
                <td colspan="4" class="text-center text-muted">কোন ইভেন্ট পাওয়া যায়নি</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingEventId ? 'ইভেন্ট সম্পাদনা' : 'নতুন ইভেন্ট যুক্ত করুন' }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>ইভেন্টের নাম</label>
              <input type="text" class="form-control" v-model="newEvent.name" placeholder="যেমন: দৌড়, সাঁতার, ফুটবল">
            </div>
            <div class="form-group">
              <label>খেলার ধরণ</label>
              <select class="form-control" v-model="newEvent.type" :disabled="!!editingEventId">
                <option value="single">একক খেলা</option>
                <option value="team">দলীয় খেলা</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
            <button type="button" class="btn btn-primary" @click="saveEvent" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm"></span> সেভ করুন
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Sub Event Modal -->
    <div class="modal fade" id="addSubEventModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingSubEventId ? 'সাব-ইভেন্ট সম্পাদনা' : 'সাব-ইভেন্ট যুক্ত করুন' }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>সাব-ইভেন্টের নাম</label>
              <input type="text" class="form-control" v-model="newSubEvent.name" placeholder="যেমন: ১০০ মিটার, ২০০ মিটার">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
            <button type="button" class="btn btn-primary" @click="saveSubEvent" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm"></span> সেভ করুন
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    schoolId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      events: [],
      editingEventId: null,
      editingSubEventId: null,
      newEvent: {
        name: '',
        type: 'single'
      },
      newSubEvent: {
        interschool_event_id: null,
        name: ''
      },
      loading: false
    };
  },
  mounted() {
    this.fetchEvents();
  },
  methods: {
    fetchEvents() {
      axios.get(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/events-settings`)
        .then(response => {
          this.events = response.data;
        })
        .catch(error => {
          console.error(error);
          toastr.error('ডাটা লোড করতে সমস্যা হয়েছে।');
        });
    },
    showAddEventModal() {
      this.editingEventId = null;
      this.newEvent.name = '';
      this.newEvent.type = 'single';
      $('#addEventModal').modal('show');
    },
    showEditEventModal(event) {
      this.editingEventId = event.id;
      this.newEvent.name = event.name;
      this.newEvent.type = event.type;
      $('#addEventModal').modal('show');
    },
    saveEvent() {
      if (!this.newEvent.name) {
        toastr.warning('ইভেন্টের নাম দিন');
        return;
      }
      this.loading = true;
      const payload = this.editingEventId
        ? { name: this.newEvent.name }
        : this.newEvent;

      const request = this.editingEventId
        ? axios.put(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/events-settings/${this.editingEventId}`, payload)
        : axios.post(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/events-settings`, payload);

      request
        .then(response => {
          if (this.editingEventId) {
            const index = this.events.findIndex(e => e.id === this.editingEventId);
            if (index !== -1) {
              this.events.splice(index, 1, response.data);
            }
            toastr.success('ইভেন্ট আপডেট হয়েছে');
          } else {
            this.events.push(response.data);
            toastr.success('ইভেন্ট যুক্ত হয়েছে');
          }
          $('#addEventModal').modal('hide');
        })
        .catch(error => {
          console.error(error);
          toastr.error('ইভেন্ট সেভ করতে সমস্যা হয়েছে।');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    deleteEvent(id) {
      if (confirm('আপনি কি নিশ্চিত?')) {
        axios.delete(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/events-settings/${id}`)
          .then(() => {
            this.events = this.events.filter(e => e.id !== id);
            toastr.success('ইভেন্ট ডিলিট হয়েছে');
          })
          .catch(() => toastr.error('ডিলিট করতে সমস্যা হয়েছে।'));
      }
    },
    showAddSubEventModal(eventId) {
      this.editingSubEventId = null;
      this.newSubEvent.interschool_event_id = eventId;
      this.newSubEvent.name = '';
      $('#addSubEventModal').modal('show');
    },
    showEditSubEventModal(subEvent) {
      this.editingSubEventId = subEvent.id;
      this.newSubEvent.interschool_event_id = subEvent.interschool_event_id;
      this.newSubEvent.name = subEvent.name;
      $('#addSubEventModal').modal('show');
    },
    saveSubEvent() {
      if (!this.newSubEvent.name) {
        toastr.warning('সাব-ইভেন্টের নাম দিন');
        return;
      }
      this.loading = true;

      const request = this.editingSubEventId
        ? axios.put(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/sub-events-settings/${this.editingSubEventId}`, { name: this.newSubEvent.name })
        : axios.post(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/sub-events-settings`, this.newSubEvent);

      request
        .then(response => {
          if (this.editingSubEventId) {
            this.events.forEach(event => {
              if (event.sub_events) {
                const subIndex = event.sub_events.findIndex(s => s.id === this.editingSubEventId);
                if (subIndex !== -1) {
                  event.sub_events.splice(subIndex, 1, response.data);
                }
              }
            });
            toastr.success('সাব-ইভেন্ট আপডেট হয়েছে');
          } else {
            const event = this.events.find(e => e.id === this.newSubEvent.interschool_event_id);
            if (event) {
              if (!event.sub_events) event.sub_events = [];
              event.sub_events.push(response.data);
            }
            toastr.success('সাব-ইভেন্ট যুক্ত হয়েছে');
          }
          $('#addSubEventModal').modal('hide');
        })
        .catch(error => {
          console.error(error);
          toastr.error('সাব-ইভেন্ট সেভ করতে সমস্যা হয়েছে।');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    deleteSubEvent(id) {
      if (confirm('আপনি কি নিশ্চিত?')) {
        axios.delete(`/principal/institute/${this.schoolId}/game-and-sports/interschool/api/sub-events-settings/${id}`)
          .then(() => {
            this.events.forEach(event => {
              event.sub_events = event.sub_events.filter(s => s.id !== id);
            });
            toastr.success('সাব-ইভেন্ট ডিলিট হয়েছে');
          })
          .catch(() => toastr.error('ডিলিট করতে সমস্যা হয়েছে।'));
      }
    }
  }
};
</script>
