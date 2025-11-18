<?php

namespace FarhanShares\MediaMan;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use FarhanShares\MediaMan\Console\Commands\MediamanPublishConfigCommand;
use FarhanShares\MediaMan\Console\Commands\MediamanPublishMigrationCommand;
use FarhanShares\MediaMan\UI\License\LicenseManager;
use FarhanShares\MediaMan\Security\SecurityManager;
use FarhanShares\MediaMan\Conversions\ConversionManager;
use FarhanShares\MediaMan\Contracts\LicenseValidator;
use FarhanShares\MediaMan\Contracts\SecurityScanner;
use FarhanShares\MediaMan\Contracts\ConversionProcessor;

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

        // Register legacy ConversionRegistry
        $this->app->singleton(ConversionRegistry::class);

        // Register Pro services
        $this->app->singleton(LicenseValidator::class, LicenseManager::class);
        $this->app->singleton(LicenseManager::class);

        $this->app->singleton(SecurityScanner::class, SecurityManager::class);
        $this->app->singleton(SecurityManager::class);

        $this->app->singleton(ConversionProcessor::class, function ($app) {
            $manager = new ConversionManager($app->make(ImageManager::class));

            // Register default conversions
            $manager->registerThumbnail()
                ->registerResponsive()
                ->registerWebP()
                ->registerBlurHash()
                ->registerSmartCrop();

            // Register watermark if configured
            if (config('mediaman.conversions.watermark_path')) {
                $manager->registerWatermark();
            }

            return $manager;
        });

        $this->app->singleton(ConversionManager::class, function ($app) {
            return $app->make(ConversionProcessor::class);
        });

        // Register ImageManager from Intervention Image
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(['driver' => 'gd']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/create_mediaman_tables.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His', time()) . '_create_mediaman_tables.php'),
            __DIR__ . '/../database/migrations/add_uuid_to_mediaman_media.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_add_uuid_to_mediaman_media.php'),
        ], 'migrations');

        // Config
        $this->publishes([
            __DIR__ . '/../config/mediaman.php' => config_path('mediaman.php'),
        ], 'config');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mediaman');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/mediaman'),
        ], 'views');

        // Assets
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/mediaman'),
        ], 'assets');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MediamanPublishConfigCommand::class,
                MediamanPublishMigrationCommand::class,
            ]);
        }
    }
}
