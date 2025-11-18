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
            $driver = config('mediaman.conversions.driver', 'gd');
            return new ImageManager(['driver' => $driver]);
        });

        // Register Cache Manager
        $this->app->singleton(\FarhanShares\MediaMan\Cache\MediaCacheManager::class);

        // Register Monitor
        $this->app->singleton(\FarhanShares\MediaMan\Monitoring\MediaMonitor::class);

        // Register Batch Uploader as a helper
        $this->app->bind(\FarhanShares\MediaMan\BatchUploader::class);
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
            __DIR__ . '/../database/migrations/add_performance_indexes_to_mediaman.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_add_performance_indexes_to_mediaman.php'),
            __DIR__ . '/../database/migrations/create_media_versions_table.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 3) . '_create_media_versions_table.php'),
            __DIR__ . '/../database/migrations/create_media_tags_tables.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 4) . '_create_media_tags_tables.php'),
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

        // Register event listeners
        $this->registerEventListeners();

        // Configure logging channel
        $this->configureLogging();

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MediamanPublishConfigCommand::class,
                MediamanPublishMigrationCommand::class,
            ]);
        }
    }

    /**
     * Register event listeners from config
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        $listeners = config('mediaman.monitoring.listeners', []);

        foreach ($listeners as $event => $eventListeners) {
            foreach ($eventListeners as $listener) {
                \Illuminate\Support\Facades\Event::listen($event, $listener);
            }
        }
    }

    /**
     * Configure logging channel
     *
     * @return void
     */
    protected function configureLogging(): void
    {
        if (!config('mediaman.monitoring.enabled')) {
            return;
        }

        $channel = config('mediaman.monitoring.log_channel', 'mediaman');

        // Add MediaMan logging channel if it doesn't exist
        if (!isset(config('logging.channels')[$channel])) {
            config([
                "logging.channels.{$channel}" => [
                    'driver' => 'daily',
                    'path' => storage_path("logs/{$channel}.log"),
                    'level' => env('LOG_LEVEL', 'debug'),
                    'days' => 14,
                ],
            ]);
        }
    }
}
