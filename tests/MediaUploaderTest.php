<?php

namespace FarhanShares\MediaMan\Tests;


use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\MediaUploader;
use Illuminate\Database\QueryException;
use FarhanShares\MediaMan\Tests\Models\CustomMedia;
use FarhanShares\MediaMan\Tests\Models\CustomMediaCollection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class MediaUploaderTest extends TestCase
{
    #[Test]
    public function test_it_can_upload_a_file_to_the_default_disk()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals(self::DEFAULT_DISK, $media->disk);

        $filesystem = Storage::disk(self::DEFAULT_DISK);

        $this->assertTrue($filesystem->exists($media->getPath()));
    }

    #[Test]
    public function test_it_can_upload_a_file_to_a_specific_disk()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $customDisk = 'custom';

        // Create a test filesystem for the custom disk...
        Storage::fake($customDisk);

        $media = MediaUploader::source($file)
            ->setDisk($customDisk)
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals($customDisk, $media->disk);

        $filesystem = Storage::disk($customDisk);

        $this->assertTrue($filesystem->exists($media->getPath()));
    }

    #[Test]
    public function test_it_can_change_the_name_of_the_media_model()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)
            ->useName($newName = 'New name')
            ->upload();

        $this->assertEquals($newName, $media->name);
    }

    #[Test]
    public function test_it_can_rename_the_file_before_it_gets_uploaded()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)
            ->useFileName($newFileName = 'new-file-name.jpg')
            ->upload();

        $this->assertEquals($newFileName, $media->file_name);
    }

    #[Test]
    public function test_it_will_sanitize_the_file_name()
    {
        $file = UploadedFile::fake()->image('bad file name#023.jpg');

        $media = MediaUploader::source($file)->upload();

        $this->assertEquals('bad-file-name-023.jpg', $media->file_name);
    }

    #[Test]
    public function test_it_can_save_data_to_the_media_model()
    {
        $file = UploadedFile::fake()->image('image.jpg');

        $media = MediaUploader::source($file)
            ->withData([
                'test-01' => 'test data 01',
                'test-02' => 'test data 02'
            ])
            ->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('test data 01', $media->data['test-01']);
        $this->assertEquals('test data 02', $media->data['test-02']);
    }

    #[Test]
    public function test_it_respects_configured_media_and_collection_models()
    {
        config()->set('mediaman.models.media', CustomMedia::class);
        config()->set('mediaman.models.collection', CustomMediaCollection::class);

        $media = MediaUploader::source(UploadedFile::fake()->image('image.jpg'))->upload();

        $this->assertInstanceOf(CustomMedia::class, $media);
        $this->assertInstanceOf(CustomMediaCollection::class, $media->collections()->first());
    }

    #[Test]
    public function test_it_rolls_back_media_and_cleans_storage_when_write_throws()
    {
        $filesystem = Mockery::mock();
        $filesystem->shouldReceive('putFileAs')
            ->once()
            ->andThrow(new RuntimeException('Unable to write file'));
        $filesystem->shouldReceive('deleteDirectory')->once()->andReturn(true);
        $filesystem->shouldNotReceive('delete');

        Storage::shouldReceive('disk')->andReturn($filesystem);

        try {
            MediaUploader::source(UploadedFile::fake()->image('image.jpg'))->upload();
            $this->fail('Expected upload to throw.');
        } catch (RuntimeException $e) {
            $this->assertSame('Unable to write file', $e->getMessage());
        }

        $mediaModel = config('mediaman.models.media');
        $this->assertSame(0, $mediaModel::query()->count());
    }

    #[Test]
    public function test_it_treats_false_return_from_write_as_failure_and_rolls_back()
    {
        $filesystem = Mockery::mock();
        $filesystem->shouldReceive('putFileAs')->once()->andReturn(false);
        $filesystem->shouldReceive('deleteDirectory')->once()->andReturn(true);
        $filesystem->shouldNotReceive('delete');

        Storage::shouldReceive('disk')->andReturn($filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write media file to storage.');

        try {
            MediaUploader::source(UploadedFile::fake()->image('image.jpg'))->upload();
        } finally {
            $mediaModel = config('mediaman.models.media');
            $this->assertSame(0, $mediaModel::query()->count());
        }
    }

    #[Test]
    public function test_it_rolls_back_and_cleans_written_file_when_collection_attach_fails()
    {
        config()->set('mediaman.tables.collection_media', 'missing_collection_media');

        $this->expectException(QueryException::class);

        try {
            MediaUploader::source(UploadedFile::fake()->image('image.jpg'))->upload();
        } finally {
            $mediaModel = config('mediaman.models.media');
            $this->assertSame(0, $mediaModel::query()->count());
            $this->assertSame([], Storage::disk(self::DEFAULT_DISK)->allFiles());
        }
    }

    #[Test]
    public function test_successful_upload_persists_media_file_and_collection_attachment()
    {
        $media = MediaUploader::source(UploadedFile::fake()->image('image.jpg'))->upload();

        $mediaModel = config('mediaman.models.media');
        $this->assertSame(1, $mediaModel::query()->count());
        $this->assertTrue(Storage::disk(self::DEFAULT_DISK)->exists($media->getPath()));

        $freshMedia = $media->fresh('collections');
        $this->assertNotNull($freshMedia);
        $this->assertCount(1, $freshMedia->collections);
        $this->assertSame(config('mediaman.collection'), $freshMedia->collections->first()->name);
    }
}
