<?php

namespace FarhanShares\MediaMan\Cache;

use Illuminate\Support\Facades\Cache;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Database\Eloquent\Collection;

class MediaCacheManager
{
    protected string $prefix;
    protected int $ttl;
    protected bool $enabled;
    protected ?string $store;

    public function __construct()
    {
        $this->prefix = config('mediaman.cache.prefix', 'mediaman');
        $this->ttl = config('mediaman.cache.ttl', 3600);
        $this->enabled = config('mediaman.cache.enabled', true);
        $this->store = config('mediaman.cache.store');
    }

    /**
     * Get cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache()
    {
        return $this->store ? Cache::store($this->store) : Cache::store();
    }

    /**
     * Generate cache key.
     *
     * @param string $key
     * @param array $params
     * @return string
     */
    protected function cacheKey(string $key, array $params = []): string
    {
        $suffix = !empty($params) ? '_' . md5(json_encode($params)) : '';
        return "{$this->prefix}:{$key}{$suffix}";
    }

    /**
     * Cache media by ID.
     *
     * @param int|string $id
     * @param Media|null $media
     * @return Media|null
     */
    public function cacheMedia($id, ?Media $media = null): ?Media
    {
        if (!$this->enabled) {
            return $media;
        }

        $key = $this->cacheKey('media', ['id' => $id]);

        if ($media === null) {
            return $this->cache()->get($key);
        }

        $this->cache()->put($key, $media, $this->ttl);
        return $media;
    }

    /**
     * Get cached media or query database.
     *
     * @param int|string $id
     * @param bool $useUuid
     * @return Media|null
     */
    public function getMedia($id, bool $useUuid = false): ?Media
    {
        if (!$this->enabled) {
            return $useUuid ? Media::findByUuid($id) : Media::find($id);
        }

        $key = $this->cacheKey('media', ['id' => $id]);

        return $this->cache()->remember($key, $this->ttl, function () use ($id, $useUuid) {
            return $useUuid ? Media::findByUuid($id) : Media::find($id);
        });
    }

    /**
     * Cache media collection.
     *
     * @param string $key
     * @param Collection|null $collection
     * @param array $params
     * @return Collection|null
     */
    public function cacheCollection(string $key, ?Collection $collection = null, array $params = []): ?Collection
    {
        if (!$this->enabled) {
            return $collection;
        }

        $cacheKey = $this->cacheKey($key, $params);

        if ($collection === null) {
            return $this->cache()->get($cacheKey);
        }

        $this->cache()->put($cacheKey, $collection, $this->ttl);
        return $collection;
    }

    /**
     * Cache media URL.
     *
     * @param int $mediaId
     * @param string|null $conversion
     * @param string|null $url
     * @return string|null
     */
    public function cacheUrl(int $mediaId, ?string $conversion = null, ?string $url = null): ?string
    {
        if (!$this->enabled) {
            return $url;
        }

        $key = $this->cacheKey('url', ['media_id' => $mediaId, 'conversion' => $conversion]);

        if ($url === null) {
            return $this->cache()->get($key);
        }

        $this->cache()->put($key, $url, $this->ttl);
        return $url;
    }

    /**
     * Invalidate cache for specific media.
     *
     * @param int|Media $media
     * @return void
     */
    public function invalidateMedia($media): void
    {
        if (!$this->enabled) {
            return;
        }

        $id = $media instanceof Media ? $media->id : $media;

        // Clear media cache
        $this->cache()->forget($this->cacheKey('media', ['id' => $id]));

        // Clear URL caches
        $this->cache()->forget($this->cacheKey('url', ['media_id' => $id, 'conversion' => null]));

        // Clear tags if enabled
        if (config('mediaman.cache.tags_enabled', false)) {
            $this->cache()->tags(["media:{$id}"])->flush();
        }
    }

    /**
     * Invalidate all media cache.
     *
     * @return void
     */
    public function invalidateAll(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (config('mediaman.cache.tags_enabled', false)) {
            $this->cache()->tags([$this->prefix])->flush();
        } else {
            // Fallback: Clear all cache (use with caution)
            if (config('mediaman.cache.allow_flush_all', false)) {
                $this->cache()->flush();
            }
        }
    }

    /**
     * Warm cache for frequently accessed media.
     *
     * @param array $mediaIds
     * @return void
     */
    public function warmCache(array $mediaIds): void
    {
        if (!$this->enabled) {
            return;
        }

        $media = Media::whereIn('id', $mediaIds)->get();

        foreach ($media as $item) {
            $this->cacheMedia($item->id, $item);
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'store' => $this->store ?? 'default',
            'prefix' => $this->prefix,
            'ttl' => $this->ttl,
            'tags_enabled' => config('mediaman.cache.tags_enabled', false),
        ];
    }
}
