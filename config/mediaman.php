<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The default disk where files should be uploaded
    |--------------------------------------------------------------------------
    |
    */

    'disk' => 'local',

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
    | The fully qualified class name of the MediaMan models
    |--------------------------------------------------------------------------
    |
    */

    'models' => [
        'media'      => \FarhanShares\MediaMan\Models\Media::class,
        'collection' => \FarhanShares\MediaMan\Models\MediaCollection::class,
        'mediable'   => \FarhanShares\MediaMan\Models\Mediable::class,
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
];
