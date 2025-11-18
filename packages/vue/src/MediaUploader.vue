<template>
  <div class="mediaman-uploader">
    <div
      class="mediaman-dropzone"
      :class="{ 'is-dragover': isDragOver, 'is-uploading': isUploading }"
      @click="openFileDialog"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
    >
      <input
        ref="fileInput"
        type="file"
        :multiple="multiple"
        :accept="accept"
        @change="handleFileSelect"
        style="display: none"
      />

      <div v-if="!isUploading" class="mediaman-dropzone-content">
        <svg
          class="mediaman-icon"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
          />
        </svg>
        <p class="mediaman-text">
          {{ dragText || 'Click to upload or drag and drop' }}
        </p>
        <p class="mediaman-subtext">
          {{ subText || 'Supports: Images, Videos, Documents' }}
        </p>
      </div>

      <div v-else class="mediaman-upload-progress">
        <div class="mediaman-progress-bar">
          <div
            class="mediaman-progress-fill"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
        <p class="mediaman-progress-text">{{ uploadProgress }}%</p>
      </div>
    </div>

    <div v-if="uploadedFiles.length > 0" class="mediaman-uploaded-files">
      <div
        v-for="file in uploadedFiles"
        :key="file.id"
        class="mediaman-uploaded-file"
      >
        <img
          v-if="file.mime_type.startsWith('image/')"
          :src="file.media_url"
          :alt="file.name"
          class="mediaman-thumbnail"
        />
        <div class="mediaman-file-info">
          <p class="mediaman-file-name">{{ file.name }}</p>
          <p class="mediaman-file-size">{{ formatFileSize(file.size) }}</p>
        </div>
        <button
          @click="removeFile(file.id)"
          class="mediaman-remove-btn"
          type="button"
        >
          ×
        </button>
      </div>
    </div>

    <div v-if="errors.length > 0" class="mediaman-errors">
      <div v-for="(error, index) in errors" :key="index" class="mediaman-error">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { MediaManCore, MediaResponse } from '@mediaman/core';

interface Props {
  uploadUrl?: string;
  collection?: string;
  conversions?: string[];
  tags?: string[];
  disk?: string;
  multiple?: boolean;
  maxFiles?: number;
  maxFileSize?: number;
  accept?: string;
  dragText?: string;
  subText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  uploadUrl: '/mediaman/upload',
  multiple: true,
  maxFiles: 10,
  maxFileSize: 10 * 1024 * 1024, // 10MB
  accept: '*',
});

const emit = defineEmits<{
  (e: 'upload-complete', media: MediaResponse | MediaResponse[]): void;
  (e: 'upload-progress', percent: number): void;
  (e: 'upload-error', error: string): void;
  (e: 'files-changed', files: MediaResponse[]): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const isDragOver = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadedFiles = ref<MediaResponse[]>([]);
const errors = ref<string[]>([]);

const uploader = new MediaManCore({
  uploadUrl: props.uploadUrl,
  collection: props.collection,
  conversions: props.conversions,
  tags: props.tags,
  disk: props.disk,
});

const openFileDialog = () => {
  fileInput.value?.click();
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    handleFiles(Array.from(target.files));
  }
};

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false;
  if (event.dataTransfer?.files) {
    handleFiles(Array.from(event.dataTransfer.files));
  }
};

const handleFiles = async (files: File[]) => {
  errors.value = [];

  // Validate file count
  if (uploadedFiles.value.length + files.length > props.maxFiles) {
    errors.value.push(`Maximum ${props.maxFiles} files allowed`);
    return;
  }

  // Validate file sizes
  const oversizedFiles = files.filter((file) => file.size > props.maxFileSize);
  if (oversizedFiles.length > 0) {
    errors.value.push(
      `Some files exceed maximum size of ${formatFileSize(props.maxFileSize)}`
    );
    return;
  }

  isUploading.value = true;
  uploadProgress.value = 0;

  try {
    if (files.length === 1) {
      const media = await uploader.upload(files[0], {
        onProgress: (percent) => {
          uploadProgress.value = Math.round(percent);
          emit('upload-progress', percent);
        },
      });
      uploadedFiles.value.push(media);
      emit('upload-complete', media);
    } else {
      const result = await uploader.uploadMultiple(files, {
        onProgress: (percent) => {
          uploadProgress.value = Math.round(percent);
          emit('upload-progress', percent);
        },
      });
      uploadedFiles.value.push(...result.results);
      emit('upload-complete', result.results);

      if (result.errors.length > 0) {
        errors.value = result.errors.map((e) => `${e.file}: ${e.error}`);
      }
    }

    emit('files-changed', uploadedFiles.value);
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : String(error);
    errors.value.push(errorMessage);
    emit('upload-error', errorMessage);
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;

    // Reset file input
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

const removeFile = (id: number) => {
  uploadedFiles.value = uploadedFiles.value.filter((file) => file.id !== id);
  emit('files-changed', uploadedFiles.value);
};

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

defineExpose({
  uploadedFiles,
  clearFiles: () => {
    uploadedFiles.value = [];
    emit('files-changed', []);
  },
});
</script>

<style scoped>
.mediaman-uploader {
  width: 100%;
}

.mediaman-dropzone {
  border: 2px dashed #cbd5e0;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background-color: #f7fafc;
}

.mediaman-dropzone:hover,
.mediaman-dropzone.is-dragover {
  border-color: #4299e1;
  background-color: #ebf8ff;
}

.mediaman-dropzone.is-uploading {
  cursor: not-allowed;
}

.mediaman-dropzone-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.mediaman-icon {
  width: 3rem;
  height: 3rem;
  color: #4a5568;
}

.mediaman-text {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
  color: #2d3748;
}

.mediaman-subtext {
  margin: 0;
  font-size: 0.875rem;
  color: #718096;
}

.mediaman-upload-progress {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.mediaman-progress-bar {
  width: 100%;
  height: 0.5rem;
  background-color: #e2e8f0;
  border-radius: 9999px;
  overflow: hidden;
}

.mediaman-progress-fill {
  height: 100%;
  background-color: #4299e1;
  transition: width 0.3s;
}

.mediaman-progress-text {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #2d3748;
}

.mediaman-uploaded-files {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mediaman-uploaded-file {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background-color: #f7fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.mediaman-thumbnail {
  width: 3rem;
  height: 3rem;
  object-fit: cover;
  border-radius: 0.25rem;
}

.mediaman-file-info {
  flex: 1;
}

.mediaman-file-name {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #2d3748;
}

.mediaman-file-size {
  margin: 0;
  font-size: 0.75rem;
  color: #718096;
}

.mediaman-remove-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background-color: #fed7d7;
  color: #c53030;
  border-radius: 0.25rem;
  cursor: pointer;
  font-size: 1.5rem;
  line-height: 1;
  transition: background-color 0.2s;
}

.mediaman-remove-btn:hover {
  background-color: #fc8181;
  color: #fff;
}

.mediaman-errors {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mediaman-error {
  padding: 0.75rem;
  background-color: #fed7d7;
  color: #c53030;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}
</style>
