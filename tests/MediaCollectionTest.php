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
    public function it_can_update_a_collection()
    {
    }

    /** @test */
    public function it_can_delete_a_collection()
    {
        // pivot should be deleted as well
    }

    /** @test */
    public function it_can_count_media_of_a_collection()
    {
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

    /** @test */
    public function it_can_attach_media_to_a_collection()
    {
        $mediaOne = MediaUploader::source($this->fileOne)
            ->useName('image-0')
            ->useCollection('Images')
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
        $this->assertEquals(3, $imageCollection->media()->count());
        $imageCollection->syncMedia([]);
        $this->assertEquals(0, $imageCollection->media()->count());

        $imageCollection->attachMedia($mediaOne);
        $imageCollection->attachMedia($mediaTwo->id);
        $imageCollection->attachMedia($mediaThree->name);
        $this->assertEquals(3, $imageCollection->media()->count());
        $imageCollection->syncMedia([]);
        $this->assertEquals(0, $imageCollection->media()->count());

        $allMedia = Media::all();
        $imageCollection->attachMedia($allMedia);
        $this->assertEquals($allMedia->count(), $imageCollection->media()->count());

        $imageCollection->syncMedia([]);
        $this->assertEquals(0, $imageCollection->media()->count());
        $imageCollection->attachMedia(collect([$mediaOne, $mediaTwo, $mediaThree]));
        $this->assertEquals(3, $imageCollection->media()->count());

        $imageCollection->syncMedia([]);
        $this->assertEquals(0, $imageCollection->media()->count());
        $imageCollection->attachMedia([$mediaOne->id]);
        $imageCollection->attachMedia([$mediaTwo->name]);
        $this->assertEquals(2, $imageCollection->media()->count());
    }

    /** @test */
    public function it_can_detach_media_from_a_collection()
    {
        $mediaOne = MediaUploader::source($this->fileOne)
            ->useName('image-0')
            ->useCollection('Images')
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
        $this->assertEquals(3, $imageCollection->media()->count());
        // detach all media by boolean true
        $imageCollection->detachMedia(true);
        $this->assertEquals(0, $imageCollection->media()->count());

        $imageCollection->attachMedia($mediaOne);
        $this->assertEquals(1, $imageCollection->media()->count());
        $imageCollection->detachMedia($mediaOne->id);
        $this->assertEquals(0, $imageCollection->media()->count());

        $imageCollection->attachMedia([$mediaOne->id, $mediaTwo->id]);
        $this->assertEquals(2, $imageCollection->media()->count());
        $imageCollection->detachMedia([$mediaOne->id, $mediaTwo->id]);
        $this->assertEquals(0, $imageCollection->media()->count());

        $imageCollection->attachMedia([$mediaOne->name, $mediaTwo->name]);
        $this->assertEquals(2, $imageCollection->media()->count());
        $imageCollection->detachMedia([$mediaOne->name, $mediaTwo->name]);
        $this->assertEquals(0, $imageCollection->media()->count());

        $imageCollection->attachMedia(collect([$mediaOne, $mediaTwo]));
        $this->assertEquals(2, $imageCollection->media()->count());
        $imageCollection->detachMedia(collect([$mediaOne, $mediaTwo]));
        $this->assertEquals(0, $imageCollection->media()->count());

        $allMedia = Media::all();
        $imageCollection->attachMedia($allMedia);
        $this->assertEquals($allMedia->count(), $imageCollection->media()->count());
        $imageCollection->detachMedia($allMedia);
        $this->assertEquals(0, $imageCollection->media()->count());
    }

    /** @test */
    public function it_returns_false_for_non_existing_or_already_attached_media_when_attaching()
    {
        MediaUploader::source($this->fileOne)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');

        $a1 = $imageCollection->attachMedia(5);
        $this->assertEquals(false, $a1);
        $a2 = $imageCollection->attachMedia([1, 7]);
        $this->assertEquals(false, $a2);
    }

    /** @test */
    public function it_returns_number_of_attached_media_if_at_least_one_of_these_is_existing_media_and_not_already_attached_when_attaching()
    {
        MediaUploader::source($this->fileOne)
            ->useName('others-1')
            ->useCollection('Others')
            ->upload();
        MediaUploader::source($this->fileOne)
            ->useName('others-2')
            ->useCollection('Others')
            ->upload();
        MediaUploader::source($this->fileOne)
            ->useName('others-3')
            ->useCollection('Others')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')
            ->firstOrCreate(['name' => 'Images']);


        $a1 = $imageCollection->attachMedia(1);
        $this->assertEquals(1, $a1);
        $a2 = $imageCollection->attachMedia([2, 3]);
        $this->assertEquals(2, $a2);

        $imageCollection->syncMedia([1]);
        $this->assertEquals(1, $imageCollection->media()->count());
        $a3 = $imageCollection->attachMedia([1, 2, 3]);
        $this->assertEquals(2, $a3);
    }

    /** @test */
    public function it_returns_false_if_all_are_non_existing_or_already_detached_media_when_detaching()
    {
        MediaUploader::source($this->fileOne)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');

        // if all are non existing media it will return false
        $b1 = $imageCollection->detachMedia(5);
        $this->assertEquals(false, $b1);
        $b2 = $imageCollection->detachMedia([10, 15]);
        $this->assertEquals(false, $b2);
    }

    /** @test */
    public function it_returns_number_of_detached_media_if_at_least_one_of_these_is_existing_attached_media_and_not_already_detached_when_detaching()
    {
        MediaUploader::source($this->fileOne)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();
        MediaUploader::source($this->fileOne)
            ->useName('image-2')
            ->useCollection('Images')
            ->upload();
        MediaUploader::source($this->fileOne)
            ->useName('image-3')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');

        $b1 = $imageCollection->detachMedia(1);
        $this->assertEquals(1, $b1);
        $b2 = $imageCollection->detachMedia([1, 2, 3, 10]);
        $this->assertEquals(2, $b2);
    }

    /** @test */
    public function it_returns_false_if_it_is_a_non_existing_media_when_synchronizing()
    {
        MediaUploader::source($this->fileOne)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');

        // if all are non existing media it will return false
        $b1 = $imageCollection->syncMedia(5);
        $this->assertEquals(false, $b1);
    }

    /** @test */
    public function it_returns_detailed_array_when_synchronizing_with_existing_non_existing_and_already_attached_media_array()
    {
        MediaUploader::source($this->fileOne)
            ->useName('image-1')
            ->useCollection('Images')
            ->upload();

        $imageCollection = $this->mediaCollection->with('media')->findByName('Images');


        // all are non existing
        $a1 = $imageCollection->syncMedia([10, 15]);
        $b1 = [
            "attached" => [],
            "detached" => [1],
            "updated" => []
        ];
        $this->assertEquals($a1, $b1);

        // existing / already attached media
        $a1 = $imageCollection->syncMedia([1, 2]);
        $b1 = [
            "attached" => [1],
            "detached" => [],
            "updated" => []
        ];
        $this->assertEquals($b1, $a1);
    }
}
