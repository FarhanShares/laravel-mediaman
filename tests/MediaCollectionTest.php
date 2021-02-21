<?php

namespace FarhanShares\MediaMan\Tests;

use FarhanShares\MediaMan\MediaUploader;


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
}
