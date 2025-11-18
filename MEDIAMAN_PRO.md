# Laravel MediaMan Pro - Implementation Guide

## Overview

This document outlines all the Pro features that have been implemented in Laravel MediaMan. The package now includes advanced features for professional media management, including UUID support, enhanced security, AI processing, and beautiful UI components for Vue, React, and Blade.

## What's New in MediaMan Pro

### 🔐 Core Enhancements

1. **UUID Support**
   - Configurable UUID generation for all media files
   - UUID-based routing for enhanced security
   - Automatic UUID generation on model creation
   - Migration included for existing installations

2. **Performance Improvements**
   - Chunked upload support for large files
   - Queue-based conversion processing
   - Configurable chunk sizes
   - Progress tracking for uploads

3. **Enhanced Security**
   - File MIME type validation
   - Virus scanning support (ClamAV ready)
   - Signed URLs with expiration
   - Filename sanitization
   - Maximum file size enforcement
   - Malicious content detection

4. **AI Features** (Framework Ready)
   - Auto-tagging
   - Text extraction (OCR)
   - Alt text generation
   - Face detection

### 🎨 UI Components

Pre-built, production-ready components for:
- **Blade** (Alpine.js)
- **Vue 3** (Composition API)
- **React** (Hooks)
- **Svelte** (Coming soon)

All components include:
- Drag & drop file upload
- Progress tracking
- Preview generation
- License checking
- AI feature toggles

### 🔧 Advanced Image Processing

- **Responsive Images**: Auto-generate multiple sizes
- **WebP Conversion**: Modern format support
- **BlurHash**: Lazy loading placeholders
- **Watermarking**: Protect your images
- **Smart Crop**: Intelligent cropping

## Installation & Setup

### 1. Publish Assets

```bash
# Publish config
php artisan vendor:publish --tag=config

# Publish migrations
php artisan vendor:publish --tag=migrations

# Publish views (optional)
php artisan vendor:publish --tag=views

# Publish JS assets (for frontend components)
php artisan vendor:publish --tag=assets
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Configure Environment

Add to your `.env`:

```env
# UUID Support
MEDIAMAN_USE_UUID=true

# Performance
MEDIAMAN_QUEUE_CONVERSIONS=true
MEDIAMAN_CONVERSION_QUEUE=default

# Security
MEDIAMAN_MAX_FILE_SIZE=104857600  # 100MB
MEDIAMAN_VIRUS_SCAN=false
MEDIAMAN_SIGNED_URLS=false

# AI Features
MEDIAMAN_AI_ENABLED=false
MEDIAMAN_AI_AUTO_TAG=false
MEDIAMAN_AI_EXTRACT_TEXT=false
MEDIAMAN_AI_GENERATE_ALT=false

# UI & License
MEDIAMAN_ENABLE_UI=true
MEDIAMAN_LICENSE_KEY=your-license-key-here
```

## Usage Examples

### Basic Upload with Pro Features

```php
use FarhanShares\MediaMan\MediaUploaderPro;
use FarhanShares\MediaMan\Security\SecurityManager;
use FarhanShares\MediaMan\Conversions\ConversionManager;

$media = MediaUploaderPro::source($request->file('image'))
    ->toCollection('avatars')
    ->withConversions(['thumbnail', 'webp', 'responsive'])
    ->withTags(['profile', 'user'])
    ->withAI(['auto_tag', 'generate_alt'])
    ->setSecurityManager(app(SecurityManager::class))
    ->setConversionManager(app(ConversionManager::class))
    ->upload();
```

### Using UUID Routing

```php
// Find by UUID
$media = Media::findByUuid('550e8400-e29b-41d4-a716-446655440000');

// Automatic route model binding with UUID
Route::get('/media/{media}', function (Media $media) {
    return $media;
});
```

### Advanced Conversions

```php
use FarhanShares\MediaMan\Conversions\ConversionManager;

$conversionManager = app(ConversionManager::class);

// Register custom conversion
$conversionManager->register('custom', function($image, $media) {
    $image->resize(800, 600);
    return $image;
});

// Process in queue
$conversionManager->processInQueue($media, ['custom', 'thumbnail', 'webp']);
```

### Security Features

```php
use FarhanShares\MediaMan\Security\SecurityManager;

$security = app(SecurityManager::class);

// Generate signed URL
$signedUrl = $security->signUrl($media->media_url, 60); // Expires in 60 minutes

// Validate file before upload
$security->validateMimeType($file);
$security->scanForVirus($file);
```

## Frontend Components

### Blade Component

```blade
<x-mediaman::upload
    collection="avatars"
    :multiple="true"
    :conversions="['thumbnail', 'webp']"
    :enable-a-i="true"
