<p align="center"><a href="https://farhanshares.com/projects/laravel-mediaman" target="_blank" title="Laravel MediaMan"><img src="https://raw.githubusercontent.com/FarhanShares/laravel-mediaman/main/docs/assets/mediaman-banner.png" /></a></p>

<p align="center">
<a href="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml"><img src="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml/badge.svg" alt="Github CI"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/dt/farhanshares/laravel-mediaman" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/v/farhanshares/laravel-mediaman" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/l/farhanshares/laravel-mediaman" alt="License"></a>
</p>

# Laravel MediaMan</h1>

MediaMan is an elegant & powerful media management package for Laravel Apps with support for painless `uploader`, virtual `collection` & automatic `conversion` plus an on demand `association` with specific broadcasting `channel`s of your app models.

MediaMan is UI agnostic & provides a fluent API to manage your app's media, which means you've total control over your media, the look & feel & a hassle free dev experience. It's a perfect suit for your App or API server.

## 🚀 MediaMan Pro Features

MediaMan Pro extends the core package with enterprise-grade features for production applications:

- **🔐 Enhanced Security**: UUID support, MIME validation, signed URLs, filename sanitization
- **⚡ Performance Optimization**: Database indexing, query caching, batch operations, lazy loading
- **🎨 Advanced Image Processing**: Responsive images, WebP conversion, BlurHash, watermarks, smart cropping
- **📦 Versioning System**: File history tracking with restore capabilities
- **🏷️ Full Tagging System**: AI-powered and manual tagging with usage analytics
- **🚦 Rate Limiting**: Configurable rate limiting with multiple strategies
- **📊 Monitoring & Logging**: Comprehensive event tracking, metrics collection, audit trails
- **🔄 Batch Operations**: Queue-based batch uploads with progress tracking
- **📚 API Documentation**: Auto-generated OpenAPI/Swagger documentation
- **🎯 License Management**: Localhost detection with API-based validation
- **🎭 UI Components**: Ready-to-use Vue 3, React, Svelte, and Blade components
- **🔧 Maximum Customizability**: Everything configurable through environment variables and config files

## In a hurry? Here's a quick example

**Basic Usage:**
```php
$media = MediaUploader::source($request->file('file'))
            ->useCollection('Posts')
            ->upload();

$post = Post::find(1);
$post->attachMedia($media, 'featured-image-channel');
```

**MediaMan Pro Usage:**
```php
use FarhanShares\MediaMan\MediaUploaderPro;

// Upload with advanced features
$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions(['responsive', 'webp', 'blurhash'])
    ->withTags(['nature', 'landscape'])
    ->withAI(['auto_tag', 'extract_text'])
    ->upload();

// Batch upload with progress tracking
$batchId = BatchUploader::source($request->file('images'))
    ->toCollection('gallery')
    ->withConversions(['thumbnail', 'webp'])
    ->upload();

// Get cached media with tags and versions
$media = app(MediaCacheManager::class)->getMedia($id);
$tags = $media->tags;
$versions = $media->versions;
```

## Why Choose MediaMan? What Sets It Apart?

