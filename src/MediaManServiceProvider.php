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
        $migrations = [
            [
                'class_name'      => 'CreateMediaManCollectionMediaTable',
                'file_name'       => 'create_mediaman_collection_media_table'
            ],
            [
                'class_name'      => 'CreateMediaManCollectionsTable',
                'file_name'       => 'create_mediaman_collections_table'
            ],
            [
                'class_name'      => 'CreateMediaManMediaTable',
                'file_name'       => 'create_mediaman_media_table'
            ],
            [
                'class_name'      => 'CreateMediamanMediablesTable',
                'file_name'       => 'create_mediaman_mediables_table'
            ],
        ];

        $this->publishMigrations($migrations);

        // Config
        $this->publishes([
            __DIR__ . '/../config/mediaman.php' => config_path('mediaman.php'),
        ], 'config');
    }


    /**
     * Migration publishing helpers
     *
     */

    protected function getMigrationFileSource(string $name, string $ext = '.stub')
    {
        return __DIR__ . '/../database/migrations/' . $name . $ext;
    }

    protected function getMigrationFileDestination(string $name, string $ext = '.php')
    {
        return database_path(
            'migrations/' . date('Y_m_d_His', time()) . '_' . $name . $ext
        );
    }

    protected function publishMigrations(array $migrations, string $tag = 'migrations')
    {
        foreach ($migrations as $migration) {
            if (!class_exists($migration['class_name'])) {
                $this->publishes([
                    $this->getMigrationFileSource($migration['file_name']) =>
                    $this->getMigrationFileDestination($migration['file_name'])
                ], $tag);
            }
        }
    }
}
