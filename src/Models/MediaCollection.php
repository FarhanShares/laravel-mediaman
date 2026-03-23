<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaCollection extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'created_at', 'updated_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (config('mediaman.use_uuids')) {
            $this->incrementing = false;
            $this->keyType = 'string';
        }
    }

    protected static function booted()
    {
        static::creating(function ($collection) {
            if (config('mediaman.use_uuids') && empty($collection->getKey())) {
                $collection->setAttribute($collection->getKeyName(), (string) Str::uuid());
            }
        });
    }


    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('mediaman.tables.collections');
    }


    /**
     * Find a collection by name
     *
     * @param $query
     * @param string $names
     * @param array $columns
     * @return Collection|MediaCollection|null
     */
    public  function scopeFindByName($query, $names, array $columns = ['*'])
    {
        if (is_array($names)) {
            return $query->select($columns)->whereIn('name', $names)->get();
        }

        return $query->select($columns)->where('name', $names)->first();
    }


    /**
     * A collection belongs to many media.
     *
     * @return BelongsToMany
     */
    public function media()
    {
        return $this->belongsToMany($this->mediaModel(), config('mediaman.tables.collection_media'), 'media_id', 'collection_id');
    }

    /**
     * Resolve the configured media model class.
     *
     * @return string
     */
    protected function mediaModel(): string
    {
        return config('mediaman.models.media');
    }


    /**
     * Sync media of a collection
     *
     * @param null|int|string|array|Media|Collection $media
     * @param boolean $detaching
     * @return array|null
     */
    public function syncMedia($media, $detaching = true): ?array
    {
        if ($this->shouldDetachAll($media)) {
            return $this->media()->sync([]);
        }

        if (!$fetch = $this->fetchMedia($media)) {
            return null;
        }

        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            return $this->media()->sync($ids, $detaching);
        }

        if (method_exists($fetch, 'getKey')) {
            return $this->media()->sync($fetch->getKey(), $detaching);
        }

        return null;
    }


    /**
     * Attach media to a collection
     *
     * @param null|int|string|array|Media|Collection $media
     * @return int|null number of attached media or null
     */
    public function attachMedia($media): ?int
    {
        if (!$fetch = $this->fetchMedia($media)) {
            return null;
        }

        // to be consistent with the return type of detach method
        // which returns number of detached model, we're using sync without detachment
        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            $res = $this->media()->sync($ids, false);

            $attached  = count($res['attached']);
            return $attached > 0 ? $attached : null;
        }

        if (method_exists($fetch, 'getKey')) {
            $res = $this->media()->sync($fetch->getKey(), false);

            $attached  = count($res['attached']);
            return $attached > 0 ? $attached : null;
        }

        return null;
    }


    /**
     * Detach media from a collection
     *
     * @param null|int|string|array|Media|Collection $media
     * @return int|null number of detached media or null
     */
    public function detachMedia($media): ?int
    {
        if ($this->shouldDetachAll($media)) {
            return $this->media()->detach();
        }

        if (!$fetch = $this->fetchMedia($media)) {
            return null;
        }

        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            return $this->media()->detach($ids);
        }

        if (method_exists($fetch, 'getKey')) {
            return $this->media()->detach($fetch->getKey());
        }

        return null;
    }


    /**
     * Check if all media should be detached
     *
     * bool|null|empty-string|empty-array to detach all media
     *
     * @param mixed $collections
     * @return boolean
     */
    private function shouldDetachAll($media): bool
    {
        if (is_bool($media) || is_null($media) || empty($media)) {
            return true;
        }

        if (is_countable($media) && count($media) === 0) {
            return true;
        }

        return false;
    }


    /**
     * Fetch media
     *
     * returns single collection for single item
     * and multiple collections for multiple items
     * todo: exception / strict return types
     *
     * @param int|string|array|MediaCollection|Collection $collections
     * @return Collection|Media|Object|null
     */
    private function fetchMedia($media)
    {
        // eloquent collection doesn't need to be fetched again
        // it's treated as a valid source of Media resource
        if ($media instanceof EloquentCollection) {
            return $media;
        }

        // todo: check for instance of media model / collection instead?
        if ($media instanceof BaseCollection) {
            $ids = $this->extractKeys($media);
            return $this->mediaModel()::find($ids);
        }

        if (is_object($media) && method_exists($media, 'getKey')) {
            return $this->mediaModel()::find($media->getKey());
        }

        if (is_numeric($media)) {
            return $this->mediaModel()::find($media);
        }

        if (is_string($media)) {
            return $this->findMediaByName($media);
        }

        // all array items should be of same type
        // find by id or name based on the type of first item in the array
        if (is_array($media) && isset($media[0])) {
            if ($media[0] instanceof BaseCollection || $media[0] instanceof EloquentCollection) {
                return $media;
            }

            if (is_numeric($media[0])) {
                return $this->mediaModel()::find($media);
            }

            if (is_string($media[0])) {
                return $this->findMediaByName($media);
            }
        }

        return null;
    }

    /**
     * Find one or many media records by media name.
     *
     * @param string|array $names
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    private function findMediaByName($names)
    {
        $model = $this->mediaModel();
        $query = $model::query();

        if (is_array($names)) {
            return $query->whereIn('name', $names)->get();
        }

        return $query->where('name', $names)->first();
    }

    /**
     * Extract model keys from a collection of media models or keyed objects.
     *
     * @param mixed $items
     * @return array
     */
    private function extractKeys($items): array
    {
        if ($items instanceof EloquentCollection) {
            return $items->modelKeys();
        }

        return collect($items)
            ->map(function ($item) {
                return method_exists($item, 'getKey') ? $item->getKey() : ($item->id ?? $item);
            })
            ->all();
    }
}
