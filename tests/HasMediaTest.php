<?php

namespace FarhanShares\MediaMan\Tests;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\MediaUploader;
use FarhanShares\MediaMan\Tests\Models\Subject;
use FarhanShares\MediaMan\Jobs\PerformConversions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class HasMediaTest extends TestCase
{

    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Subject::create();
    }

    /** @test */
    public function it_registers_the_media_relationship()
    {
        $this->assertInstanceOf(MorphToMany::class, $this->subject->media());
    }

    /** @test */
    public function it_can_attach_media_to_the_default_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media);

        $attachedMedia = $this->subject->media()->first();

        $this->assertEquals($attachedMedia->id, $media->id);
        $this->assertEquals('default', $attachedMedia->pivot->channel);
    }

    /** @test */
    public function it_can_attach_media_to_a_named_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, $channel = 'custom');

        $attachedMedia = $this->subject->media()->first();

        $this->assertEquals($media->id, $attachedMedia->id);
        $this->assertEquals($channel, $attachedMedia->pivot->channel);
    }

    /** @test */
    public function it_can_attach_a_collection_of_media()
    {
        $media = factory(Media::class, 2)->create();

        $this->subject->attachMedia($media);

        $attachedMedia = $this->subject->media()->get();

        $this->assertCount(2, $attachedMedia);
        $this->assertEmpty($media->diff($attachedMedia));

        $attachedMedia->each(
            function ($media) {
                $this->assertEquals('default', $media->pivot->channel);
            }
        );
    }

    /** @test */
    public function it_returns_number_of_attached_media_or_null_while_associating()
    {
        $media = factory(Media::class)->create();

        $attachedCount = $this->subject->attachMedia($media, 'custom');

        $this->assertEquals(1, $attachedCount);

        if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            // SQLite doesn't enforce foreign key constraints by default, so this test won't fail as expected in an SQLite environment.
            // However, it should work as expected on other relational databases that enforce these constraints.
            $this->markTestSkipped('Skipping test for SQLite connection.');
        } else {
            // try attaching a non-existing media record
            $attached = $this->subject->attachMedia(5, 'custom');
            $this->assertEquals(null, $attached);
        }
    }

    /** @test */
    public function it_returns_number_of_detached_media_or_null_while_disassociating()
    {
        $media = factory(Media::class)->create();
        $this->subject->attachMedia($media, 'custom');

        $detached = $this->subject->detachMedia($media);

        $this->assertEquals(1, $detached);

        // try detaching a non-existing media record
        $detached = $this->subject->detachMedia(100);
        $this->assertEquals(null, $detached);
    }

    public function it_can_attach_media_and_returns_number_of_media_attached()
    {
        $media = factory(Media::class)->create();

        $attachedCount = $this->subject->attachMedia($media);

        $this->assertEquals(1, $attachedCount);

        $attachedMedia = $this->subject->media()->first();
        $this->assertEquals($media->id, $attachedMedia->id);
    }

    /** @test */
    public function it_can_detach_media_and_returns_number_of_media_detached()
    {
        $media = factory(Media::class)->create();
        $this->subject->attachMedia($media);

        $detachedCount = $this->subject->detachMedia($media);

        $this->assertEquals(1, $detachedCount);
        $this->assertNull($this->subject->media()->first());
    }

    /** @test */
    public function it_can_sync_media_and_returns_sync_status()
    {
        $media1 = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useCollection('default')
            ->useDisk('default')
            ->upload();
        $media2 = MediaUploader::source($this->fileOne)
            ->useName('image')
            ->useCollection('default')
            ->useDisk('default')
            ->upload();

        // Initially attach media1
        $this->subject->attachMedia($media1);

        // Now, sync to media2
        $syncStatus = $this->subject->syncMedia($media2);

        $this->assertArrayHasKey('updated', $syncStatus);
        $this->assertArrayHasKey('attached', $syncStatus);
        $this->assertArrayHasKey('detached', $syncStatus);

        $this->assertEquals([$media2->id], $syncStatus['attached']);
        $this->assertEquals([$media1->id], $syncStatus['detached']);

        $syncStatus = $this->subject->syncMedia([]); // should detach all
        $this->assertEquals(1, count($syncStatus['detached']));
    }

    /** @test */
    public function it_can_sync_collections_for_a_media_instance()
    {
        $media = factory(Media::class)->create();
        $collections = ['collection1', 'collection2'];

        $syncStatus = $media->syncCollections($collections);

        $this->assertArrayHasKey('attached', $syncStatus);
        $this->assertArrayHasKey('detached', $syncStatus);
        $this->assertArrayHasKey('updated', $syncStatus);
    }

    /** @test */
    public function it_will_perform_the_given_conversions_when_media_is_attached()
    {
        Queue::fake();

        $media = factory(Media::class)->create();

        $conversions = ['conversion'];

        $this->subject->attachMedia($media, 'default', $conversions);

        Queue::assertPushed(
            PerformConversions::class,
            function ($job) use ($media, $conversions) {
                return $media->is($job->getMedia())
                    && empty(array_diff($conversions, $job->getConversions()));
            }
        );
    }

    /** @test */
    public function it_will_perform_the_conversions_registered_by_the_channel_when_media_is_attached()
    {
        Queue::fake();

        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, $channel = 'converted-images');

        Queue::assertPushed(
            PerformConversions::class,
            function ($job) use ($media, $channel) {
                $conversions = $this->subject
                    ->getMediaChannel($channel)
                    ->getConversions();

                return $media->is($job->getMedia())
                    && empty(array_diff($conversions, $job->getConversions()));
            }
        );
    }

    /** @test */
    public function it_can_retrieve_all_the_media_from_the_default_channel()
    {
        $media = factory(Media::class, 2)->create();

        $this->subject->attachMedia($media);

        $defaultMedia = $this->subject->getMedia();

        $this->assertEquals(2, $defaultMedia->count());
        $this->assertEmpty($media->diff($defaultMedia));
    }

    /** @test */
    public function it_can_retrieve_all_the_media_from_the_specified_channel()
    {
        $media = factory(Media::class, 2)->create();

        $this->subject->attachMedia($media, 'gallery');

        $galleryMedia = $this->subject->getMedia('gallery');

        $this->assertEquals(2, $galleryMedia->count());
        $this->assertEmpty($media->diff($galleryMedia));
    }

    /** @test */
    public function it_can_handle_attempts_to_get_media_from_an_empty_channel()
    {
        $media = $this->subject->getMedia();

        $this->assertInstanceOf(EloquentCollection::class, $media);
        $this->assertTrue($media->isEmpty());
    }

    /** @test */
    public function it_can_retrieve_the_first_media_from_the_default_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media);

        $firstMedia = $this->subject->getFirstMedia();

        $this->assertInstanceOf(Media::class, $firstMedia);
        $this->assertEquals($media->id, $firstMedia->id);
    }

    /** @test */
    public function it_can_retrieve_the_first_media_from_the_specified_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, 'gallery');

        $firstMedia = $this->subject->getFirstMedia('gallery');

        $this->assertInstanceOf(Media::class, $firstMedia);
        $this->assertEquals($media->id, $firstMedia->id);
    }

    /** @test */
    public function it_will_only_retrieve_media_from_the_specified_channel()
    {
        $defaultMedia = factory(Media::class)->create();
        $galleryMedia = factory(Media::class)->create();

        // Attach media to the default channel...
        $this->subject->attachMedia($defaultMedia->id);

        // Attach media to the gallery channel...
        $this->subject->attachMedia($galleryMedia->id, 'gallery');

        $allDefaultMedia = $this->subject->getMedia();
        $allGalleryMedia = $this->subject->getMedia('gallery');
        $firstGalleryMedia = $this->subject->getFirstMedia('gallery');

        $this->assertCount(1, $allDefaultMedia);
        $this->assertEquals($defaultMedia->id, $allDefaultMedia->first()->id);

        $this->assertCount(1, $allGalleryMedia);
        $this->assertEquals($galleryMedia->id, $allGalleryMedia->first()->id);
        $this->assertEquals($galleryMedia->id, $firstGalleryMedia->id);
    }

    /** @test */
    public function it_can_retrieve_the_url_of_the_first_media_item_from_the_default_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media);

        $url = $this->subject->getFirstMediaUrl();

        $this->assertEquals($media->getUrl(), $url);
    }

    /** @test */
    public function it_can_retrieve_the_url_of_the_first_media_item_from_the_specified_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, 'gallery');

        $url = $this->subject->getFirstMediaUrl('gallery');

        $this->assertEquals($media->getUrl(), $url);
    }

    /** @test */
    public function it_can_retrieve_the_converted_image_url_of_the_first_media_item_from_the_specified_group()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, 'gallery');

        $url = $this->subject->getFirstMediaUrl('gallery', 'conversion-name');

        $this->assertEquals($media->getUrl('conversion-name'), $url);
    }

    /** @test */
    public function it_can_determine_if_there_is_media_in_the_default_channel()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media);

        $this->assertTrue($this->subject->hasMedia());
        $this->assertFalse($this->subject->hasMedia('empty'));
    }

    /** @test */
    public function it_can_determine_if_there_is_any_media_in_the_specified_group()
    {
        $media = factory(Media::class)->create();

        $this->subject->attachMedia($media, 'gallery');

        $this->assertTrue($this->subject->hasMedia('gallery'));
        $this->assertFalse($this->subject->hasMedia());
    }

    /** @test */
    public function it_can_detach_all_the_media()
    {
        $mediaOne = factory(Media::class)->create();
        $mediaTwo = factory(Media::class)->create();

        $this->subject->attachMedia($mediaOne);
        $this->subject->attachMedia($mediaTwo, 'gallery');

        $this->subject->detachMedia();

        $this->assertFalse($this->subject->media()->exists());
    }

    /** @test */
    public function it_can_detach_specific_media_items()
    {
        $mediaOne = factory(Media::class)->create();
        $mediaTwo = factory(Media::class)->create();

        $this->subject->attachMedia([
            $mediaOne->id, $mediaTwo->id,
        ]);

        $this->subject->detachMedia($mediaOne);

        $this->assertCount(1, $this->subject->getMedia());
        $this->assertEquals($mediaTwo->id, $this->subject->getFirstMedia()->id);
    }

    /** @test */
    public function it_can_detach_all_the_media_in_a_specified_channel()
    {
        $mediaOne = factory(Media::class)->create();
        $mediaTwo = factory(Media::class)->create();

        $this->subject->attachMedia($mediaOne, 'one');
        $this->subject->attachMedia($mediaTwo, 'two');

        $this->subject->clearMediaChannel('one');

        $this->assertFalse($this->subject->hasMedia('one'));
        $this->assertCount(1, $this->subject->getMedia('two'));
        $this->assertEquals($mediaTwo->id, $this->subject->getFirstMedia('two')->id);
    }
}
