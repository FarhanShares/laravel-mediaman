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
        'file'       => App\Models\MediaManFile::class,
        'collection' => App\Models\MediaManCollection::class,
        'mediable'   => App\Models\MediaManMediable::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | The table names for MediaMan
    |--------------------------------------------------------------------------
    |
    */

    'tables' => [
        'files'           => 'mediaman_files',
        'collections'     => 'mediaman_collections',
        'collection_file' => 'mediaman_collection_file',
        'mediables'       => 'mediaman_mediables',
    ],
];