/>
```

### Vue 3 Component

```vue
<template>
  <MediaUploader
    collection="avatars"
    :multiple="true"
    :conversions="['thumbnail', 'webp']"
    :enable-a-i="true"
    @uploaded="handleUpload"
    @error="handleError"
  />
</template>

<script setup>
import MediaUploader from '@/components/MediaUploader.vue';

const handleUpload = (media) => {
  console.log('Uploaded:', media);
};

const handleError = (error) => {
  console.error('Error:', error);
};
</script>
```

### React Component

```jsx
import MediaUploader from './MediaUploader';

function App() {
  const handleUpload = (media) => {
    console.log('Uploaded:', media);
  };

  return (
    <MediaUploader
      collection="avatars"
      multiple={true}
      conversions={['thumbnail', 'webp']}
      enableAI={true}
      onUploaded={handleUpload}
    />
  );
}
```

## API Endpoints

All UI routes are prefixed with `/mediaman` by default (configurable):

- `POST /mediaman/upload` - Upload file
- `POST /mediaman/upload/chunked` - Chunked upload
- `GET /mediaman/validate-license` - Validate license
- `GET /mediaman/media` - List media
- `GET /mediaman/media/{id}` - Get single media
- `PUT /mediaman/media/{id}` - Update media
- `DELETE /mediaman/media/{id}` - Delete media

## Configuration Reference

See `config/mediaman.php` for all available options:

- **UUID Configuration**: Enable UUID support and routing
- **Performance**: Chunk sizes and queue settings
- **Security**: File validation and signing
- **AI Features**: Enable/disable AI processing
- **UI Settings**: Middleware and route prefix
- **Conversions**: Image processing settings

## License Management

MediaMan Pro uses a license key system:

- **Development/Localhost**: All features work without a license
- **Production**: Requires a valid license key
- License validation is cached for 1 hour
- Features are gracefully disabled without a valid license

## Testing

Run the test suite:

```bash
vendor/bin/phpunit tests/Feature/MediaUploadTest.php
vendor/bin/phpunit tests/Unit/LicenseManagerTest.php
vendor/bin/phpunit tests/Unit/SecurityManagerTest.php
```

## Architecture

### Contracts (Interfaces)

- `Uploader` - Media upload interface
- `LicenseValidator` - License validation
- `SecurityScanner` - Security features
- `ConversionProcessor` - Image processing

### Traits

- `InteractsWithConfig` - Config helpers
- `ValidatesMedia` - File validation
- `ProcessesMedia` - Upload processing
- `HasLicenseCheck` - License checking

### Managers

- `LicenseManager` - License validation and feature gating
- `SecurityManager` - Security scanning and validation
- `ConversionManager` - Image conversion processing

## Extending MediaMan Pro

### Custom Conversions

```php
use FarhanShares\MediaMan\Conversions\ConversionManager;

app(ConversionManager::class)->register('sepia', function($image, $media) {
    $image->greyscale();
    $image->colorize(40, 20, 0);
    return $image;
});
```

### Custom AI Processors

```php
use FarhanShares\MediaMan\Jobs\ProcessAIFeatures;

// Extend the job to add custom AI processing
class CustomAIProcessor extends ProcessAIFeatures
{
    protected function generateTags(): array
    {
        // Your AI tag generation logic
        return ['tag1', 'tag2'];
    }
}
```

## Performance Tips

1. **Enable Queue Processing**: Set `MEDIAMAN_QUEUE_CONVERSIONS=true`
2. **Use Chunked Uploads**: For files > 10MB
3. **Optimize Conversions**: Only generate needed sizes
4. **Cache Signed URLs**: Reduce signature generation overhead
5. **Use CDN**: Serve media through a CDN

## Security Best Practices

1. Always validate MIME types
2. Set appropriate max file sizes
3. Enable virus scanning in production
4. Use signed URLs for sensitive content
5. Sanitize user-provided filenames
6. Implement rate limiting on upload endpoints

## Support & Contribution

- Issues: [GitHub Issues](https://github.com/FarhanShares/laravel-mediaman/issues)
- Discussions: [GitHub Discussions](https://github.com/FarhanShares/laravel-mediaman/discussions)

## Roadmap

- [ ] Svelte component
- [ ] Video processing support
- [ ] Cloud storage optimization
- [ ] Advanced AI integrations
- [ ] Real-time upload progress via WebSockets
- [ ] Chunked upload completion
- [ ] Multi-part upload for S3

---

**MediaMan Pro** - The most elegant & powerful media management package for Laravel!
