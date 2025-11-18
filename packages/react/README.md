# @mediaman/react

React components for MediaMan Laravel package.

## Installation

```bash
npm install @mediaman/react
```

## Usage

```tsx
import React from 'react';
import { MediaUploader } from '@mediaman/react';
import type { MediaResponse } from '@mediaman/core';

function App() {
  const handleUploadComplete = (media: MediaResponse | MediaResponse[]) => {
    console.log('Upload complete:', media);
  };

  const handleProgress = (percent: number) => {
    console.log(`Progress: ${percent}%`);
  };

  const handleError = (error: string) => {
    console.error('Upload error:', error);
  };

  const handleFilesChanged = (files: MediaResponse[]) => {
    console.log('Files changed:', files);
  };

  return (
    <div>
      <MediaUploader
        uploadUrl="/mediaman/upload"
        collection="products"
        conversions={['thumbnail', 'webp']}
        tags={['product-images']}
        multiple={true}
        maxFiles={10}
        onUploadComplete={handleUploadComplete}
        onUploadProgress={handleProgress}
        onUploadError={handleError}
        onFilesChanged={handleFilesChanged}
      />
    </div>
  );
}

export default App;
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
| `onUploadComplete` | `(media) => void` | - | Called when upload completes |
| `onUploadProgress` | `(percent) => void` | - | Called during upload |
| `onUploadError` | `(error) => void` | - | Called on upload error |
| `onFilesChanged` | `(files) => void` | - | Called when files list changes |

## Styling

The component comes with default inline styles. To customize:

```tsx
// Option 1: Override with CSS classes
<MediaUploader
  {...props}
  style={{ border: '2px solid blue' }}
/>

// Option 2: Use CSS to override default styles
.mediaman-dropzone {
  border-color: #your-color !important;
  background-color: #your-bg !important;
}

.mediaman-dropzone:hover {
  border-color: #your-hover-color !important;
}
```

## TypeScript

The package includes TypeScript definitions out of the box:

```tsx
import { MediaUploader, type MediaUploaderProps } from '@mediaman/react';
import type { MediaResponse } from '@mediaman/core';
```

## License

MIT
