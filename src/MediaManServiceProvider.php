<?php

namespace FarhanShares\MediaMan;

use Illuminate\Support\ServiceProvider;

class MediaManServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mediaman.php',
            'mediaman'
        );

        $this->app->singleton(ConversionRegistry::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Migrations
        if (!class_exists('CreateMediaManCollectionFileTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_mediaman_collection_file_table.stub' => database_path(
                    'migrations/mediaman/' . date('Y_m_d_His', time()) . '_create_mediaman_collection_file_table.php'
                ),
            ], 'migrations');
        }

        if (!class_exists('CreateMediaManCollectionsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_mediaman_collections_table.stub' => database_path(
                    'migrations/mediaman/' . date('Y_m_d_His', time()) . '_create_mediaman_collections_table.php'
                ),
            ], 'migrations');
        }

        if (!class_exists('CreateMediaManFilesTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_mediaman_files_table.stub' => database_path(
                    'migrations/mediaman/' . date('Y_m_d_His', time()) . '_create_mediaman_files_table.php'
                ),
            ], 'migrations');
        }

        if (!class_exists('CreateMediamanMediablesTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_mediaman_mediables_table.stub' => database_path(
                    'migrations/mediaman/' . date('Y_m_d_His', time()) . '_create_mediaman_mediables_table.php'
                ),
            ], 'migrations');
        }

        // Config
        $this->publishes([
            __DIR__ . '/../config/mediaman.php' => config_path('mediaman.php'),
        ], 'config');
    }
}
