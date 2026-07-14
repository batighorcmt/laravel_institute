<template>
  <div class="gallery-manager" v-if="loaded">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div class="d-flex gap-3 flex-wrap">
        <div class="stat-pill"><i class="fas fa-image mr-1"></i> {{ images.length }} টি সাধারণ ছবি</div>
        <div class="stat-pill"><i class="fas fa-folder-open mr-1"></i> {{ albums.length }} টি এলবাম</div>
      </div>
    </div>

    <!-- General gallery -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-dark text-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-images mr-2"></i> সাধারণ গ্যালারি</span>
      </div>
      <div class="card-body">
        <upload-dropzone @files-selected="(files) => uploadFiles(files, null)"></upload-dropzone>

        <div v-if="uploadQueue.length" class="mt-3">
          <div v-for="job in uploadQueue" :key="job.id" class="upload-progress-row">
            <span class="upload-name">{{ job.name }}</span>
            <div class="progress flex-grow-1 mx-2" style="height: 8px;">
              <div class="progress-bar" :class="job.status === 'error' ? 'bg-danger' : 'bg-success'" :style="{ width: job.progress + '%' }"></div>
            </div>
            <span class="upload-status">
              <i class="fas fa-check text-success" v-if="job.status === 'done'"></i>
              <i class="fas fa-times text-danger" v-else-if="job.status === 'error'"></i>
              <span v-else>{{ job.progress }}%</span>
            </span>
          </div>
        </div>

        <div v-if="images.length" class="row mt-4">
          <div v-for="img in images" :key="img.id" class="col-6 col-md-3 mb-4">
            <div class="gallery-thumb">
              <img :src="img.url" alt="">
              <button type="button" class="btn btn-danger btn-sm gallery-thumb-delete" @click="deleteImage(img)">
                <i class="fas fa-times"></i>
              </button>
              <div class="gallery-thumb-date"><i class="far fa-clock mr-1"></i>{{ img.uploaded_at }}</div>
            </div>
          </div>
        </div>
        <p v-else class="text-muted small mb-0 mt-3">কোনো সাধারণ গ্যালারি ছবি নেই।</p>
      </div>
    </div>

    <!-- Albums -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-indigo text-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-folder-open mr-2"></i> এলবাম সমূহ</span>
        <button type="button" class="btn btn-sm btn-light" @click="showNewAlbumForm = !showNewAlbumForm">
          <i class="fas fa-plus mr-1"></i> নতুন এলবাম
        </button>
      </div>
      <div class="card-body">
        <div v-if="showNewAlbumForm" class="border rounded p-3 mb-4 bg-light">
          <div class="form-group">
            <label class="small font-weight-bold">এলবামের নাম *</label>
            <input v-model="newAlbum.name" type="text" class="form-control" placeholder="যেমন: বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৬">
          </div>
          <div class="form-group mb-2">
            <label class="small font-weight-bold">বিবরণ (ঐচ্ছিক)</label>
            <textarea v-model="newAlbum.description" rows="2" class="form-control"></textarea>
          </div>
          <button type="button" class="btn btn-primary btn-sm" :disabled="creatingAlbum || !newAlbum.name" @click="createAlbum">
            <span v-if="creatingAlbum" class="spinner-border spinner-border-sm mr-1"></span>
            এলবাম তৈরি করুন
          </button>
        </div>

        <div v-if="!albums.length" class="text-muted small">কোনো এলবাম তৈরি করা হয়নি।</div>

        <div v-for="album in albums" :key="album.id" class="album-card mb-3">
          <div class="album-card-header" @click="toggleAlbum(album)">
            <div class="album-thumb-collage">
              <img v-for="(t, i) in album.thumbnails.slice(0, 4)" :key="i" :src="t" alt="">
              <div v-for="i in Math.max(0, 4 - album.thumbnails.length)" :key="'ph'+i" class="album-thumb-placeholder"></div>
            </div>
            <div class="flex-grow-1 px-3">
              <div class="font-weight-bold">{{ album.name }}</div>
              <div class="text-muted small">{{ album.images_count }} টি ছবি &middot; তৈরি: {{ album.created_at }}</div>
              <div v-if="album.description" class="text-muted small">{{ album.description }}</div>
            </div>
            <div class="album-card-actions" @click.stop>
              <button type="button" class="btn btn-sm btn-outline-secondary" @click="startEditAlbum(album)"><i class="fas fa-edit"></i></button>
              <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteAlbum(album)"><i class="fas fa-trash"></i></button>
              <i class="fas fa-chevron-down ml-2 expand-icon" :class="{ 'rotated': expandedAlbumId === album.id }" @click="toggleAlbum(album)"></i>
            </div>
          </div>

          <div v-if="editingAlbumId === album.id" class="border rounded p-3 mt-2 bg-light">
            <div class="form-group">
              <label class="small font-weight-bold">নাম</label>
              <input v-model="editAlbum.name" type="text" class="form-control">
            </div>
            <div class="form-group mb-2">
              <label class="small font-weight-bold">বিবরণ</label>
              <textarea v-model="editAlbum.description" rows="2" class="form-control"></textarea>
            </div>
            <button type="button" class="btn btn-primary btn-sm" @click="saveAlbumEdit(album)">সংরক্ষণ করুন</button>
            <button type="button" class="btn btn-light btn-sm ml-2" @click="editingAlbumId = null">বাতিল</button>
          </div>

          <div v-if="expandedAlbumId === album.id" class="album-expanded border rounded p-3 mt-2">
            <upload-dropzone @files-selected="(files) => uploadFiles(files, album.id)"></upload-dropzone>

            <div v-if="albumUploadQueue[album.id] && albumUploadQueue[album.id].length" class="mt-3">
              <div v-for="job in albumUploadQueue[album.id]" :key="job.id" class="upload-progress-row">
                <span class="upload-name">{{ job.name }}</span>
                <div class="progress flex-grow-1 mx-2" style="height: 8px;">
                  <div class="progress-bar" :class="job.status === 'error' ? 'bg-danger' : 'bg-success'" :style="{ width: job.progress + '%' }"></div>
                </div>
                <span class="upload-status">
                  <i class="fas fa-check text-success" v-if="job.status === 'done'"></i>
                  <i class="fas fa-times text-danger" v-else-if="job.status === 'error'"></i>
                  <span v-else>{{ job.progress }}%</span>
                </span>
              </div>
            </div>

            <div v-if="albumImagesLoading" class="text-center py-4">
              <span class="spinner-border spinner-border-sm"></span>
            </div>
            <div v-else class="row mt-3">
              <div v-for="img in (albumImageCache[album.id] || [])" :key="img.id" class="col-6 col-md-3 mb-4">
                <div class="gallery-thumb">
                  <img :src="img.url" alt="">
                  <button type="button" class="btn btn-danger btn-sm gallery-thumb-delete" @click="deleteImage(img, album)">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="gallery-thumb-date"><i class="far fa-clock mr-1"></i>{{ img.uploaded_at }}</div>
                </div>
              </div>
              <p v-if="!(albumImageCache[album.id] || []).length" class="text-muted small mb-0 ml-3">এই এলবামে কোনো ছবি নেই।</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5">
    <div class="spinner-border text-primary"></div>
  </div>
