<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The default disk where files should be uploaded
    |--------------------------------------------------------------------------
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | The default collection name where files should reside.
    |--------------------------------------------------------------------------
    |
    */

    'collection' => 'Default',

    /*
    |--------------------------------------------------------------------------
    | The queue that should be used to perform image conversions
    |--------------------------------------------------------------------------
    |
    | Leave empty to use the default queue driver.
    |
    */

    'queue' => null,

    /*
    |--------------------------------------------------------------------------
    | UUID Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    | Enable UUID support for media files. When enabled, each media file will
    | have a UUID column for better security and obfuscation of sequential IDs.
    |
    */

    'use_uuid' => env('MEDIAMAN_USE_UUID', false),
    'uuid_column' => 'uuid',
    'expose_uuid_in_routes' => true, // Use UUID in URLs instead of ID

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'chunk_size' => 1024 * 1024 * 2, // 2MB chunks for large file uploads
    'queue_conversions' => env('MEDIAMAN_QUEUE_CONVERSIONS', true),
    'conversion_queue' => env('MEDIAMAN_CONVERSION_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Security Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'allowed_mimetypes' => env('MEDIAMAN_ALLOWED_MIMES', null), // null = all allowed
    'max_file_size' => env('MEDIAMAN_MAX_FILE_SIZE', 1024 * 1024 * 100), // 100MB default
    'sanitize_filenames' => true,
    'check_mime_type' => true,
    'virus_scan' => env('MEDIAMAN_VIRUS_SCAN', false),
    'signed_urls' => env('MEDIAMAN_SIGNED_URLS', false),
    'signed_url_expiration' => 60, // minutes

    /*
    |--------------------------------------------------------------------------
    | AI Features Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'ai' => [
        'enabled' => env('MEDIAMAN_AI_ENABLED', false),
        'auto_tag' => env('MEDIAMAN_AI_AUTO_TAG', false),
        'extract_text' => env('MEDIAMAN_AI_EXTRACT_TEXT', false),
        'generate_alt' => env('MEDIAMAN_AI_GENERATE_ALT', false),
        'face_detection' => env('MEDIAMAN_AI_FACE_DETECTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'enable_ui' => env('MEDIAMAN_ENABLE_UI', true),
    'ui_middleware' => ['web', 'auth'],
    'ui_route_prefix' => 'mediaman',
    'license_key' => env('MEDIAMAN_LICENSE_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | The fully qualified class name of the MediaMan models
    |--------------------------------------------------------------------------
    |
    */

    'models' => [
        'media'      => \FarhanShares\MediaMan\Models\Media::class,
        'collection' => \FarhanShares\MediaMan\Models\MediaCollection::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | The table names for MediaMan
    |--------------------------------------------------------------------------
    |
    */

    'tables' => [
        'media'            => 'mediaman_media',
        'collections'      => 'mediaman_collections',
        'collection_media' => 'mediaman_collection_media',
        'mediables'        => 'mediaman_mediables',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Check Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines whether the package should perform an
    | accessibility check (i.e., write and delete) when ensuring the disk is
    | writable. If set to true, a temporary file will be created and deleted
    | to validate write permissions on the specified disk.
    |
    */

    'check_disk_accessibility' => env('MEDIAMAN_CHECK_DISK_ACCESSIBILITY', false),

    /*
    |--------------------------------------------------------------------------
    | Image Processing Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'conversions' => [
        'responsive_breakpoints' => [320, 640, 768, 1024, 1920],
        'webp_quality' => 90,
        'thumbnail_size' => [150, 150],
        'enable_blurhash' => env('MEDIAMAN_ENABLE_BLURHASH', false),
        'watermark_path' => null, // Path to watermark image
        'watermark_position' => 'bottom-right',
    ],
];
