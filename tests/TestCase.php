<?php

namespace FarhanShares\MediaMan\Tests;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as BaseTestCase;
use FarhanShares\MediaMan\MediaManServiceProvider;
use FarhanShares\MediaMan\Tests\Factories\MediaFactory;

class TestCase extends BaseTestCase
{

    const DEFAULT_DISK = 'default';

    protected $file;

    protected $fileOne;

    protected $fileTwo;

    protected $media;

    protected $mediaCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Use a test disk as the default disk...
        Config::set('mediaman.disk', self::DEFAULT_DISK);

        // Create a test filesystem for the default disk...
        Storage::fake(self::DEFAULT_DISK);

        // Media & MediaCollection models
        $this->media = resolve(config('mediaman.models.media'));
        $this->mediaCollection = resolve(config('mediaman.models.collection'));

        // Fake uploaded files
        $this->fileOne = UploadedFile::fake()->image('file-one.jpg');
        $this->fileTwo = UploadedFile::fake()->image('file-two.jpg');
    }

    /**
     * Create one or more media records using the class-based test factory.
     *
     * @param int $count
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection|\FarhanShares\MediaMan\Models\Media
     */
    protected function createMedia(int $count = 1, array $attributes = [])
    {
        $factory = MediaFactory::new();

        if (!empty($attributes)) {
            $factory = $factory->state($attributes);
        }

        if ($count > 1) {
            return $factory->count($count)->create();
        }

        return $factory->create();
    }

    protected function getPackageProviders($app)
    {
        return [
            MediaManServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Load migrations
        $app['migrator']->path(__DIR__ . '/../database/migrations');
    }
}
