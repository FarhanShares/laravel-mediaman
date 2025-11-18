# @mediaman/core

Framework-agnostic JavaScript library for MediaMan Laravel package.

## Installation

```bash
npm install @mediaman/core
```

## Usage

```javascript
import { MediaManCore } from '@mediaman/core';

const uploader = new MediaManCore({
  uploadUrl: '/mediaman/upload',
  collection: 'products',
  conversions: ['thumbnail', 'webp'],
  tags: ['product-images'],
});

// Upload single file
const fileInput = document.querySelector('input[type="file"]');
fileInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];

  try {
    const media = await uploader.upload(file, {
      onProgress: (percent) => {
        console.log(`Upload progress: ${percent}%`);
      },
      onComplete: (response) => {
        console.log('Upload complete:', response);
      },
      onError: (error) => {
        console.error('Upload failed:', error);
      },
    });
  } catch (error) {
    console.error(error);
  }
});

// Upload multiple files
const files = Array.from(fileInput.files);
const result = await uploader.uploadMultiple(files, {
  onProgress: (percent) => {
    console.log(`Overall progress: ${percent}%`);
  },
});

console.log(`Uploaded: ${result.uploaded}, Failed: ${result.failed}`);
```

## API

### Constructor

```typescript
new MediaManCore(config: MediaManConfig)
```

**Config options:**
- `uploadUrl` (required): Upload endpoint URL
- `collection`: Collection name
- `conversions`: Array of conversion names
- `tags`: Array of tags
- `disk`: Storage disk name
- `headers`: Custom HTTP headers
- `withCredentials`: Send cookies (default: true)

### Methods

#### upload(file, options)

Upload a single file.

```typescript
upload(file: File, options?: UploadOptions): Promise<MediaResponse>
```

#### uploadMultiple(files, options)

Upload multiple files.

```typescript
uploadMultiple(files: File[], options?: UploadOptions): Promise<BatchUploadResult>
```

#### abort()

Abort current upload.

```typescript
abort(): void
```

#### updateConfig(config)

Update configuration.

```typescript
updateConfig(config: Partial<MediaManConfig>): void
```

## License

MIT
