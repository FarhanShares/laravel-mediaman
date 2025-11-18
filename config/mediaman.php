<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default disk where files should be uploaded
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default Collection
    |--------------------------------------------------------------------------
    |
    | The default collection name where files should reside.
    |
    */

    'collection' => 'Default',

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | The queue that should be used to perform image conversions
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
    | UI & API Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'enable_ui' => env('MEDIAMAN_ENABLE_UI', true),
    'enable_api' => env('MEDIAMAN_ENABLE_API', true),
    'ui_middleware' => ['web', 'auth'],
    'api_middleware' => ['api', 'auth:sanctum'],
    'ui_route_prefix' => 'mediaman',
    'api_route_prefix' => 'api/mediaman',
    'license_key' => env('MEDIAMAN_LICENSE_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Versioning Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    | Enable versioning to keep track of file changes
    |
    */

    'versioning' => [
        'enabled' => env('MEDIAMAN_VERSIONING_ENABLED', false),
        'max_versions' => env('MEDIAMAN_MAX_VERSIONS', 10), // Keep last N versions
        'auto_version_on_update' => true, // Create version before updating
        'storage_path' => 'versions', // Relative to media directory
    ],

    /*
    |--------------------------------------------------------------------------
    | Tagging Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'tagging' => [
        'enabled' => env('MEDIAMAN_TAGGING_ENABLED', true),
        'types' => ['user-defined', 'ai-generated', 'system'],
        'auto_slug' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'rate_limiting' => [
        'enabled' => env('MEDIAMAN_RATE_LIMITING_ENABLED', true),
        'key_strategy' => 'user', // user, ip, session, fingerprint

        'limiters' => [
            'upload' => [
                'requests' => env('MEDIAMAN_UPLOAD_RATE_LIMIT', 100),
                'per_minutes' => env('MEDIAMAN_UPLOAD_RATE_WINDOW', 60),
            ],
            'api' => [
                'requests' => env('MEDIAMAN_API_RATE_LIMIT', 200),
                'per_minutes' => env('MEDIAMAN_API_RATE_WINDOW', 60),
            ],
            'batch' => [
                'requests' => env('MEDIAMAN_BATCH_RATE_LIMIT', 10),
                'per_minutes' => env('MEDIAMAN_BATCH_RATE_WINDOW', 60),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Upload Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'batch' => [
        'enabled' => env('MEDIAMAN_BATCH_ENABLED', true),
        'use_queue' => env('MEDIAMAN_BATCH_USE_QUEUE', true),
        'queue' => env('MEDIAMAN_BATCH_QUEUE', 'default'),
        'max_files_per_batch' => env('MEDIAMAN_MAX_BATCH_FILES', 100),
        'timeout' => 3600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'cache' => [
        'enabled' => env('MEDIAMAN_CACHE_ENABLED', true),
        'store' => env('MEDIAMAN_CACHE_STORE', null), // null = default cache store
        'prefix' => 'mediaman',
        'ttl' => env('MEDIAMAN_CACHE_TTL', 3600), // seconds
        'tags_enabled' => env('MEDIAMAN_CACHE_TAGS', false), // Requires Redis or Memcached
        'allow_flush_all' => false, // Allow flushing all cache (use with caution)
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'search' => [
        'include_tags' => true,
        'include_metadata' => true,
        'driver' => 'database', // database, scout, meilisearch, algolia
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging Configuration (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'monitoring' => [
        'enabled' => env('MEDIAMAN_MONITORING_ENABLED', true),
        'log_channel' => env('MEDIAMAN_LOG_CHANNEL', 'mediaman'),
        'log_uploads' => true,
        'log_deletions' => true,
        'log_conversions' => true,
        'log_security_events' => true,
        'log_performance' => env('MEDIAMAN_LOG_PERFORMANCE', false),

        // Metric collectors (custom implementations)
        'collectors' => [
            // \App\Metrics\MediaManStatsDCollector::class,
            // \App\Metrics\MediaManPrometheusCollector::class,
        ],

        // Event listeners
        'listeners' => [
            \FarhanShares\MediaMan\Events\MediaUploaded::class => [
                \FarhanShares\MediaMan\Listeners\LogMediaUpload::class,
            ],
            \FarhanShares\MediaMan\Events\MediaDeleted::class => [
                \FarhanShares\MediaMan\Listeners\LogMediaDeletion::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAPI/Swagger Documentation (MediaMan Pro)
    |--------------------------------------------------------------------------
    |
    */

    'openapi' => [
        'enabled' => env('MEDIAMAN_OPENAPI_ENABLED', true),
        'route' => 'mediaman/docs',
        'middleware' => ['web'],
        'title' => 'MediaMan API Documentation',
        'description' => 'Complete API documentation for MediaMan Pro',
        'version' => '2.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the MediaMan models
    |
    */

    'models' => [
        'media' => \FarhanShares\MediaMan\Models\Media::class,
        'collection' => \FarhanShares\MediaMan\Models\MediaCollection::class,
        'version' => \FarhanShares\MediaMan\Models\MediaVersion::class,
        'tag' => \FarhanShares\MediaMan\Models\Tag::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | The table names for MediaMan
    |
    */

    'tables' => [
        'media' => 'mediaman_media',
        'collections' => 'mediaman_collections',
        'collection_media' => 'mediaman_collection_media',
        'mediables' => 'mediaman_mediables',
        'versions' => 'mediaman_versions',
        'tags' => 'mediaman_tags',
        'media_tags' => 'mediaman_media_tags',
    ],

    /*
    |--------------------------------------------------------------------------
    | Disk Accessibility Check
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
        'driver' => 'gd', // gd or imagick
    ],

    /*
    |--------------------------------------------------------------------------
    | License Configuration
    |--------------------------------------------------------------------------
    |
    | Configure license validation for MediaMan. Set enabled to false to
    | disable license checks. Localhost domains are always allowed.
    |
    */

    'licensing' => [
        'enabled' => env('MEDIAMAN_LICENSE_ENABLED', false),
        'key' => env('MEDIAMAN_LICENSE_KEY', null),
        'cache_ttl' => env('MEDIAMAN_LICENSE_CACHE_TTL', 3600), // 1 hour

        // LemonSqueezy integration (optional)
        'lemonsqueezy' => [
            'product_id' => env('LEMONSQUEEZY_PRODUCT_ID', null),
            'store_id' => env('LEMONSQUEEZY_STORE_ID', null),
        ],
    ],
];
