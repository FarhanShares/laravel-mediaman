<?php

namespace FarhanShares\MediaMan\Tests;


use Mockery;
use Illuminate\Filesystem\Filesystem;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\MediaUploader;
use FarhanShares\MediaMan\Tests\TestCase;

class MediaTest extends TestCase
{
    /** @test */
    public function it_has_an_extension_accessor()
    {
        $image = new Media();
        $image->file_name = 'image.png';

        $video = new Media();
        $video->file_name = 'video.mov';

        $this->assertEquals('png', $image->extension);
        $this->assertEquals('mov', $video->extension);
    }

    /** @test */
    public function it_has_a_type_accessor()
    {
        $image = new Media();
        $image->mime_type = 'image/png';

        $video = new Media();
        $video->mime_type = 'video/mov';

        $this->assertEquals('image', $image->type);
        $this->assertEquals('video', $video->type);
    }

    /** @test */
    public function it_can_determine_its_type()
    {
        $media = new Media();
        $media->mime_type = 'image/png';

        $this->assertTrue($media->isOfType('image'));
        $this->assertFalse($media->isOfType('video'));
    }

    /** @test */
    public function it_can_get_the_path_on_disk_to_the_file()
    {
        $media = new Media();
        $media->id = 1;
        $media->file_name = 'image.jpg';

        $this->assertEquals('1/image.jpg', $media->getPath());
    }

    /** @test */
    public function it_can_get_the_path_on_disk_to_a_converted_image()
    {
        $media = new Media();
        $media->id = 1;
        $media->file_name = 'image.jpg';

        $this->assertEquals(
            '1/conversions/thumbnail/image.jpg',
            $media->getPath('thumbnail')
        );
    }

    /** @test */
    public function it_can_get_the_full_path_to_the_file()
    {
        $media = Mockery::mock(Media::class)->makePartial();

        $filesystem = Mockery::mock(Filesystem::class)->makePartial();

        // Assert filesystem calls path with the correct path on disk...
        $filesystem->shouldReceive('path')->with($media->getPath())->once()->andReturn('path');

        $media->shouldReceive('filesystem')->once()->andReturn($filesystem);

        $this->assertEquals('path', $media->getFullPath());
    }

    /** @test */
    public function it_can_get_the_full_path_to_a_converted_image()
    {
        $media = Mockery::mock(Media::class)->makePartial();

        $filesystem = Mockery::mock(Filesystem::class)->makePartial();

        // Assert filesystem calls path with the correct path on disk...
        $filesystem->shouldReceive('path')->with($media->getPath('thumbnail'))->once()->andReturn('path');

        $media->shouldReceive('filesystem')->once()->andReturn($filesystem);

        $this->assertEquals('path', $media->getFullPath('thumbnail'));
    }

    /** @test */
    public function it_can_get_the_url_to_the_file()
    {
        $media = Mockery::mock(Media::class)->makePartial();

        $filesystem = Mockery::mock(Filesystem::class)->makePartial();

        // Assert filesystem calls url with the correct path on disk...
        $filesystem->shouldReceive('url')->with($media->getPath())->once()->andReturn('url');

        $media->shouldReceive('filesystem')->once()->andReturn($filesystem);

        $this->assertEquals('url', $media->getUrl());
    }

    /** @test */
    public function it_can_get_the_url_to_a_converted_image()
    {
        $media = Mockery::mock(Media::class)->makePartial();

        $filesystem = Mockery::mock(Filesystem::class)->makePartial();

        // Assert filesystem calls url with the correct path on disk...
        $filesystem->shouldReceive('url')->with($media->getPath('thumbnail'))->once()->andReturn('url');

        $media->shouldReceive('filesystem')->once()->andReturn($filesystem);

        $this->assertEquals('url', $media->getUrl('thumbnail'));
    }

    /** @test */
    public function it_can_sync_a_collection_by_id()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'Test Collection'
        ]);

        $media = $this->media;
        $media->id = 1;
        $media->syncCollection($collection->id);

        $this->assertEquals(1, $media->collections()->count());
        $this->assertEquals($collection->name, $media->collections[0]->name);
    }


    /** @test */
    public function it_can_sync_a_collection_by_name()
    {
        $collection = $this->mediaCollection::firstOrCreate([
            'name' => 'Test Collection'
        ]);

        $media = $this->media;
        $media->id = 1;
        $media->syncCollection($collection->name);

        $this->assertEquals(1, $media->collections()->count());
        $this->assertEquals($collection->name, $media->collections[0]->name);
    }

    /** @test */
    public function it_can_sync_multiple_collections_by_name()
    {
        $this->mediaCollection::firstOrCreate([
            'name' => 'Test Collection'
        ]);

        $media = $this->media;
        $media->id = 1;
        $media->syncCollections(['Default', 'Test Collection']);

        $this->assertEquals(2, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('Test Collection', $media->collections[1]->name);
    }

    /** @test */
    public function it_can_sync_multiple_collections_by_id()
    {
        $this->mediaCollection::firstOrCreate([
            'name' => 'Test Collection'
        ]);

        $media = $this->media;
        $media->id = 1;
        $media->syncCollections([1, 2]);

        $this->assertEquals(2, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('Test Collection', $media->collections[1]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_a_collection_using_collection_id()
    {
        $collection = $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $collectionTwo = $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollection($collection->id);
        $media->attachCollection($collectionTwo->id);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_a_collection_using_collection_name()
    {
        $collection = $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $collectionTwo = $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollection($collection->name);
        $media->attachCollection($collectionTwo->name);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_a_collection_using_collection_object()
    {
        $collection = $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $collectionTwo = $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollection($collection);
        $media->attachCollection($collectionTwo);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
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
