@props([
    'collection' => 'default',
    'multiple' => false,
    'conversions' => [],
    'maxSize' => 10485760, // 10MB
    'accept' => 'image/*,video/*,application/pdf',
    'enableAI' => true,
    'endpoint' => route('mediaman.upload'),
])

<div
    class="mediaman-uploader"
    x-data="mediamanUploader({
        collection: '{{ $collection }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        conversions: @js($conversions),
        maxSize: {{ $maxSize }},
        accept: '{{ $accept }}',
        enableAI: {{ $enableAI ? 'true' : 'false' }},
        endpoint: '{{ $endpoint }}'
    })"
    x-init="init()"
>
    <div x-show="!isLicensed && !isLocalhost" class="mediaman-watermark">
        MediaMan Pro Required
    </div>

    <div
        class="mediaman-dropzone"
        :class="{ 'dragging': isDragging, 'disabled': !isPro }"
        @drop.prevent="handleDrop"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @click="$refs.fileInput.click()"
    >
        <input
            type="file"
            x-ref="fileInput"
            :multiple="multiple"
            :accept="accept"
            @change="handleFileSelect"
            class="hidden"
        >

        <div class="mediaman-dropzone-content">
            <svg class="mediaman-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p x-text="isDragging ? 'Drop files here...' : 'Drag & drop files here, or click to browse'"></p>
        </div>
    </div>

    <div class="mediaman-file-list">
        <template x-for="file in files" :key="file.id">
            <div class="mediaman-file-item">
                <img x-show="file.preview" :src="file.preview" class="mediaman-thumbnail">
                <div class="mediaman-file-info">
                    <p x-text="file.file.name"></p>
                    <p class="text-sm text-gray-500" x-text="formatSize(file.file.size)"></p>
                </div>
                <div class="mediaman-file-progress">
                    <div class="progress-bar" :style="`width: ${file.progress}%`"></div>
                </div>
                <button @click="removeFile(file.id)" class="mediaman-remove-btn">×</button>
            </div>
        </template>
    </div>

    <div x-show="enableAI && isPro" class="mediaman-ai-features">
        <label><input type="checkbox" x-model="aiFeatures.autoTag"> Auto-generate tags</label>
        <label><input type="checkbox" x-model="aiFeatures.extractText"> Extract text (OCR)</label>
        <label><input type="checkbox" x-model="aiFeatures.generateAlt"> Generate alt text</label>
    </div>
</div>

<style>
.mediaman-uploader { position: relative; }
.mediaman-watermark {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    z-index: 1000; background: rgba(0,0,0,0.8); color: white;
    padding: 1rem 2rem; border-radius: 0.5rem; font-weight: bold;
}
.mediaman-dropzone {
    border: 2px dashed #cbd5e0; border-radius: 0.5rem;
    padding: 2rem; text-align: center; cursor: pointer;
    transition: all 0.3s;
}
.mediaman-dropzone.dragging { border-color: #4299e1; background: #ebf8ff; }
.mediaman-dropzone.disabled { opacity: 0.5; pointer-events: none; }
.mediaman-icon { width: 4rem; height: 4rem; margin: 0 auto 1rem; color: #a0aec0; }
.mediaman-file-list { margin-top: 1rem; }
.mediaman-file-item {
    display: flex; align-items: center; gap: 1rem;
    padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;
    margin-bottom: 0.5rem;
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
.mediaman-ai-features label { display: flex; align-items: center; gap: 0.5rem; }
</style>
