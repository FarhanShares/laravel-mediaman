# @mediaman/vue

Vue 3 components for MediaMan Laravel package.

## Installation

```bash
npm install @mediaman/vue
```

## Usage

```vue
<template>
  <div>
    <MediaUploader
      upload-url="/mediaman/upload"
      collection="products"
      :conversions="['thumbnail', 'webp']"
      :tags="['product-images']"
      :multiple="true"
      :max-files="10"
      @upload-complete="handleUploadComplete"
      @upload-progress="handleProgress"
      @upload-error="handleError"
    />
  </div>
</template>

<script setup lang="ts">
import { MediaUploader } from '@mediaman/vue';
import type { MediaResponse } from '@mediaman/core';

const handleUploadComplete = (media: MediaResponse | MediaResponse[]) => {
  console.log('Upload complete:', media);
};

const handleProgress = (percent: number) => {
  console.log(`Progress: ${percent}%`);
};

const handleError = (error: string) => {
  console.error('Upload error:', error);
};
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `uploadUrl` | `string` | `/mediaman/upload` | Upload endpoint URL |
| `collection` | `string` | - | Collection name |
| `conversions` | `string[]` | - | Array of conversion names |
| `tags` | `string[]` | - | Array of tags |
| `disk` | `string` | - | Storage disk name |
| `multiple` | `boolean` | `true` | Allow multiple file uploads |
| `maxFiles` | `number` | `10` | Maximum number of files |
| `maxFileSize` | `number` | `10485760` | Maximum file size in bytes (10MB) |
| `accept` | `string` | `*` | Accepted file types |
| `dragText` | `string` | - | Custom drag & drop text |
| `subText` | `string` | - | Custom sub text |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `upload-complete` | `MediaResponse \| MediaResponse[]` | Emitted when upload completes |
| `upload-progress` | `number` | Emitted during upload (percent) |
| `upload-error` | `string` | Emitted on upload error |
| `files-changed` | `MediaResponse[]` | Emitted when files list changes |

## Exposed Methods

```vue
<template>
  <MediaUploader ref="uploaderRef" />
</template>

<script setup>
import { ref } from 'vue';
import { MediaUploader } from '@mediaman/vue';

const uploaderRef = ref(null);

// Access uploaded files
console.log(uploaderRef.value.uploadedFiles);

// Clear all files
uploaderRef.value.clearFiles();
</script>
```

## Styling

The component comes with default styling that you can override:

```css
/* Override default styles */
.mediaman-dropzone {
  border-color: #your-color;
  background-color: #your-bg;
}

.mediaman-dropzone:hover {
  border-color: #your-hover-color;
}
```

## License

MIT
