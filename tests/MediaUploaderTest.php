<?php

namespace FarhanShares\MediaMan\Tests;


use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\MediaUploader;

class MediaUploaderTest extends TestCase
{
    /** @test */
    public function it_can_upload_a_file_to_the_default_disk()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)->upload();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals(self::DEFAULT_DISK, $media->disk);

        $filesystem = Storage::disk(self::DEFAULT_DISK);

        $this->assertTrue($filesystem->exists($media->getPath()));
    }

    /** @test */
    public function it_can_upload_a_file_to_a_specific_disk()
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

    /** @test */
    public function it_can_change_the_name_of_the_media_model()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)
            ->useName($newName = 'New name')
            ->upload();

        $this->assertEquals($newName, $media->name);
    }

    /** @test */
    public function it_can_rename_the_file_before_it_gets_uploaded()
    {
        $file = UploadedFile::fake()->image('file-name.jpg');

        $media = MediaUploader::source($file)
            ->useFileName($newFileName = 'new-file-name.jpg')
            ->upload();

        $this->assertEquals($newFileName, $media->file_name);
    }

    /** @test */
    public function it_will_sanitize_the_file_name()
    {
        $file = UploadedFile::fake()->image('bad file name#023.jpg');

        $media = MediaUploader::source($file)->upload();

        $this->assertEquals('bad-file-name-023.jpg', $media->file_name);
    }

    /** @test */
    public function it_can_save_data_to_the_media_model()
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
}
