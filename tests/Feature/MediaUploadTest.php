<?php

namespace FarhanShares\MediaMan\Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\MediaUploaderPro;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Jobs\ProcessConversions;

class MediaUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_uploads_file_with_uuid()
    {
        config(['mediaman.use_uuid' => true]);

        $file = UploadedFile::fake()->image('test.jpg');

        $media = MediaUploaderPro::source($file)->upload();

        $this->assertNotNull($media->uuid);
        $this->assertDatabaseHas(config('mediaman.tables.media'), [
            'uuid' => $media->uuid,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    /** @test */
    public function it_processes_conversions_in_queue()
    {
        Queue::fake();
        config(['mediaman.queue_conversions' => true]);

        $file = UploadedFile::fake()->image('test.jpg');

        $media = MediaUploaderPro::source($file)
            ->withConversions(['thumbnail', 'webp'])
            ->setConversionManager(app(\FarhanShares\MediaMan\Conversions\ConversionManager::class))
            ->upload();

        Queue::assertPushed(ProcessConversions::class, function ($job) use ($media) {
            return $job->media->id === $media->id
                && in_array('thumbnail', $job->conversions)
                && in_array('webp', $job->conversions);
        });
    }

    /** @test */
    public function it_validates_file_size()
    {
        config(['mediaman.max_file_size' => 1024]); // 1KB

        $file = UploadedFile::fake()->image('test.jpg')->size(2048); // 2KB

        $this->expectException(\FarhanShares\MediaMan\Exceptions\FileSizeException::class);

        MediaUploaderPro::source($file)->upload();
    }

    /** @test */
    public function it_sanitizes_filename()
    {
        $file = UploadedFile::fake()->createWithContent(
            '../../../etc/passwd.jpg',
            'fake content'
        );

        $media = MediaUploaderPro::source($file)->upload();

        $this->assertStringNotContainsString('..', $media->file_name);
        $this->assertStringNotContainsString('/', $media->file_name);
    }

    /** @test */
    public function it_can_upload_to_custom_collection()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $media = MediaUploaderPro::source($file)
            ->toCollection('avatars')
            ->upload();

        $this->assertTrue(
            $media->collections()->where('name', 'avatars')->exists()
        );
    }

    /** @test */
    public function it_can_set_custom_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $media = MediaUploaderPro::source($file)
            ->withData(['custom_field' => 'custom_value'])
            ->upload();

        $this->assertEquals('custom_value', $media->data['custom_field']);
    }

    /** @test */
    public function it_finds_media_by_uuid()
    {
        config(['mediaman.use_uuid' => true]);

        $file = UploadedFile::fake()->image('test.jpg');
        $media = MediaUploaderPro::source($file)->upload();

        $found = Media::findByUuid($media->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($media->id, $found->id);
    }
}
