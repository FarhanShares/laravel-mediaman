<?php

namespace FarhanShares\MediaMan\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use FarhanShares\MediaMan\MediaChannel;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\MediaUploader;
use FarhanShares\MediaMan\Models\MediaCollection;


class MediaCollectionTest extends TestCase
{
    const DEFAULT_DISK = 'default';

    protected $file;

    protected $media;

    protected $mediaCollection;


    protected function setUp(): void
    {
        parent::setUp();

        // Use a test disk as the default disk...
        Config::set('mediaman.disk', self::DEFAULT_DISK);

        // Create a test filesystem for the default disk...
        Storage::fake(self::DEFAULT_DISK);

        $this->media = resolve(config('mediaman.models.media'));
        $this->mediaCollection = resolve(config('mediaman.models.collection'));

        $this->file = UploadedFile::fake()->image('sample-file.jpg');
    }

    /** @test */
    public function it_can_create_a_collection()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'test-collection'
        ]);

        $this->assertEquals('test-collection', $collection->name);
    }

    /** @test */
    public function we_can_attach_media_to_a_collection()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'my-collection'
        ]);

        MediaUploader::source($this->file)->upload();
        $media = $this->media::latest()->first();

        $media->collections()->attach($collection->id);

        $this->assertEquals('my-collection', $media->collections()->first()->name);
    }
}
