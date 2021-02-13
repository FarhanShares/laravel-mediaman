<?php

namespace FarhanShares\MediaMan\Traits;

use FarhanShares\MediaMan\MediaChannel;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use FarhanShares\MediaMan\Jobs\PerformConversions;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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
    public function getMedia(string $channel = 'default')
    {
        return $this->media->where('pivot.channel', $channel);
    }

    /**
     * Get the first media item in the specified channel.
     *
     * @param string $channel
     * @return mixed
     */
    public function getFirstMedia(string $channel = 'default')
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
    public function getFirstMediaUrl(string $channel = 'default', string $conversion = '')
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
     * @return void
     */
    public function attachMedia($media, string $channel = 'default', array $conversions = [])
    {
        $this->registerMediaChannels();

        $ids = $this->parseMediaIds($media);

        $mediaGroup = $this->getMediaChannel($channel);

        if ($mediaGroup && $mediaGroup->hasConversions()) {
            $conversions = array_merge(
                $conversions,
                $mediaGroup->getConversions()
            );
        }

        if (!empty($conversions)) {
            $model = config('mediaman.models.media');

            $media = $model::findMany($ids);

            $media->each(function ($media) use ($conversions) {
                PerformConversions::dispatch(
                    $media,
                    $conversions
                );
            });
        }

        $this->media()->attach($ids, [
            'channel' => $channel,
        ]);
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
        $group = new MediaChannel();

        $this->mediaChannels[$name] = $group;

        return $group;
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
     * @return void
     */
    public function detachMedia($media = null)
    {
        $this->media()->detach($media);
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
}
