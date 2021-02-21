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
    /** @test */
    public function it_can_create_a_collection()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'test-collection'
        ]);

        $this->assertEquals(2, $collection->id);
        $this->assertEquals('test-collection', $collection->name);
    }

    /** @test */
    public function it_can_retrieve_media_of_a_collection()
    {

        MediaUploader::source($this->fileOne)
            ->useName('file-1')
            ->useCollection('images')
            ->upload();

        MediaUploader::source($this->fileTwo)
            ->useName('file-2')
            ->useCollection('images')
            ->upload();

        $imageCollection = $this->mediaCollection->findByName('images');

        $one = $imageCollection->media[0];
        $two = $imageCollection->media[1];

        $this->assertEquals(2, $imageCollection->count());

        $this->assertEquals('file-1', $one->name);
        $this->assertEquals('file-2', $two->name);
    }

    /** @test */
    public function we_can_attach_media_to_a_collection()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'my-collection'
        ]);

        MediaUploader::source($this->fileOne)->upload();
        $media = $this->media::latest()->first();

        $media->collections()->sync($collection->id);

        $this->assertEquals('my-collection', $media->collections()->first()->name);
    }

    /** @test */
    public function we_can_sync_a_collection_of_media()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'another-collection'
        ]);

        MediaUploader::source($this->fileOne)->upload();
        $media = $this->media::latest()->first();

        $media->collections()->sync([]);

        $this->assertEquals(null, $media->collections()->first());

        $media->syncCollection("another-collection");

        $this->assertEquals("another-collection", $media->collections[0]->name);
    }
}