While many Laravel applications grapple with media and file management, I've often found solace in [Spatie's Laravel MediaLibrary](https://github.com/spatie/laravel-medialibrary). Yet, despite its capabilities, there were aspects it didn't address, features I yearned for.

**Enter MediaMan:** Sleek, lightweight, and brimming with functionality. Whether you need a straightforward solution for attaching and detaching media or the robust backbone for an extensive media manager, MediaMan adapts to your needs. And rest assured, its evolution will be guided by the ever-changing requirements of modern applications, whether they are Monolithic, API-driven, enhanced with Livewire/InertiaJS integrations, or built upon Serverless architectures.

| Comparison                         | **MediaMan**                              | Spatie              |
|------------------------------------|-------------------------------------------|---------------------|
| **Relationship type**              | **Many to many**                          | One to many         |
| **Reuse media with another model** | **Yes**                                   | No                  |
| **Multiple Disk Support**          | **Yes**                                   | No                  |
| **Channels (file tags)**           | **Yes**                                   | No                  |
| **Collections (file groups)**      | **Yes**                                   | Specific to version |
| **Auto delete media with model**   | **Yes, with options to keep**             | Yes                 |
| **Image manipulation**             | **Yes, at ease**                          | Yes                 |
| **Manipulation type**              | **Global registry**                       | Specific to a model |
| **Custom Conversion Support**      | **Yes**                                   | Limited             |

### MediaMan Core vs Pro

| Feature                            | **MediaMan Core**  | **MediaMan Pro**    |
|------------------------------------|-------------------|---------------------|
| **Basic Upload & Storage**         | ✅ Yes            | ✅ Yes              |
| **Collections & Channels**         | ✅ Yes            | ✅ Yes              |
| **Image Conversions**              | ✅ Yes            | ✅ Enhanced         |
| **UUID Support**                   | ❌ No             | ✅ Yes              |
| **Responsive Images**              | ❌ No             | ✅ Yes              |
| **WebP Conversion**                | ❌ No             | ✅ Yes              |
| **BlurHash Placeholders**          | ❌ No             | ✅ Yes              |
| **Watermarking**                   | ❌ No             | ✅ Yes              |
| **Smart Cropping**                 | ❌ No             | ✅ Yes              |
| **Versioning System**              | ❌ No             | ✅ Yes              |
| **Tagging System**                 | ❌ No             | ✅ Yes              |
| **AI-Powered Features**            | ❌ No             | ✅ Yes              |
| **Batch Operations**               | ❌ No             | ✅ Yes              |
| **Query Caching**                  | ❌ No             | ✅ Yes              |
| **Rate Limiting**                  | ❌ No             | ✅ Yes              |
| **Signed URLs**                    | ❌ No             | ✅ Yes              |
| **MIME Validation**                | ❌ No             | ✅ Yes              |
| **Monitoring & Logging**           | ❌ No             | ✅ Yes              |
| **Performance Indexes**            | ❌ No             | ✅ Yes (40+ indexes)|
| **UI Components**                  | ❌ No             | ✅ Vue/React/Blade  |
| **API Documentation**              | ❌ No             | ✅ OpenAPI/Swagger  |
| **License Management**             | ❌ No             | ✅ Yes              |

## Overview & Key concepts

There are a few key concepts that need to be understood before continuing:

* **Media**: It can be any type of file. You should specify file restrictions in your application's validation logic before you attempt to upload a file.

* **MediaUploader**: Media items are uploaded as its own entity. It does not belong to any other model in the system when it's being created, so items can be managed independently (which makes it the perfect engine for a media manager). MediaMan provides "MediaUploader" for creating records in the database & storing in the filesystem as well.

* **MediaCollection**: It can be also referred to as a group of files. Media items can be bundled to any "collection". Media & Collections will form many-to-many relation. You can create collections / virtual directories / groups of media & later on retrieve a group to check what it contains or do.

* **Association**: Media items need be attached to a model for an association to be made. MediaMan exposes helpers to easily get the job done. Many-to-many polymorphic relationships allow any number of media to be associated to any number of other models without the need of modifying the existing database schema.

* **Channel**: It can be also referred to as a tag of files. Media items are bound to a "channel" of a model during association. Thus you can easily associate multiple types of media to a model. For example, a "User" model might have an "avatar" and a "documents" media channel. If your head is spinning, simply think of "channels" as :tags" for a specific model. Channels are also needed to perform conversions.

* **Conversion**: You can manipulate images using conversions, conversions will be performed when a media item is associated to a model. For example, you can register a "thumbnail" conversion to run when images are attached to the "gallery" channel of a model.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [Media](#media)
* [Media & Models](#media--models)
* [Collections](#collections)
* [Media & Collections](#media--collections)
* [Media, Models & Conversions](#conversions)
* [MediaMan Pro Features](#mediaman-pro-features-1)
  * [UUID Support](#uuid-support)
  * [Advanced Image Processing](#advanced-image-processing)
  * [Versioning System](#versioning-system)
  * [Tagging System](#tagging-system)
  * [Batch Operations](#batch-operations)
  * [Query Caching](#query-caching)
  * [Rate Limiting](#rate-limiting)
  * [Security Features](#security-features)
  * [Monitoring & Logging](#monitoring--logging)
  * [UI Components](#ui-components)
  * [API Documentation](#api-documentation)
  * [License Management](#license-management)
* [Performance Optimization](#performance-optimization)
* [Upgrade Guide to MediaMan v1.x](#upgrade-guide-to-mediaman-v1x)
* [Contribution and License](#contribution-and-license)

## Requirements

| Laravel Version | Package Version | PHP Version        |
|-----------------|-----------------|--------------------|
| v7              | 1.0.0-stable    | 7.3 - 8.0          |
| v8              | 1.0.0-stable    | 7.3 - 8.1          |
| v9              | 1.0.0-stable    | 8.0 - 8.2          |
| v10             | 1.x.x           | 8.1 - 8.3          |
| v11             | 1.x.x           | 8.2 - 8.4          |
| v12             | 1.x.x           | 8.2 - 8.4          |

Note: The `1.0.0-stable` and `1.0.0` versions of the package are functionally identical and both considered stable. However, starting from version `1.0.0+` (without the `-stable` suffix), support for Laravel 9 and below has been **dropped**.
If your project uses Laravel 9 or an earlier version, you should use `1.0.0-stable`. For Laravel 10 and above, you can use the latest `1.x.x` version.
We strongly recommend upgrading your Laravel version to maintain compatibility with future package updates.

## Installation

You can install the package via composer:

```bash
composer require farhanshares/laravel-mediaman
```

The package should be auto discovered by Laravel unless you've disabled auto-discovery mode. In that case, add the service provider to your config/app.php:

`FarhanShares\MediaMan\MediaManServiceProvider::class`

Once installed, you should publish the provided assets to create the necessary migration and config files.

```bash
php artisan mediaman:publish-config
php artisan mediaman:publish-migration
```

**For MediaMan Pro features**, publish the Pro migrations:

```bash
php artisan vendor:publish --tag=mediaman-migrations
```

This will publish:
- `add_uuid_to_mediaman_media.php` - UUID support
- `add_performance_indexes_to_mediaman.php` - Performance optimization indexes
- `create_media_versions_table.php` - Versioning system
- `create_media_tags_tables.php` - Tagging system

Ensure the storage is linked:

```bash
php artisan storage:link
```

Run the migrations:

```bash
php artisan migrate
```

**Optional: Publish UI components** (Vue, React, Blade)

```bash
php artisan vendor:publish --tag=mediaman-ui
```

**CI/CD Setup**: MediaMan Pro includes a GitHub Actions workflow (`.github/workflows/tests.yml`) for automated testing across PHP 8.1, 8.2, 8.3 and Laravel 10, 11, 12.

## Configuration

MediaMan works out of the box. If you want to tweak it, MediaMan ships with a `config/mediaman.php`. One common need of tweaking could be to store media in a dedicated Storage.

MediaMan supports all of the storage drivers that are supported by Laravel (for i.e. Local, S3, SFTP, FTP, Dropbox & so on).

Here's an example configuration to use a dedicated local media disk for MediaMan.

```php
// file: config/filesystems.php
// define a new disk
'disks' => [
    ...
    'media' =>
        'driver' => 'local',
        'root' => storage_path('app/media'),
        'url' => env('APP_URL') . '/media',
        'visibility' => 'public',
    ],
]

// define the symbolic link
'links' => [
    ...
    public_path('media') => storage_path('app/media'),
],


// file: config/mediaman.php
// update the disk config to use our recently created media disk
'disk' => 'media'
```

Now, run `php artisan storage:link` to create the symbolic link of our newly created media disk.

### MediaMan Pro Configuration

MediaMan Pro adds extensive configuration options. Here are the key environment variables you can set in your `.env` file:

```bash
# UUID Support
MEDIAMAN_USE_UUID=true

# API & UI Control
MEDIAMAN_ENABLE_API=true
MEDIAMAN_ENABLE_UI=true

# Versioning
MEDIAMAN_VERSIONING_ENABLED=true
MEDIAMAN_MAX_VERSIONS=10
MEDIAMAN_VERSION_AUTO_CLEANUP=true

# Tagging
MEDIAMAN_TAGGING_ENABLED=true
MEDIAMAN_TAG_AUTO_SLUG=true

# Rate Limiting
MEDIAMAN_RATE_LIMITING_ENABLED=true
MEDIAMAN_RATE_KEY_STRATEGY=user
MEDIAMAN_RATE_UPLOAD_REQUESTS=60
MEDIAMAN_RATE_UPLOAD_MINUTES=1
MEDIAMAN_RATE_API_REQUESTS=100
MEDIAMAN_RATE_API_MINUTES=1
MEDIAMAN_RATE_BATCH_REQUESTS=10
MEDIAMAN_RATE_BATCH_MINUTES=60

# Batch Operations
MEDIAMAN_BATCH_ENABLED=true
MEDIAMAN_BATCH_USE_QUEUE=true
MEDIAMAN_BATCH_QUEUE=media
MEDIAMAN_MAX_BATCH_FILES=100
MEDIAMAN_BATCH_CHUNK_SIZE=10

# Caching
MEDIAMAN_CACHE_ENABLED=true
MEDIAMAN_CACHE_DRIVER=redis
MEDIAMAN_CACHE_TTL=3600
MEDIAMAN_CACHE_PREFIX=mediaman
MEDIAMAN_CACHE_TAGS=true

# Image Conversions
MEDIAMAN_CONVERSION_DRIVER=gd
MEDIAMAN_CONVERSION_QUALITY=90
MEDIAMAN_WEBP_ENABLED=true
MEDIAMAN_WEBP_QUALITY=85
MEDIAMAN_RESPONSIVE_ENABLED=true
MEDIAMAN_BLURHASH_ENABLED=true
MEDIAMAN_WATERMARK_ENABLED=false
MEDIAMAN_WATERMARK_PATH=watermark.png

# Security
MEDIAMAN_MIME_VALIDATION=true
MEDIAMAN_MIME_STRICT=true
MEDIAMAN_SIGNED_URLS=false
MEDIAMAN_SIGNED_URL_EXPIRATION=3600
MEDIAMAN_SANITIZE_FILENAME=true
MEDIAMAN_FILENAME_LOWERCASE=true
MEDIAMAN_REMOVE_SPECIAL_CHARS=true

# Monitoring & Logging
MEDIAMAN_MONITORING_ENABLED=true
MEDIAMAN_LOG_CHANNEL=stack
MEDIAMAN_LOG_UPLOAD=true
MEDIAMAN_LOG_DELETE=true
MEDIAMAN_LOG_CONVERSION=true
MEDIAMAN_LOG_SECURITY=true
MEDIAMAN_METRICS_ENABLED=true
MEDIAMAN_AUDIT_ENABLED=true
MEDIAMAN_AUDIT_TRACK_USER=true

# OpenAPI Documentation
MEDIAMAN_OPENAPI_ENABLED=true
MEDIAMAN_OPENAPI_ROUTE=mediaman/docs
MEDIAMAN_OPENAPI_TITLE="MediaMan API"
MEDIAMAN_OPENAPI_VERSION=1.0.0

# License Management (Optional)
MEDIAMAN_LICENSE_ENABLED=false
MEDIAMAN_LICENSE_KEY=your-license-key-here
MEDIAMAN_LICENSE_URL=https://api.mediaman.dev/validate
MEDIAMAN_ALLOW_LOCALHOST=true
MEDIAMAN_LICENSE_CACHE_TTL=86400
```

For detailed configuration options, see the [MediaMan Pro Features](#mediaman-pro-features-1) section.

## Media

### Upload media

You should use the `FarhanShares\MediaMan\MediaUploader` class to handle file uploads. You can upload, create a record in the database & store the file in the filesystem in one go.

```php
$file  = $request->file('file')
$media = MediaUploader::source($file)->upload();
```

The file will be stored in the default disk & bundled in the default collection specified in the mediaman config. The file size will be stored in the database & the file name will be sanitized automatically.

However, you can do a lot more, not just stick to the defaults.

```php
$file  = $request->file('file')
$media = MediaUploader::source($file)
            ->useName('Custom name')
            ->useFileName('custom-name.png')
            ->useCollection('Images')
            ->useDisk('media')
            ->useData([
                'additional_data' => 'will be stored as json',
                'use_associative_array' => 'to store any data you want to be with the file',
            ])
            ->upload();
```

If the collection doesn't exist, it'll be created on the fly. You can read more about collections below.

**Q: What happens if I don't provide a unique file name in the above process?**

A: Don't worry, MediaMan manages uploading in a smart & safe way. Files are stored in the disk in a way that conflicts are barely going to happen. When storing in the disk, MediaMan will create a directory in the disk with a format of: `mediaId-hash` & put the file inside of it. Anything related to the file will have it's own little house.

**Q: But why? Won't I get a bunch of directories?**

A: Yes, you'll. If you want, extend the `FarhanShares\MediaMan\Models\Media` model & you can customize however you like. Finally point your customized model in the mediaman config. But we recommend sticking to the default, thus you don't need to worry about file conflicts. A hash is added along with the mediaId, hence users won't be able to guess & retrieve a random file. More on customization will be added later.

**Reminder: MediaMan treats any file (instance of `Illuminate\Http\UploadedFile`) as a media source. If you want a certain file types can be uploaded, you can use Laravel's validator.**

### Retrieve media

You can use any Eloquent operation to retrieve a media plus we've added findByName().

```php
// by id
$media = Media::find(1);

// by name
$media = Media::findByName('media-name');

// with collections
$media = Media::with('collections')->find(1);
```

An instance of Media has the following attributes:

```php
'id' => int
'name' => string
'file_name' => string
'extension' => string
'type' => string
'mime_type' => string
'size' =>  int // in bytes
'friendly_size' => string // in human readable format
'media_uri' => string  // media URI for the original file. Usage in Blade: {{ asset($media->media_uri) }}.
'media_url' => string  // direct URL for the original file.
'disk' =>  string
'data' => array // casts as array
'created_at' =>  string
'updated_at' => string
'collections' => object // eloquent collection
```

You have access to some methods along with the attributes:

```php
// $media->mime_type => 'image/jpg'
$media->isOfType('image') // true

// get the media url, accepts optional '$conversionName' argument
$media->getUrl('conversion-name')

// get the path to the file on disk, accepts optional '$conversionName' argument
$media->getPath('conversion-name')

// get the directory where the media stored on disk
$media->getDirectory()
```

### Update media

With an instance of `Media`, you can perform various update operations:

```php
$media = Media::first();
$media->name = 'New name';
$media->data = ['additional_data' => 'additional data']
$media->save()
```

#### Update Media Name

```php
$media = Media::first();
$media->name = 'New Display Name';
$media->save();
```

#### Update Additional Data

```php
$media->data = ['additional_data' => 'new additional data'];
$media->save();
```

#### Remove All Additional Data

```php
$media->data = [];
$media->save();
```

#### Update Media File Name

Updating the media file name will also rename the actual file in storage.

```php
$media->file_name = 'new_filename.jpg';
$media->save();
```

#### Change Media Storage Disk

Moving the media to another storage disk will transfer the actual file to the specified disk.

```php
$media->disk = 's3';  // Example disk name, ensure it exists
$media->save();
```

**Heads Up!** There's a config regarding disk accessibility checks for read-write operations: `check_disk_accessibility`.

**Disk Accessibility Checks**:

* *Pros*: Identifies potential disk issues early on.

* *Cons*: Can introduce performance delays.

**Tip**: Enabling this check can preemptively spot storage issues, but may add minor operational delays. Consider your system's needs and decide accordingly.

### Delete media

You can delete a media by calling delete() method on an instance of Media.

```php
$media = Media::first();
$media->delete()
```

Or you delete media like this:

```php
Media::destroy(1);
Media::destroy([1, 2, 3]);
```

**Note:** When a Media instance gets deleted, file will be removed from the filesystem, all the association with your App Models & MediaCollection will be removed as well.

**Heads Up!:** You should not delete media using queries, e.g. `Media::where('name', 'the-file')->delete()`, this will not trigger deleted event & the file won't be deleted from the filesystem. Read more about it in the [official documentation](https://laravel.com/docs/master/eloquent#deleting-models-using-queries).

-----

## Media & Models

### Associate media

MediaMan exposes easy to use API via `FarhanShares\MediaMan\HasMedia` trait for associating media items to models. Use the trait in your App Model & you are good to go.

```php
use Illuminate\Database\Eloquent\Model;
use FarhanShares\MediaMan\Traits\HasMedia;

class Post extends Model
{
    use HasMedia;
}
```

This will establish the relationship between your App Model and the Media Model.

Once done, you can associate media to the model as demonstrated below.

The first parameter of the `attachMedia()` method can either be a media model / id or an iterable collection of models / ids.

```php
$post = Post::first();

// Associate in the default channel
$post->attachMedia($media); // or 1 or [1, 2, 3] or collection of media models

// Associate in a custom channel
$post->attachMedia($media, 'featured-image');
```

`attachMedia()` returns number of media attached (int) on success & null on failure.

### Retrieve media of a model

Apart from that, `HasMedia` trait enables your App Models retrieving media conveniently.

```php
// All media from the default channel
$post->getMedia();

// All media from the specified channel
$post->getMedia('featured-image');
```

Though the original media URL is appended with the Media model, it's nice to know that you have a getUrl() method available.

```php
$media =  $post->getMedia('featured-image');
// getUrl() accepts only one optional argument: name of the conversion
// leave it empty to get the original media URL
$mediaOneUrl = $media[0]->getUrl();
```

It might be a common scenario for most of the Laravel apps to use the first media item more often, hence MediaMan has dedicated methods to retrieve the first item among all associated media.

```php
// First media item from the default channel
$post->getFirstMedia();

// First media item from the specified channel
$post->getFirstMedia('featured-image');

// URL of the first media item from the default channel
$post->getFirstMediaUrl();

// URL of the first media item from the specified channel
$post->getFirstMediaUrl('featured-image');
```

*Tip:* getFirstMediaUrl() accepts two optional arguments: channel name & conversion name

### Disassociate media

You can use `detachMedia()` method which is also shipped with HasMedia trait to disassociate media from model.

```php
// Detach the specified media
$post->detachMedia($media); // or 1 or [1, 2, 3] or collection of media models

// Detach all media from all channels
$post->detachMedia();

// Detach all media of the default channel
$post->clearMediaChannel();

// Detach all media of the specific channel
$post->clearMediaChannel('channel-name');
```

`detachMedia()` returns number of media detached (int) on success & null on failure.

### Synchronize association / disassociation

You can sync media of a specified channel using the syncMedia() method. This provides a flexible way to maintain the association between your model and the related media records. The default method signature look like this: `syncMedia($media, string $channel = 'default', array $conversions = [], $detaching = true)`

This will remove the media that aren't in the provided list and add those which aren't already attached if $detaching is truthy.

```php
$post = Post::first();
$media = Media::find(1); // model instance or just an media id: 1, or array of id: [1, 2, 3] or a collection of media models

// Sync media in the default channel (the $post will have only $media and others will be removed)
$post->syncMedia($media);
```

**Heads Up!:** None of the attachMedia, detachMedia or syncMedia methods deletes the file, it just does as it means. Refer to delete media section to know how to delete a media.

-----

## Collections

MediaMan provides collections to bundle your media for better media management. Use `FarhanShares\MediaMan\Models\MediaCollection` to deal with media collections.

### Create collection

Collections are created on thy fly if it doesn't exist while uploading file.

```php
$media = MediaUploader::source($request->file('file'))
            ->useCollection('My Collection')
            ->upload();
```

If you wish to create collection without uploading a file, you can do it, after all, it's an Eloquent model.

```php
MediaCollection::create(['name' => 'My Collection']);
```

### Retrieve collection

You can retrieve a collection by it's id or name.

```php
MediaCollection::find(1);
MediaCollection::findByName('My Collection');

// Retrieve the bound media as well
MediaCollection::with('media')->find(1);
MediaCollection::with('media')->findByName('My Collection');
```

### Update collection

You can update a collection name. It doesn't really have any other things to update.

```php
$collection = MediaCollection::findByName('My Collection');
$collection->name = 'Our Collection'
$collection->save();
```

### Delete collection

You can delete a collection using an instance of MediaCollection.

```php
$collection = MediaCollection::find(1);
$collection->delete()
```

This won't delete the media from disk but the bindings will be removed from database.

*Heads Up!* deleteWithMedia() is a conceptual method that hasn't implemented yet, create a feature request if you need this. PRs are very much appreciated.

------

## Media & Collections

The relationship between `Media` & `MediaCollection` are already configured. You can bind, unbind & sync binding & unbinding easily. The method signatures are similar for `Media::**Collections()` and `MediaCollection::**Media()`.

### Bind media

```php
$collection = MediaCollection::first();
// You can just pass a media model / id / name or an iterable collection of those
// e.g. 1 or [1, 2] or $media or [$mediaOne, $mediaTwo] or 'media-name' or ['media-name', 'another-media-name']
$collection->attachMedia($media);
```

`attachMedia()` returns number of media attached (int) on success & null on failure. Alternatively, you can use `Media::attachCollections()` to bind to collections from a media model instance.

*Heads Up!* Unlike `HasMedia` trait, you can not have channels on media collections.

### Unbind media

```php
$collection = MediaCollection::first();
// You can just pass a media model / id / name or an iterable collection of those
// e.g. 1 or [1, 2] or $media or [$mediaOne, $mediaTwo] or 'media-name' or ['media-name', 'another-media-name']
$collection->detachMedia($media);

// Detach all media by passing null / bool / empty-string / empty-array
$collection->detachMedia([]);
```

`detachMedia()` returns number of media detached (int) on success & null on failure. Alternatively, you can use `Media::detachCollections()` to unbind from collections from a media model instance.

### Synchronize binding & unbinding

```php
$collection = MediaCollection::first();
// You can just pass media model / id / name
$collection->syncMedia($media);

// You can even pass iterable list / collection
$collection->syncMedia(Media::all())
$collection->syncMedia([1, 2, 3, 4, 5]);
$collection->syncMedia([$mediaSix, $mediaSeven]);
$collection->syncMedia(['media-name', 'another-media-name']);

// Synchronize to having zero media by passing null / bool / empty-string / empty-array
$collection->syncMedia([]);
```

`syncMedia()` always returns an array containing synchronization status. Alternatively, you can use `Media::syncCollections()` to sync with collections from a media model instance.

## Conversions

You can specify a model to perform "conversions" when a media is attached to a channel.

MediaMan provides a fluent api to manipulate images. It uses the popular [intervention/image](https://github.com/Intervention/image) library under the hood. Resizing, adding watermark, converting to a different format or anything that is supported can be done. In short, You can utilize all functionalities from the library.

Conversions are registered globally. This means that they can be reused across your application, for i.e a Post and a User both can have the same sized thumbnail without having to register the same conversion twice.

To get started, you should first register a conversion in one of your application's service providers:

```php
use Intervention\Image\Image;
use FarhanShares\MediaMan\Facades\Conversion;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Conversion::register('thumb', function (Image $image) {
            // you have access to intervention/image library,
            // perform your desired conversions here
            return $image->fit(64, 64);
        });
    }
}
```

Once you've registered a conversion, you should configure a media channel to perform the conversion when media is attached to your model.

```php
class Post extends Model
{
    use HasMedia;

    public function registerMediaChannels()
    {
        $this->addMediaChannel('gallery')
             ->performConversions('thumb');
    }
}
```

From now on, whenever a media item is attached to the "gallery" channel, a converted image will be generated. You can get the url of the converted image as demonstrated below:

```php
// getFirstMediaUrl() accepts two optional arguments: channel name & conversion name
// you should provide channel name & conversion name to get the url
$post->getFirstMediaUrl('gallery', 'thumb');
```

*Tip:* The default channel name is `default`.

```php
// if you have multiple media associated & need to retrieve URLs you can do it with getUrl():
$media = $post->getMedia();
// getUrl() accepts only one optional argument: name of the conversion
// you should provide the conversion name to get the url
$mediaOneThumb = $media[0]->getUrl('thumb');
```

*Tip:* The `media_uri` and `media_url` are always appended with an instance of `Media`, these reflect the original file (and not the conversions).

-----

## MediaMan Pro Features

MediaMan Pro provides enterprise-grade features for production applications. All Pro features are designed with maximum customizability and can be enabled/disabled through configuration.

### Installation & Setup

After installing MediaMan, publish the Pro migrations:

```bash
# Publish Pro migrations
php artisan vendor:publish --tag=mediaman-migrations

# Run migrations
php artisan migrate
```

**Available Pro Migrations:**
- `add_uuid_to_mediaman_media.php` - Adds UUID support
- `add_performance_indexes_to_mediaman.php` - Performance optimization indexes
- `create_media_versions_table.php` - Versioning system
- `create_media_tags_tables.php` - Tagging system

### UUID Support

Use UUIDs instead of auto-incrementing IDs for obfuscated, non-sequential identifiers.

**Enable UUID:**

```php
// config/mediaman.php
'use_uuid' => env('MEDIAMAN_USE_UUID', true),
```

**Usage:**

```php
use FarhanShares\MediaMan\MediaUploaderPro;

// Upload with automatic UUID generation
$media = MediaUploaderPro::source($request->file('file'))->upload();

// Access UUID
echo $media->uuid; // "9b3c5e8a-4f2d-4a1b-9c3e-5d8a4f2d4a1b"

// Find by UUID
$media = Media::where('uuid', $uuid)->first();
```

**Benefits:**
- Non-sequential IDs prevent enumeration attacks
- Globally unique identifiers
- Safe for public-facing URLs
- Compatible with distributed systems

### Advanced Image Processing

MediaMan Pro includes powerful image processing features powered by Intervention Image.

**Configuration:**

```php
// config/mediaman.php
'conversions' => [
    'driver' => env('MEDIAMAN_CONVERSION_DRIVER', 'gd'), // gd or imagick
    'quality' => env('MEDIAMAN_CONVERSION_QUALITY', 90),
    'webp' => [
        'enabled' => env('MEDIAMAN_WEBP_ENABLED', true),
        'quality' => env('MEDIAMAN_WEBP_QUALITY', 85),
    ],
    'responsive' => [
        'enabled' => env('MEDIAMAN_RESPONSIVE_ENABLED', true),
        'breakpoints' => [320, 640, 768, 1024, 1366, 1920],
    ],
    'blurhash' => [
        'enabled' => env('MEDIAMAN_BLURHASH_ENABLED', true),
        'components_x' => 4,
        'components_y' => 3,
    ],
    'watermark' => [
        'enabled' => env('MEDIAMAN_WATERMARK_ENABLED', false),
        'path' => env('MEDIAMAN_WATERMARK_PATH', 'watermark.png'),
        'position' => 'bottom-right',
        'opacity' => 50,
    ],
],
```

**Responsive Images:**

```php
use FarhanShares\MediaMan\MediaUploaderPro;

$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions([
        'responsive' => [
            'breakpoints' => [320, 640, 1024, 1920],
            'quality' => 85,
        ]
    ])
    ->upload();

// Get responsive URLs
$urls = $media->getResponsiveUrls(); // Returns array of breakpoint URLs
```

**WebP Conversion:**

```php
$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions([
        'webp' => [
            'quality' => 85,
        ]
    ])
    ->upload();

// Get WebP URL
$webpUrl = $media->getUrl('webp');
```

**BlurHash (Image Placeholders):**

```php
$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions(['blurhash' => true])
    ->upload();

// Get BlurHash string
$blurHash = $media->data['blurhash'] ?? null;
```

**Watermarking:**

```php
$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions([
        'watermark' => [
            'path' => 'watermarks/logo.png',
            'position' => 'bottom-right', // center, top-left, top-right, bottom-left, bottom-right
            'opacity' => 50,
        ]
    ])
    ->upload();
```

**Smart Cropping:**

```php
$media = MediaUploaderPro::source($request->file('image'))
    ->withConversions([
        'thumbnail' => [
            'width' => 300,
            'height' => 300,
            'crop' => 'smart', // Automatically detects focal point
        ]
    ])
    ->upload();
```

### Versioning System

Track file history and restore previous versions.

**Enable Versioning:**

```php
// config/mediaman.php
'versioning' => [
    'enabled' => env('MEDIAMAN_VERSIONING_ENABLED', true),
    'max_versions' => env('MEDIAMAN_MAX_VERSIONS', 10),
    'auto_cleanup' => env('MEDIAMAN_VERSION_AUTO_CLEANUP', true),
],
```

**Usage:**

```php
use FarhanShares\MediaMan\Models\Media;

$media = Media::find(1);

// Create a new version
$version = $media->createVersion('Updated header image', auth()->id());

// Get all versions
$versions = $media->versions; // Ordered by version number DESC

// Restore a previous version
$oldVersion = $media->versions()->where('version_number', 2)->first();
$oldVersion->restore(); // Creates backup and restores

// Get version details
echo $version->version_number; // 1, 2, 3, etc.
echo $version->reason; // "Updated header image"
echo $version->file_path; // Path to versioned file
echo $version->created_by; // User ID who created version
```

**Automatic Versioning on Upload:**

```php
use FarhanShares\MediaMan\MediaUploaderPro;

// Replace existing media (creates version automatically)
$media = Media::find(1);
$newMedia = MediaUploaderPro::source($request->file('file'))
    ->replacingMedia($media)
    ->upload();

// Original file is saved as version 1
// New file becomes the current media
```

### Tagging System

Organize media with tags, track usage, and enable powerful search.

**Enable Tagging:**

```php
// config/mediaman.php
'tagging' => [
    'enabled' => env('MEDIAMAN_TAGGING_ENABLED', true),
    'auto_slug' => env('MEDIAMAN_TAG_AUTO_SLUG', true),
    'types' => ['user-defined', 'ai-generated', 'system'],
],
```

**Usage:**

```php
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Models\Tag;
use FarhanShares\MediaMan\MediaUploaderPro;

// Upload with tags
$media = MediaUploaderPro::source($request->file('image'))
    ->withTags(['nature', 'landscape', 'mountains'])
    ->upload();

// Attach tags to existing media
$media->attachTags(['sunset', 'golden-hour'], 'user-defined');

// Sync tags (removes old, adds new)
$media->syncTags(['nature', 'photography'], 'user-defined');

// Detach specific tags
$media->detachTags(['mountains']);

// Detach all tags
$media->detachAllTags();

// Get media tags
$tags = $media->tags;
foreach ($tags as $tag) {
    echo $tag->name; // "nature"
    echo $tag->slug; // "nature"
    echo $tag->type; // "user-defined"
    echo $tag->usage_count; // 42
}

// Find or create tags
$tag = Tag::findOrCreateByName('wildlife', 'user-defined');

// Popular tags
$popularTags = Tag::popular(10)->get(); // Top 10 most used tags

// Search media by tags
$media = Media::whereHas('tags', function ($query) {
    $query->whereIn('slug', ['nature', 'landscape']);
})->get();
```

**AI-Powered Tagging:**

```php
// Upload with AI tagging
$media = MediaUploaderPro::source($request->file('image'))
    ->withAI(['auto_tag', 'extract_text'])
    ->upload();

// AI-generated tags are automatically marked as type 'ai-generated'
$aiTags = $media->tags()->where('type', 'ai-generated')->get();
```

### Batch Operations

Upload multiple files efficiently with queue-based processing.

**Enable Batch Operations:**

```php
// config/mediaman.php
'batch' => [
    'enabled' => env('MEDIAMAN_BATCH_ENABLED', true),
    'use_queue' => env('MEDIAMAN_BATCH_USE_QUEUE', true),
    'queue_name' => env('MEDIAMAN_BATCH_QUEUE', 'media'),
    'max_files_per_batch' => env('MEDIAMAN_MAX_BATCH_FILES', 100),
    'chunk_size' => env('MEDIAMAN_BATCH_CHUNK_SIZE', 10),
],
```

**Usage:**

```php
use FarhanShares\MediaMan\BatchUploader;

$files = $request->file('files'); // Array of uploaded files

// Synchronous batch upload
$results = BatchUploader::source($files)
    ->toCollection('product-images')
    ->synchronously()
    ->upload();

foreach ($results as $media) {
    echo $media->id;
}

// Asynchronous batch upload (uses queues)
$batchId = BatchUploader::source($files)
    ->toCollection('product-images')
    ->withConversions(['thumbnail', 'webp'])
    ->withTags(['products', '2024'])
    ->onProgress(function ($processed, $total) {
        // Update progress bar or notification
    })
    ->upload();

// Check batch status
$status = BatchUploader::getBatchStatus($batchId);
echo $status['status']; // 'pending', 'processing', 'completed', 'failed'
echo $status['progress']; // 0-100
echo $status['processed']; // Number of files processed
echo $status['total']; // Total files
echo $status['errors']; // Array of errors if any
```

**Batch Upload with Progress Tracking:**

```php
// In your controller
public function uploadBatch(Request $request)
{
    $batchId = BatchUploader::source($request->file('files'))
        ->toCollection('gallery')
        ->upload();

    return response()->json(['batch_id' => $batchId]);
}

// Check progress endpoint
public function batchProgress($batchId)
{
    $status = BatchUploader::getBatchStatus($batchId);
    return response()->json($status);
}
```

### Query Caching

Improve performance with intelligent query caching and automatic invalidation.

**Enable Caching:**

```php
// config/mediaman.php
'cache' => [
    'enabled' => env('MEDIAMAN_CACHE_ENABLED', true),
    'driver' => env('MEDIAMAN_CACHE_DRIVER', 'redis'), // redis, memcached, file
    'ttl' => env('MEDIAMAN_CACHE_TTL', 3600), // 1 hour
    'prefix' => env('MEDIAMAN_CACHE_PREFIX', 'mediaman'),
    'tags_enabled' => env('MEDIAMAN_CACHE_TAGS', true), // Requires Redis/Memcached
],
```

**Usage:**

```php
use FarhanShares\MediaMan\Cache\MediaCacheManager;

$cacheManager = app(MediaCacheManager::class);

// Get media from cache (auto-caches if not exists)
$media = $cacheManager->getMedia(1);
$mediaByUuid = $cacheManager->getMedia($uuid, true);

// Cache media URL
$url = $cacheManager->cacheUrl($mediaId, 'thumbnail');

// Warm cache for multiple media
$cacheManager->warmCache([1, 2, 3, 4, 5]);

// Invalidate specific media cache
$cacheManager->invalidateMedia($media);

// Invalidate collection cache
$cacheManager->invalidateCollection('product-images');

// Clear all media cache
$cacheManager->flush();
```

**Automatic Cache Invalidation:**

Cache is automatically invalidated when:
- Media is updated or deleted
- Media associations change
- Conversions are processed
- Tags are modified

### Rate Limiting

Protect your application from abuse with flexible rate limiting.

**Enable Rate Limiting:**

```php
// config/mediaman.php
'rate_limiting' => [
    'enabled' => env('MEDIAMAN_RATE_LIMITING_ENABLED', true),
    'key_strategy' => env('MEDIAMAN_RATE_KEY_STRATEGY', 'user'), // user, ip, session, fingerprint

    'limiters' => [
        'upload' => [
            'requests' => env('MEDIAMAN_RATE_UPLOAD_REQUESTS', 60),
            'per_minutes' => env('MEDIAMAN_RATE_UPLOAD_MINUTES', 1),
        ],
        'api' => [
            'requests' => env('MEDIAMAN_RATE_API_REQUESTS', 100),
            'per_minutes' => env('MEDIAMAN_RATE_API_MINUTES', 1),
        ],
        'batch' => [
            'requests' => env('MEDIAMAN_RATE_BATCH_REQUESTS', 10),
            'per_minutes' => env('MEDIAMAN_RATE_BATCH_MINUTES', 60),
        ],
    ],
],
```

**Apply to Routes:**

```php
use FarhanShares\MediaMan\Http\Middleware\MediaManRateLimiter;

// Apply to upload routes
Route::post('/upload', [UploadController::class, 'upload'])
    ->middleware(MediaManRateLimiter::class . ':upload');

// Apply to batch uploads
Route::post('/upload/batch', [UploadController::class, 'uploadBatch'])
    ->middleware(MediaManRateLimiter::class . ':batch');

// Apply to API routes
Route::middleware([MediaManRateLimiter::class . ':api'])
    ->prefix('api/mediaman')
    ->group(function () {
        Route::get('/media', [MediaController::class, 'index']);
    });
```

**Rate Limit Strategies:**

- **user**: Rate limit per authenticated user ID (falls back to IP for guests)
- **ip**: Rate limit per IP address
- **session**: Rate limit per session ID
- **fingerprint**: Rate limit per request fingerprint

**Custom Rate Limiters:**

```php
// config/mediaman.php
'rate_limiting' => [
    'limiters' => [
        'download' => [
            'requests' => 1000,
            'per_minutes' => 60,
        ],
    ],
],

// Apply in routes
Route::get('/media/{id}/download', [MediaController::class, 'download'])
    ->middleware(MediaManRateLimiter::class . ':download');
```

### Security Features

Enterprise-grade security features to protect your application.

**Security Configuration:**

```php
// config/mediaman.php
'security' => [
    'mime_validation' => [
        'enabled' => env('MEDIAMAN_MIME_VALIDATION', true),
        'strict' => env('MEDIAMAN_MIME_STRICT', true),
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'video/mp4',
            // Add your allowed MIME types
        ],
    ],
    'signed_urls' => [
        'enabled' => env('MEDIAMAN_SIGNED_URLS', false),
        'expiration' => env('MEDIAMAN_SIGNED_URL_EXPIRATION', 3600), // 1 hour
    ],
    'filename_sanitization' => [
        'enabled' => env('MEDIAMAN_SANITIZE_FILENAME', true),
        'lowercase' => env('MEDIAMAN_FILENAME_LOWERCASE', true),
        'remove_special_chars' => env('MEDIAMAN_REMOVE_SPECIAL_CHARS', true),
    ],
],
```

**MIME Type Validation:**

```php
use FarhanShares\MediaMan\MediaUploaderPro;
use FarhanShares\MediaMan\Exceptions\InvalidMimeTypeException;

try {
    $media = MediaUploaderPro::source($request->file('file'))
        ->validateMime(['image/jpeg', 'image/png'])
        ->upload();
} catch (InvalidMimeTypeException $e) {
    return response()->json(['error' => 'Invalid file type'], 422);
}
```

**Signed URLs:**

```php
use FarhanShares\MediaMan\Managers\SecurityManager;

$security = app(SecurityManager::class);

// Generate signed URL (expires in 1 hour)
$signedUrl = $security->generateSignedUrl($media);

// Generate signed URL with custom expiration
$signedUrl = $security->generateSignedUrl($media, now()->addHours(24));

// Verify signed URL (automatic in middleware)
if ($security->verifySignedUrl($url, $signature)) {
    // URL is valid
}
```

**Filename Sanitization:**

```php
// Automatic sanitization during upload
$media = MediaUploaderPro::source($request->file('file'))
    ->upload();

// Special characters removed, lowercase applied
// "My Photo (1).JPG" becomes "my-photo-1.jpg"
```

### Monitoring & Logging

Comprehensive monitoring and logging infrastructure for production applications.

**Enable Monitoring:**

```php
// config/mediaman.php
'monitoring' => [
    'enabled' => env('MEDIAMAN_MONITORING_ENABLED', true),
    'log_channel' => env('MEDIAMAN_LOG_CHANNEL', 'stack'),

    'events' => [
        'upload' => env('MEDIAMAN_LOG_UPLOAD', true),
        'delete' => env('MEDIAMAN_LOG_DELETE', true),
        'conversion' => env('MEDIAMAN_LOG_CONVERSION', true),
        'security' => env('MEDIAMAN_LOG_SECURITY', true),
    ],

    'metrics' => [
        'enabled' => env('MEDIAMAN_METRICS_ENABLED', true),
        'collectors' => [
            // 'FarhanShares\MediaMan\Monitoring\Collectors\StorageCollector',
            // 'FarhanShares\MediaMan\Monitoring\Collectors\PerformanceCollector',
        ],
    ],

    'audit' => [
        'enabled' => env('MEDIAMAN_AUDIT_ENABLED', true),
        'track_user' => env('MEDIAMAN_AUDIT_TRACK_USER', true),
    ],
],
```

**Usage:**

```php
use FarhanShares\MediaMan\Monitoring\MediaMonitor;

$monitor = app(MediaMonitor::class);

// Log upload event
$monitor->logUpload($media, [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// Log deletion
$monitor->logDelete($media, [
    'reason' => 'User requested deletion',
]);

// Log conversion
$monitor->logConversion($media, ['thumbnail', 'webp'], $duration);

// Log security event
$monitor->logSecurityEvent('invalid_mime_type', [
    'file' => $file->getClientOriginalName(),
    'mime' => $file->getMimeType(),
]);

// Get storage statistics
$stats = $monitor->getStorageStats();
echo $stats['total_files']; // 1542
echo $stats['total_size']; // 5368709120 (bytes)
echo $stats['by_disk']['s3']; // 3221225472
echo $stats['by_mime_type']['image/jpeg']; // 2147483648
```

**Event Listeners:**

MediaMan Pro automatically logs events:
- `MediaUploaded` - Logged with user, IP, and file details
- `MediaDeleted` - Logged with reason and context
- `ConversionProcessed` - Logged with duration and conversion types
- `SecurityViolation` - Logged with violation type and details

**Custom Metrics Collectors:**

```php
namespace App\Monitoring\Collectors;

use FarhanShares\MediaMan\Monitoring\Contracts\MetricCollector;

class CustomMetricCollector implements MetricCollector
{
    public function collect(): array
    {
        return [
            'custom_metric' => 'value',
        ];
    }
}

// Register in config/mediaman.php
'monitoring' => [
    'metrics' => [
        'collectors' => [
            \App\Monitoring\Collectors\CustomMetricCollector::class,
        ],
    ],
],
```

### UI Components

Ready-to-use UI components for Vue 3, React, Svelte, and Blade.

**Publish UI Assets:**

```bash
php artisan vendor:publish --tag=mediaman-ui
```

**Vue 3 Component:**

```vue
<template>
  <MediaUploader
    :collection="'products'"
    :conversions="['thumbnail', 'webp']"
    :tags="['product-images']"
    :multiple="true"
    :max-files="10"
    @upload-complete="handleUploadComplete"
    @upload-error="handleUploadError"
  />
</template>

<script setup>
import MediaUploader from '@/mediaman/vue/MediaUploader.vue';

const handleUploadComplete = (media) => {
  console.log('Uploaded:', media);
};

const handleUploadError = (error) => {
  console.error('Upload failed:', error);
};
</script>
```

**React Component:**

```jsx
import MediaUploader from './mediaman/react/MediaUploader';

function App() {
  const handleUploadComplete = (media) => {
    console.log('Uploaded:', media);
  };

  return (
    <MediaUploader
      collection="products"
      conversions={['thumbnail', 'webp']}
      tags={['product-images']}
      multiple={true}
      maxFiles={10}
      onUploadComplete={handleUploadComplete}
      onUploadError={(error) => console.error(error)}
    />
  );
}
```

**Blade Component (Alpine.js):**

```blade
<x-mediaman-upload
    collection="products"
    :conversions="['thumbnail', 'webp']"
    :tags="['product-images']"
    :multiple="true"
    :max-files="10"
/>
```

**Framework-Agnostic Core:**

```javascript
import MediaManCore from './mediaman/core/MediaManCore';

const uploader = new MediaManCore({
  uploadUrl: '/mediaman/upload',
  collection: 'products',
  conversions: ['thumbnail', 'webp'],
});

uploader.upload(files, {
  onProgress: (percent) => {
    console.log(`Upload progress: ${percent}%`);
  },
  onComplete: (media) => {
    console.log('Upload complete:', media);
  },
  onError: (error) => {
    console.error('Upload failed:', error);
  },
});
```

### API Documentation

Auto-generated OpenAPI/Swagger documentation for your MediaMan API.

**Enable API Documentation:**

```php
// config/mediaman.php
'openapi' => [
    'enabled' => env('MEDIAMAN_OPENAPI_ENABLED', true),
    'route' => env('MEDIAMAN_OPENAPI_ROUTE', 'mediaman/docs'),
    'title' => env('MEDIAMAN_OPENAPI_TITLE', 'MediaMan API'),
    'version' => env('MEDIAMAN_OPENAPI_VERSION', '1.0.0'),
],

'enable_api' => env('MEDIAMAN_ENABLE_API', true),
```

**Recommended: Using Scramble (Auto-Generation):**

```bash
composer require dedoc/scramble
```

```php
// config/scramble.php
'api_path' => 'api/mediaman',
'api_domain' => null,
```

Access documentation at: `http://yourapp.test/docs/api`

**Alternative: L5-Swagger:**

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

**Disable API Routes:**

```php
// .env
MEDIAMAN_ENABLE_API=false
```

### License Management

Built-in license validation for commercial deployments.

**Enable License Validation:**

```php
// config/mediaman.php
'license' => [
    'enabled' => env('MEDIAMAN_LICENSE_ENABLED', false),
    'key' => env('MEDIAMAN_LICENSE_KEY', null),
    'validation_url' => env('MEDIAMAN_LICENSE_URL', 'https://api.mediaman.dev/validate'),
    'allow_localhost' => env('MEDIAMAN_ALLOW_LOCALHOST', true),
    'cache_ttl' => env('MEDIAMAN_LICENSE_CACHE_TTL', 86400), // 24 hours
],
```

**Usage:**

```php
use FarhanShares\MediaMan\Managers\LicenseManager;

$license = app(LicenseManager::class);

// Validate license
if ($license->isValid()) {
    // License is valid
    $features = $license->getEnabledFeatures();
}

// Check specific feature
if ($license->hasFeature('batch-upload')) {
    // Feature is available
}

// Get license info
$info = $license->getLicenseInfo();
echo $info['type']; // 'pro', 'enterprise'
echo $info['expires_at']; // '2025-12-31'
echo $info['domain']; // 'yourapp.com'
```

**License Validation Flow:**

1. Localhost domains are automatically allowed
2. Production domains validate against license server
3. Validation results are cached for 24 hours
4. Features are gated based on license type

-----

## Performance Optimization

MediaMan Pro includes comprehensive performance optimizations:

### Database Indexes

40+ strategically placed indexes for optimal query performance:

```php
// Automatically applied with migration:
// add_performance_indexes_to_mediaman.php

// Examples:
// - Media table: disk, mime_type, created_at, composite indexes
// - Media-Model pivot: model_type + model_id, channel
// - Collections: name, created_at
// - Versions: media_id + version_number
// - Tags: slug, usage_count (for popular tags)
// - Full-text search on media names (MySQL/PostgreSQL)
```

**Performance Gains:**
- 10-100x faster queries on large datasets
- Optimized collection lookups
- Efficient tag-based searches
- Fast version history retrieval

### Query Optimization Best Practices

```php
// Use eager loading to avoid N+1 queries
$media = Media::with(['collections', 'tags', 'versions'])->get();

// Use caching for frequently accessed media
$cacheManager->warmCache($mediaIds);

// Use database indexes for searches
$media = Media::where('disk', 's3')
    ->where('mime_type', 'image/jpeg')
    ->whereBetween('created_at', [$start, $end])
    ->get(); // Uses composite index

// Batch operations for bulk uploads
BatchUploader::source($files)->upload(); // Uses queues
```

### Caching Strategy

```php
// Cache frequently accessed media
$cacheManager->warmCache([1, 2, 3, 4, 5]);

// Cache media URLs
$url = $cacheManager->cacheUrl($mediaId, 'thumbnail');

// Automatic invalidation on updates
// No stale cache issues
```

### Lazy Loading

```php
// Load relationships only when needed
$media = Media::find(1);
$tags = $media->tags; // Loaded on-demand

// Or use eager loading when you know you'll need them
$media = Media::with('tags')->find(1);
```

-----

## Upgrade Guide to MediaMan v1.x

If you're upgrading from a previous version of MediaMan, rest assured that the transition is fairly straightforward. Here's what you need to know:

### Changes

* **Introduction of `media_uri`**:
  In this release, we've introduced a new attribute called `media_uri`. This provides the URI for the original file. When you want to generate a full URL for use in Blade, you'd use it like so:

  ```blade
  {{ asset($media->media_uri) }}
  ```

* **Modification to `media_url`**:
  In previous versions, `media_url` used to act like what `media_uri` does now. Starting from v1.0.0, `media_url` will directly give you the absolute URL for the original file.

### Steps to Upgrade

1. **Update the Package**:
   Run the composer update command to get the latest version.

   ```bash
   composer update farhanshares/laravel-mediaman
   ```

2. **Review Your Blade Files**:
   If you previously used `media_url` with the `asset()` helper, like:

   ```blade
   {{ asset($media->media_url) }}
   ```

   Update it to:

   ```blade
   {{ asset($media->media_uri) }}
   ```

   If you used `media_url` without `asset()`, there's no change required.

3. **Run Any New Migrations** (if applicable):
   Always check for new migrations and run them to ensure your database schema is up-to-date.

4. **Test Your Application**:
   As always, after an upgrade, ensure you test your application thoroughly, especially the parts where media files are used, to make sure everything works as expected.

Thank you for using MediaMan and we hope you enjoy the improvements in v1.0.0! If you face any issues, feel free to open a ticket on GitHub.

## Contribution and License

If you encounter a bug, please consider opening an issue. Feature Requests & PRs are welcome.

The MIT License (MIT). Please read [License File](LICENSE.md) for more information.