</template>

<script>
import axios from 'axios';

let jobCounter = 0;

export default {
  components: {
    UploadDropzone: {
      template: `
        <div class="upload-dropzone" :class="{ dragging: isDragging }"
             @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="onDrop">
          <input type="file" multiple accept="image/*" class="dropzone-input" @change="onSelect" ref="fileInput">
          <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
          <div class="font-weight-bold text-secondary">ছবি টেনে আনুন অথবা ক্লিক করে বেছে নিন</div>
          <div class="text-muted small">একাধিক ছবি একসাথে আপলোড করা যাবে (JPG, PNG, WEBP)</div>
        </div>
      `,
      data() { return { isDragging: false }; },
      methods: {
        onSelect(e) {
          const files = Array.from(e.target.files || []);
          if (files.length) this.$emit('files-selected', files);
          this.$refs.fileInput.value = '';
        },
        onDrop(e) {
          this.isDragging = false;
          const files = Array.from(e.dataTransfer.files || []).filter(f => f.type.startsWith('image/'));
          if (files.length) this.$emit('files-selected', files);
        },
      },
    },
  },
  props: {
    schoolId: { type: Number, required: true },
  },
  data() {
    return {
      loaded: false,
      images: [],
      albums: [],
      uploadQueue: [],
      albumUploadQueue: {},
      showNewAlbumForm: false,
      newAlbum: { name: '', description: '' },
      creatingAlbum: false,
      editingAlbumId: null,
      editAlbum: { name: '', description: '' },
      expandedAlbumId: null,
      albumImageCache: {},
      albumImagesLoading: false,
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/gallery/data`);
        this.images = res.data.images || [];
        this.albums = res.data.albums || [];
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    uploadFiles(files, albumId) {
      const queue = albumId ? (this.albumUploadQueue[albumId] || (this.albumUploadQueue[albumId] = [])) : this.uploadQueue;

      files.forEach(file => {
        const job = { id: ++jobCounter, name: file.name, progress: 0, status: 'uploading' };
        queue.push(job);

        const fd = new FormData();
        fd.append('images[]', file);
        if (albumId) fd.append('gallery_album_id', albumId);

        axios.post(`/principal/institute/${this.schoolId}/frontend/gallery/upload`, fd, {
          onUploadProgress: (evt) => {
            if (evt.total) job.progress = Math.round((evt.loaded / evt.total) * 100);
          },
        }).then((res) => {
          job.status = 'done';
          job.progress = 100;
          const uploaded = res.data.images || [];
          if (albumId) {
            if (this.expandedAlbumId === albumId) {
              this.albumImageCache[albumId] = [...uploaded, ...(this.albumImageCache[albumId] || [])];
            }
            const album = this.albums.find(a => a.id === albumId);
            if (album) {
              album.images_count += uploaded.length;
              album.thumbnails = [...uploaded.map(i => i.url), ...album.thumbnails].slice(0, 4);
            }
          } else {
            this.images = [...uploaded, ...this.images];
          }
          setTimeout(() => { queue.splice(queue.indexOf(job), 1); }, 2000);
        }).catch(() => {
          job.status = 'error';
          if (window.toastr) window.toastr.error(file.name + ' আপলোড ব্যর্থ হয়েছে');
        });
      });
    },
    async deleteImage(img, album) {
      if (!confirm('এই ছবিটি মুছে ফেলতে চান?')) return;
      try {
        await axios.delete(`/principal/institute/${this.schoolId}/frontend/gallery/images/${img.id}`);
        if (album) {
          this.albumImageCache[album.id] = (this.albumImageCache[album.id] || []).filter(i => i.id !== img.id);
          album.images_count = Math.max(0, album.images_count - 1);
        } else {
          this.images = this.images.filter(i => i.id !== img.id);
        }
        if (window.toastr) window.toastr.success('ছবি মুছে ফেলা হয়েছে');
      } catch (e) {
        if (window.toastr) window.toastr.error('মুছতে সমস্যা হয়েছে');
      }
    },
    async createAlbum() {
      this.creatingAlbum = true;
      try {
        const res = await axios.post(`/principal/institute/${this.schoolId}/frontend/gallery/albums`, this.newAlbum);
        this.albums.unshift(res.data.album);
        this.newAlbum = { name: '', description: '' };
        this.showNewAlbumForm = false;
        if (window.toastr) window.toastr.success('এলবাম তৈরি হয়েছে');
      } catch (e) {
        if (window.toastr) window.toastr.error('এলবাম তৈরি করতে সমস্যা হয়েছে');
      } finally {
        this.creatingAlbum = false;
      }
    },
    startEditAlbum(album) {
      this.editingAlbumId = album.id;
      this.editAlbum = { name: album.name, description: album.description || '' };
    },
    async saveAlbumEdit(album) {
      try {
        const res = await axios.put(`/principal/institute/${this.schoolId}/frontend/gallery/albums/${album.id}`, this.editAlbum);
        Object.assign(album, res.data.album, { thumbnails: album.thumbnails });
        this.editingAlbumId = null;
        if (window.toastr) window.toastr.success('এলবাম আপডেট হয়েছে');
      } catch (e) {
        if (window.toastr) window.toastr.error('আপডেট করতে সমস্যা হয়েছে');
      }
    },
    async deleteAlbum(album) {
      if (!confirm('এই এলবামটি মুছে ফেলতে চান? ছবিগুলো সাধারণ গ্যালারিতে থেকে যাবে।')) return;
      try {
        await axios.delete(`/principal/institute/${this.schoolId}/frontend/gallery/albums/${album.id}`);
        this.albums = this.albums.filter(a => a.id !== album.id);
        await this.fetchData();
        if (window.toastr) window.toastr.success('এলবাম মুছে ফেলা হয়েছে');
      } catch (e) {
        if (window.toastr) window.toastr.error('মুছতে সমস্যা হয়েছে');
      }
    },
    async toggleAlbum(album) {
      if (this.expandedAlbumId === album.id) {
        this.expandedAlbumId = null;
        return;
      }
      this.expandedAlbumId = album.id;
      if (!this.albumImageCache[album.id]) {
        this.albumImagesLoading = true;
        try {
          const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/gallery/albums/${album.id}/images`);
          this.albumImageCache[album.id] = res.data.images || [];
        } catch (e) {
          if (window.toastr) window.toastr.error('এলবামের ছবি লোড করতে সমস্যা হয়েছে');
        } finally {
          this.albumImagesLoading = false;
        }
      }
    },
  },
};
</script>

