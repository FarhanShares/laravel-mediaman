<?php

namespace FarhanShares\MediaMan\Tests;


use Mockery;
use Illuminate\Filesystem\Filesystem;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\MediaUploader;
use FarhanShares\MediaMan\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection as ElCollection;

class MediaTest extends TestCase
{
    public function getMediaPath($mediaId): string
    {
        return $mediaId . '-' . md5($mediaId . config('app.key'));
    }


    /** @test */
    public function it_can_create_a_media_record_with_media_uploader()
    {
        // use api
        $mediaOne = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useFileName('image.jpg')
            ->useCollection('one')
            ->useCollection('two')
            ->useDisk('default')
            ->useData([
                'extraData'       => 'extra data value',
                'additional_data' => 'additional data value',
                'something-else'  => 'anything else?'
            ])
            ->upload();

        $fetch = Media::find($mediaOne->id);

        $this->assertEquals($fetch->id, $mediaOne->id);
    }

    /** @test */
    public function it_can_update_a_media_record()
    {
        $mediaOne = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->upload();

        $this->assertEquals('image', $mediaOne->name);

        $mediaOne->name = 'new-name';
        $mediaOne->data = ['newData' => 'new value'];
        $mediaOne->save();

        $this->assertEquals('new-name', $mediaOne->name);
        $this->assertEquals(['newData' => 'new value'], $mediaOne->data);

        // todo: make a fluent api like the following?
        // $mediaOne->rename('new-file')
        //     ->renameFile('new-file.ext')
        //     ->moveTo('disk')
        //     ->syncData(['new-data' => 'new new'])
        //     ->store();
    }

    /** @test */
    public function it_can_delete_a_media_record()
    {
        $media = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useDisk('default')
            ->upload();

        $mediaId = $media->id;
        $mediaFile = $media->file_name;
        $media->delete();

        $this->assertEquals(null, Media::find($mediaId));
        $this->assertEquals(false, Storage::disk('default')->exists($mediaFile));
    }

    /** @test */
    public function it_deletes_media_and_related_files_from_storage_when_media_is_deleted()
    {
        $media = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useDisk('default')
            ->upload();
        $mediaFilePath = $media->getPath();

        $media->delete();

        $this->assertNull(Media::find($media->id));
        Storage::disk($media->disk)->assertMissing($mediaFilePath);
    }

    /** @test */
    public function it_moves_file_to_new_disk_on_disk_update()
    {
        $newDiskName = 'newValidDisk';
        Storage::fake($newDiskName);

        config()->set("filesystems.disks.$newDiskName", [
            'driver' => 'local',
            'root' => storage_path("app/$newDiskName"),
        ]);

        $media = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useDisk('default')
            ->upload();

        $originalDisk = $media->disk;
        $originalPath = $media->getPath();

        $media->update(['disk' => $newDiskName]);

        Storage::disk($originalDisk)->assertMissing($originalPath);
        Storage::disk($newDiskName)->assertExists($media->getPath());
    }

    /** @test */
    public function it_renames_file_in_storage_on_filename_update()
    {
        $media = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useDisk('default')
            ->upload();

        $oldPath = $media->getPath();
        $media->update(['file_name' => 'new_name']);
        $newPath = $media->getPath();

        Storage::disk($media->disk)->assertMissing($oldPath);
        Storage::disk($media->disk)->assertExists($newPath);
    }

    /** @test */
    public function it_validates_disk_usability_for_valid_disk()
    {
        Storage::fake('newValidDisk');

        config()->set('filesystems.disks.newValidDisk', [
            'driver' => 'local',
            'root' => storage_path('app/newValidDisk'),
        ]);

        $this->assertNull(Media::ensureDiskUsability('newValidDisk'));
    }

    /** @test */
    public function it_throws_exception_for_invalid_disk_in_disk_usability_check()
    {
        $this->expectException(\InvalidArgumentException::class);

        Media::ensureDiskUsability('invalidDisk');
    }

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

