<?php

namespace FarhanShares\MediaMan\Tests;


use FarhanShares\MediaMan\Models\Media;
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

    /** @test */
    public function it_can_sync_media_of_a_collection()
    {
        $mediaOne = MediaUploader::source($this->fileOne)
            ->useName('video-1')
            ->useCollection('Videos')
            ->upload();

        $mediaTwo = MediaUploader::source($this->fileTwo)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();

        $mediaThree = MediaUploader::source($this->fileTwo)
            ->useName('image-2')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');
        $this->assertEquals(2, $imageCollection->media()->count());

        // detach all media by boolean true
        $imageCollection->syncMedia(true);
        $this->assertEquals(0, $imageCollection->media()->count());
        // sync media by media id, name or model object
        $imageCollection->syncMedia($mediaOne->id);
        $this->assertEquals(1, $imageCollection->media()->count());
        $imageCollection->syncMedia($mediaTwo->name);
        $this->assertEquals(1, $imageCollection->media()->count());
        $imageCollection->syncMedia($mediaThree);
        $this->assertEquals(1, $imageCollection->media()->count());

        // detach all media by boolean false
        $imageCollection->syncMedia(false);
        $this->assertEquals(0, $imageCollection->media()->count());
        // sync media by array of media id or name
        $imageCollection->syncMedia([$mediaTwo->id, $mediaThree->id]);
        $this->assertEquals(2, $imageCollection->media()->count());
        $imageCollection->syncMedia([$mediaTwo->name, $mediaThree->name]);
        $this->assertEquals(2, $imageCollection->media()->count());

        // sync media by collection of media model
        $allMedia = Media::all();
        $imageCollection->syncMedia($allMedia);
        $this->assertEquals($allMedia->count(), $imageCollection->media()->count());
        $imageCollection->syncMedia(collect([$mediaTwo, $mediaThree]));
        $this->assertEquals(2, $imageCollection->media()->count());

        // detach all media by null value
        $imageCollection->syncMedia(null);
        $this->assertEquals(0, $imageCollection->media()->count());

        $videoCollection = $this->mediaCollection->with('media')->findByName('Videos');
        $this->assertEquals(1, $videoCollection->media()->count());
        // detach all media by empty-string
        $videoCollection->syncMedia('');
        $this->assertEquals(0, $imageCollection->media()->count());

        // sync media with id
        $videoCollection->syncMedia($mediaOne->id);
        $this->assertEquals(1, $videoCollection->media()->count());
        // detach all media by empty-array
        $videoCollection->syncMedia([]);
        $this->assertEquals(0, $videoCollection->media()->count());
    }
}