<style scoped>
.stat-pill { background: #f1f5f9; border-radius: 20px; padding: 6px 16px; font-weight: 700; font-size: .85rem; color: #475569; }
.upload-dropzone {
  position: relative; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 32px; text-align: center;
  background: #f8fafc; transition: all .2s;
}
.upload-dropzone.dragging { border-color: #6366f1; background: #eef2ff; }
.dropzone-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.upload-progress-row { display: flex; align-items: center; padding: 6px 0; font-size: .85rem; }
.upload-name { width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.upload-status { width: 40px; text-align: right; }
.gallery-thumb { position: relative; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
.gallery-thumb img { width: 100%; height: 140px; object-fit: cover; display: block; }
.gallery-thumb-delete { position: absolute; top: 6px; right: 6px; padding: 2px 8px; }
.gallery-thumb-date {
  position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,.55); color: #fff;
  font-size: .7rem; padding: 3px 8px;
}
.album-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.album-card-header { display: flex; align-items: center; padding: 12px; cursor: pointer; background: #fff; }
.album-card-header:hover { background: #f8fafc; }
.album-thumb-collage {
  width: 72px; height: 72px; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr;
  gap: 2px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #e2e8f0;
}
.album-thumb-collage img, .album-thumb-placeholder { width: 100%; height: 100%; object-fit: cover; background: #e2e8f0; }
.album-card-actions { display: flex; align-items: center; gap: 6px; }
.expand-icon { transition: transform .2s; cursor: pointer; color: #94a3b8; }
.expand-icon.rotated { transform: rotate(180deg); }
.album-expanded { background: #f8fafc; }
</style>