        $path = $this->getMediaPath($media->id);
        $this->assertEquals($path . '/image.jpg', $media->getPath());
    }

    /** @test */
    public function it_can_get_the_path_on_disk_to_a_converted_image()
    {
        $media = new Media();
        $media->id = 1;
        $media->file_name = 'image.jpg';

        $path = $this->getMediaPath($media->id);
        $this->assertEquals(
            $path . '/conversions/thumbnail/image.jpg',
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
        $media->syncCollections($collection->id);

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
        $media->syncCollections($collection->name);

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

        $media->attachCollections($collection->id);
        $media->attachCollections($collectionTwo->id);

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

        $media->attachCollections($collection->name);
        $media->attachCollections($collectionTwo->name);

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

        $media->attachCollections($collection);
        $media->attachCollections($collectionTwo);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_multiple_collections_using_collection_ids()
    {
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);
        $collections = $this->mediaCollection->all();

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollections([$collections[1]->id, $collections[2]->id]);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_multiple_collections_using_collection_names()
    {
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);
        $collections = $this->mediaCollection->all();

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollections([$collections[1]->name, $collections[2]->name]);

        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_attach_a_media_to_multiple_collections_using_collection_object()
    {
        $media = MediaUploader::source($this->fileOne)->upload();

        // detach all collections
        $media->syncCollections([]);
        $this->assertEquals(0, $media->collections()->count());

        // create collections
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);

        // retrieve all collections
        $collections = $this->mediaCollection->all();
        $this->assertEquals(3, $collections->count());

        // attach all collections
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        $this->assertEquals('Default', $media->collections[0]->name);
        $this->assertEquals('my-collection', $media->collections[1]->name);
        $this->assertEquals('another-collection', $media->collections[2]->name);
    }

    /** @test */
    public function it_can_detach_a_media_to_a_collection_using_collection_id()
    {
        $collection = $this->mediaCollection::first(); // default collection

        $media = MediaUploader::source($this->fileOne)->upload(); // added to the default collection
        $this->assertEquals(1, $media->collections()->count());

        $media->detachCollections($collection->id);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_detach_a_collection_from_a_media_using_collection_name()
    {
        $collection = $this->mediaCollection::first(); // default collection

        $media = MediaUploader::source($this->fileOne)->upload(); // added to the default collection
        $this->assertEquals(1, $media->collections()->count());

        $media->detachCollections($collection->name);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_detach_a_collection_from_a_media_using_collection_object()
    {
        $collection = $this->mediaCollection::first(); // default collection

        $media = MediaUploader::source($this->fileOne)->upload(); // added to the default collection
        $this->assertEquals(1, $media->collections()->count());

        $media->detachCollections($collection);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_detach_multiple_collections_from_a_media_using_collection_ids()
    {
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);
        $collections = $this->mediaCollection->all();

        $media = MediaUploader::source($this->fileOne)->upload();

        $media->attachCollections([$collections[1]->id, $collections[2]->id]);
        $this->assertEquals(3, $media->collections()->count());

        $media->detachCollections([$collections[0]->id, $collections[1]->id, $collections[2]->id]);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_detach_multiple_collections_from_a_media_using_collection_names()
    {
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);

        $collections = $this->mediaCollection->all();
        $this->assertEquals(3, $collections->count());

        // default collection
        $media = MediaUploader::source($this->fileOne)->upload();
        $this->assertEquals(1, $media->collections()->count());

        // add to more collections
        $media->attachCollections([$collections[1]->name, $collections[2]->name]);
        $this->assertEquals(3, $media->collections()->count());

        // detach from all collections
        $media->detachCollections([$collections[0]->id, $collections[1]->id, $collections[2]->id]);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_remove_collections_if_its_bool_null_empty_string_or_empty_array_with_sync_collection()
    {
        $media = MediaUploader::source($this->fileOne)->upload();
        $collection = $this->mediaCollection->first();

        // sync with bool true resets back to zero collection
        $media->syncCollections(true);
        $this->assertEquals(0, $media->collections()->count());

        // attach a collections
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // sync with bool false resets back to zero collection
        $media->syncCollections(false);
        $this->assertEquals(0, $media->collections()->count());

        // attach a collection again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // sync with null resets back to zero collection
        $media->syncCollections(true);
        $this->assertEquals(0, $media->collections()->count());


        // attach a collection again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // sync with empty-string resets back to zero collection
        $media->syncCollections('');
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // sync with empty-array resets back to zero collection
        $media->syncCollections([]);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_detach_multiple_collections_from_a_media_using_collection_object()
    {
        $collections = $this->mediaCollection->all();
        $this->assertEquals(1, $collections->count());

        // default collection
        $media = MediaUploader::source($this->fileOne)->upload();
        $this->assertEquals(1, $media->collections()->count());

        // detach from all collections
        $media->detachCollections($collections);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_remove_collections_if_its_bool_null_empty_string_or_empty_array_with_sync()
    {
        $media = MediaUploader::source($this->fileOne)->upload();

        // create collections
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);
        // retrieve all collections
        $collections = $this->mediaCollection->all();
        $this->assertEquals(3, $collections->count());


        // default collection
        $this->assertEquals(1, $media->collections()->count());
        // sync with bool true resets back to zero collection
        $media->syncCollections(true);
        $this->assertEquals(0, $media->collections()->count());

        // attach all collections
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // sync with bool false resets back to zero collection
        $media->syncCollections(false);
        $this->assertEquals(0, $media->collections()->count());

        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // sync with null resets back to zero collection
        $media->syncCollections(true);
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // sync with empty-string resets back to zero collection
        $media->syncCollections('');
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // sync with empty-array resets back to zero collection
        $media->syncCollections([]);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_remove_collections_if_its_bool_null_empty_string_or_empty_array_with_detach_collection()
    {
        $media = MediaUploader::source($this->fileOne)->upload();
        $collection = $this->mediaCollection->first();

        // detach with bool true resets back to zero collection
        $media->detachCollections(true);
        $this->assertEquals(0, $media->collections()->count());

        // attach a collections
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // detach with bool false resets back to zero collection
        $media->detachCollections(false);
        $this->assertEquals(0, $media->collections()->count());

        // attach a collection again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // detach with null resets back to zero collection
        $media->detachCollections(true);
        $this->assertEquals(0, $media->collections()->count());


        // attach a collection again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // detach with empty-string resets back to zero collection
        $media->detachCollections('');
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collection);
        $this->assertEquals(1, $media->collections()->count());
        // detach with empty-array resets back to zero collection
        $media->detachCollections([]);
        $this->assertEquals(0, $media->collections()->count());
    }

    /** @test */
    public function it_can_remove_collections_if_its_bool_null_empty_string_or_empty_array_with_detach_collections()
    {
        $media = MediaUploader::source($this->fileOne)->upload();

        // create collections
        $this->mediaCollection::firstOrCreate(['name' => 'my-collection']);
        $this->mediaCollection::firstOrCreate(['name' => 'another-collection']);
        // retrieve all collections
        $collections = $this->mediaCollection->all();
        $this->assertEquals(3, $collections->count());


        // default collection
        $this->assertEquals(1, $media->collections()->count());
        // detach with bool true resets back to zero collection
        $media->detachCollections(true);
        $this->assertEquals(0, $media->collections()->count());

        // attach all collections
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // detach with bool false resets back to zero collection
        $media->detachCollections(false);
        $this->assertEquals(0, $media->collections()->count());

        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // detach with null resets back to zero collection
        $media->detachCollections(true);
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // detach with empty-string resets back to zero collection
        $media->detachCollections('');
        $this->assertEquals(0, $media->collections()->count());


        // attach all collections again
        $media->attachCollections($collections);
        $this->assertEquals(3, $media->collections()->count());
        // detach with empty-array resets back to zero collection
        $media->detachCollections([]);
        $this->assertEquals(0, $media->collections()->count());
    }
}
