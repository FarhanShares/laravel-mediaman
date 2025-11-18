<template>
  <div class="mediaman-uploader" :class="{ 'mediaman-pro': isPro }">
    <div v-if="!isLicensed && !isLocalhost" class="mediaman-watermark">
      MediaMan Pro Required
    </div>

    <div
      class="mediaman-dropzone"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      :class="{ 'dragging': isDragging }"
    >
      <input
        type="file"
        ref="fileInput"
        :multiple="multiple"
        :accept="accept"
        @change="handleFileSelect"
        class="hidden"
      >

      <div class="mediaman-dropzone-content" @click="$refs.fileInput.click()">
        <svg class="mediaman-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p>{{ isDragging ? 'Drop files here...' : 'Drag & drop files here, or click to browse' }}</p>
      </div>
    </div>

    <div class="mediaman-file-list">
      <div v-for="file in files" :key="file.id" class="mediaman-file-item">
        <img v-if="file.preview" :src="file.preview" class="mediaman-thumbnail">
        <div class="mediaman-file-info">
          <p>{{ file.file.name }}</p>
          <p class="text-sm">{{ formatSize(file.file.size) }}</p>
        </div>
        <div class="mediaman-file-progress">
          <div class="progress-bar" :style="`width: ${file.progress}%`"></div>
        </div>
        <span class="status-icon">{{ file.status === 'success' ? '✓' : file.status === 'error' ? '✗' : '⋯' }}</span>
        <button @click="removeFile(file.id)" class="mediaman-remove-btn">×</button>
      </div>
    </div>

    <div v-if="enableAI && isPro" class="mediaman-ai-features">
      <label><input type="checkbox" v-model="aiFeatures.autoTag"> Auto-generate tags</label>
      <label><input type="checkbox" v-model="aiFeatures.extractText"> Extract text (OCR)</label>
      <label><input type="checkbox" v-model="aiFeatures.generateAlt"> Generate alt text</label>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { MediaManCore } from '../core/MediaManCore';

const props = defineProps({
  multiple: { type: Boolean, default: false },
  collection: { type: String, default: 'default' },
  conversions: { type: Array, default: () => [] },
  maxSize: { type: Number, default: 10485760 },
  accept: { type: String, default: 'image/*,video/*,application/pdf' },
  enableAI: { type: Boolean, default: true },
  endpoint: { type: String, default: '/mediaman/upload' }
});

const emit = defineEmits(['uploaded', 'error', 'progress']);

const core = new MediaManCore({ endpoint: props.endpoint });

const files = ref([]);
const isDragging = ref(false);
const isLicensed = ref(false);
const isLocalhost = ref(false);
const aiFeatures = ref({
  autoTag: false,
  extractText: false,
  generateAlt: false
});

const isPro = computed(() => isLicensed.value || isLocalhost.value);

onMounted(async () => {
  await core.validateLicense();
  isLicensed.value = core.isLicensed;
  isLocalhost.value = core.isLocalhost;
});

const handleDrop = (e) => {
  isDragging.value = false;
  const droppedFiles = Array.from(e.dataTransfer.files);
  processFiles(droppedFiles);
};

const handleFileSelect = (e) => {
  const selectedFiles = Array.from(e.target.files);
  processFiles(selectedFiles);
};

const processFiles = async (fileList) => {
  for (const file of fileList) {
    if (file.size > props.maxSize) {
      emit('error', { file, message: 'File size exceeds limit' });
      continue;
    }

    const preview = await core.generatePreview(file);
    const fileObject = {
      id: Date.now() + Math.random(),
      file,
      progress: 0,
      status: 'pending',
      preview
    };

    files.value.push(fileObject);
    uploadFile(fileObject);
  }
};

const uploadFile = async (fileObject) => {
  try {
    const result = await core.uploadWithProgress(
      fileObject.file,
      {
        collection: props.collection,
        conversions: props.conversions,
        aiFeatures: Object.keys(aiFeatures.value).filter(k => aiFeatures.value[k])
      },
      (progress) => {
        fileObject.progress = progress;
        emit('progress', { file: fileObject, progress });
      }
    );

    fileObject.status = 'success';
    emit('uploaded', result);
  } catch (error) {
    fileObject.status = 'error';
    emit('error', { file: fileObject, error });
  }
};

const removeFile = (id) => {
  files.value = files.value.filter(f => f.id !== id);
};

const formatSize = (bytes) => core.formatSize(bytes);
</script>

<style scoped>
/* Styles from Blade component */
.mediaman-uploader { position: relative; }
.mediaman-watermark {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  z-index: 1000; background: rgba(0,0,0,0.8); color: white;
  padding: 1rem 2rem; border-radius: 0.5rem; font-weight: bold;
}
.mediaman-dropzone {
  border: 2px dashed #cbd5e0; border-radius: 0.5rem;
  padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s;
}
.mediaman-dropzone.dragging { border-color: #4299e1; background: #ebf8ff; }
.mediaman-icon { width: 4rem; height: 4rem; margin: 0 auto 1rem; color: #a0aec0; }
.mediaman-file-list { margin-top: 1rem; }
.mediaman-file-item {
  display: flex; align-items: center; gap: 1rem;
  padding: 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 0.375rem; margin-bottom: 0.5rem;
}
.mediaman-thumbnail { width: 3rem; height: 3rem; object-fit: cover; border-radius: 0.25rem; }
.mediaman-file-info { flex: 1; }
.mediaman-file-progress {
  flex: 1; height: 0.5rem; background: #e2e8f0;
  border-radius: 0.25rem; overflow: hidden;
}
.progress-bar { height: 100%; background: #4299e1; transition: width 0.3s; }
.mediaman-remove-btn {
  background: #f56565; color: white; border: none;
  width: 2rem; height: 2rem; border-radius: 50%;
  cursor: pointer; font-size: 1.5rem; line-height: 1;
}
.mediaman-ai-features {
  margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;
}
.hidden { display: none; }
</style>
