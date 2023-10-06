<?php

namespace FarhanShares\MediaMan\Traits;

use Throwable;
use FarhanShares\MediaMan\MediaChannel;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use FarhanShares\MediaMan\Jobs\PerformConversions;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as BaseCollection;

trait HasMedia
{
    /** @var MediaChannels[] */
    protected $mediaChannels = [];

    /**
     * Get the "media" relationship.
     *
     * @return MorphToMany
     */
    public function media(): MorphToMany
    {
        return $this
            ->morphToMany(config('mediaman.models.media'), 'mediable', config('mediaman.tables.mediables'))
            ->withPivot('channel');
    }

    /**
     * Determine if there is any media in the specified group.
     *
     * @param string $channel
     * @return mixed
     */
    public function hasMedia(string $channel = 'default')
    {
        return $this->getMedia($channel)->isNotEmpty();
    }

    /**
     * Get all the media in the specified group.
     *
     * @param string $group
     * @return mixed
     */
    public function getMedia(?string $channel = 'default')
    {
        if ($channel) {
            return $this->media->where('pivot.channel', $channel);
        }

        return $this->media;
    }

    /**
     * Get the first media item in the specified channel.
     *
     * @param string $channel
     * @return mixed
     */
    public function getFirstMedia(?string $channel = 'default')
    {
        return $this->getMedia($channel)->first();
    }

    /**
     * Get the url of the first media item in the specified channel.
     *
     * @param string $channel
     * @param string $conversion
     * @return string
     */
    public function getFirstMediaUrl(?string $channel = 'default', string $conversion = '')
    {
        if (!$media = $this->getFirstMedia($channel)) {
            return '';
        }

        return $media->getUrl($conversion);
    }

    /**
     * Attach media to the specified channel.
     *
     * @param mixed $media
     * @param string $channel
     * @param array $conversions
     * @return int|null
     */
    public function attachMedia($media, string $channel = 'default', array $conversions = [])
    {
        // Utilize syncMedia with detaching set to false to achieve the attach behavior
        $syncResult = $this->syncMedia($media, $channel, $conversions, false);

        if (!isset($syncResult['attached'])) return null;

        // Count the number of attached media from the sync result
        $attached  = count($syncResult['attached'] ?? []);

        // Return the count of attached media if there's any, otherwise return null
        return $attached > 0 ? $attached : null;
    }

    /**
     * Parse the media id's from the mixed input.
     *
     * @param mixed $media
     * @return array
     */
    protected function parseMediaIds($media)
    {
        if ($media instanceof Collection) {
            return $media->modelKeys();
        }

        if ($media instanceof Media) {
            return [$media->getKey()];
        }

        return (array) $media;
    }

    /**
     * Register all the model's media channels.
     *
     * @return void
     */
    public function registerMediaChannels()
    {
        //
    }

    /**
     * Register a new media group.
     *
     * @param string $name
     * @return MediaChannel
     */
    protected function addMediaChannel(string $name)
    {
        $channel = new MediaChannel();

        $this->mediaChannels[$name] = $channel;

        return $channel;
    }

    /**
     * Get the media channel with the specified name.
     *
     * @param string $name
     * @return MediaChannel|null
     */
    public function getMediaChannel(string $name)
    {
        return $this->mediaChannels[$name] ?? null;
    }

    /**
     * Detach the specified media.
     *
     * @param mixed $media
     * @return int|null
     */
    public function detachMedia($media = null)
    {
        $count =  $this->media()->detach($media);

        return $count > 0 ? $count : null;
    }

    /**
     * Detach all the media in the specified channel.
     *
     * @param string $channel
     * @return void
     */
    public function clearMediaChannel(string $channel = 'default')
    {
        $this->media()->wherePivot('channel', $channel)->detach();
    }


    /**
     * Sync media to the specified channel.
     *
     * This will remove the media that aren't in the provided list
     * and add those which aren't already attached if $detaching is truthy.
     *
     * @param mixed $media
     * @param string $channel
     * @param array $conversions
     * @param bool $detaching
     * @return array|null
     */
    public function syncMedia($media, string $channel = 'default', array $conversions = [], $detaching = true)
    {
        $this->registerMediaChannels();

        if ($detaching === true && $this->shouldDetachAll($media)) {
            return $this->media()->sync([]);
        }

        $ids = $this->parseMediaIds($media);

        $mediaChannel = $this->getMediaChannel($channel);

        if ($mediaChannel && $mediaChannel->hasConversions()) {
            $conversions = array_merge(
                $conversions,
                $mediaChannel->getConversions()
            );
        }

        if (!empty($conversions)) {
            $model = config('mediaman.models.media');

            $mediaInstances = $model::findMany($ids);

            $mediaInstances->each(function ($mediaInstance) use ($conversions) {
                PerformConversions::dispatch(
                    $mediaInstance,
                    $conversions
                );
            });
        }

        $mappedIds = [];
        foreach ($ids as $id) {
            $mappedIds[$id] = ['channel' => $channel];
        }

        try {
            $res = $this->media()->sync($mappedIds, $detaching);
            return $res; // this should give an array containing 'attached', 'detached', and 'updated'
        } catch (Throwable $th) {
            return null;
        }
    }

    /**
     * Check if all media should be detached
     *
     * bool|null|empty-string|empty-array to detach all media
     *
     * @param mixed $collections
     * @return boolean
     */
    protected function shouldDetachAll($media): bool
    {
        if (is_bool($media) || is_null($media) || empty($media)) {
            return true;
        }

        if (is_countable($media) && count($media) === 0) {
            return true;
        }

        return false;
    }
}
