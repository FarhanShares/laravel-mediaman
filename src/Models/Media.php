<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Support\Str;
use FarhanShares\MediaMan\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Filesystem\Filesystem;
use FarhanShares\MediaMan\Models\MediaCollection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'file_name', 'mime_type', 'size', 'disk', 'data'
    ];

    /**
     * The attributes that need casting.
     *
     * @var array
     */
    protected $casts = [
        'data' => Json::class
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['friendly_size',  'media_url', 'type', 'extension'];


    public static function booted()
    {
        static::deleted(static function ($media) {
            // delete the media directory
            $deleted = Storage::disk($media->disk)->deleteDirectory($media->getDirectory());
            // if failed, try deleting the file then
            !$deleted && Storage::disk($media->disk)->delete($media->getPath());
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('mediaman.tables.media');
    }

    /**
     * Get the file extension.
     *
     * @return string
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type.
     *
     * @return string|null
     */
    public function getTypeAttribute()
    {
        return Str::before($this->mime_type, '/') ?? null;
    }

    /**
     * Determine if the file is of the specified type.
     *
     * @param string $type
     * @return bool
     */
    public function isOfType(string $type)
    {
        return $this->type === $type;
    }

    /**
     * Get the file size in human readable format.
     *
     * @return string|null
     */
    public function getFriendlySizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($this->size == 0) {
            return '0 ' . $units[1];
        }

        for ($i = 0; $this->size > 1024; $i++) {
            $this->size /= 1024;
        }

        return round($this->size, 2) . ' ' . $units[$i];
    }


    /**
     * Get the url to the file.
     *
     * @param string $conversion
     * @return mixed
     */
    public function getUrl(string $conversion = '')
    {
        return $this->filesystem()->url(
            $this->getPath($conversion)
        );
    }

    /**
     * Get the full path to the file.
     *
     * @param string $conversion
     * @return mixed
     */
    public function getFullPath(string $conversion = '')
    {
        return $this->filesystem()->path(
            $this->getPath($conversion)
        );
    }

    /**
     * Get the path to the file on disk.
     *
     * @param string $conversion
     * @return string
     */
    public function getPath(string $conversion = '')
    {
        $directory = $this->getDirectory();

        if ($conversion) {
            $directory .= '/conversions/' . $conversion;
        }

        return $directory . '/' . $this->file_name;
    }

    /**
     * Get the directory for files on disk.
     *
     * @return mixed
     */
    public function getDirectory()
    {
        return $this->getKey() . '-' . md5($this->getKey() . config('app.key'));
    }

    /**
     * Get the filesystem where the associated file is stored.
     *
     * @return Filesystem
     */
    public function filesystem()
    {
        return Storage::disk($this->disk);
    }


    /**
     * Find a media by media name
     *
     * @param $query
     * @param string $names
     * @param array $columns
     * @return Collection|Media|null
     */
    public  function scopeFindByName($query, $names, array $columns = ['*'])
    {
        if (is_array($names)) {
            return $query->select($columns)->whereIn('name', $names)->get();
        }

        return $query->select($columns)->where('name', $names)->first();
    }


    /**
     * A media belongs to many collection
     *
     * @return BelongsToMany
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(MediaCollection::class, config('mediaman.tables.collection_media'), 'collection_id', 'media_id');
    }


    /**
     * Sync collections of a media
     *
     * @param null|int|string|array|MediaCollection|Collection $collections
     * @param boolean $detaching
     * @return array of synced status
     */
    public function syncCollections($collections, $detaching = true)
    {
        if ($this->shouldDetachAll($collections)) {
            return $this->collections()->sync([]);
        }

        $fetch = $this->fetchCollections($collections);
        if (is_countable($fetch)) {
            $ids = $fetch->pluck('id')->all();
            return ($this->collections()->sync($ids, $detaching));
        } else {
            return ($this->collections()->sync($fetch->id, $detaching));
        }

        return false;
    }


    /**
     * Attach a media to collections
     *
     * @param null|int|string|array|MediaCollection|Collection $collections
     * @return int|null
     */
    public function attachCollections($collections)
    {
        $fetch = $this->fetchCollections($collections);
        if (is_countable($fetch)) {
            $ids = $fetch->pluck('id');
            $res = $this->collections()->sync($ids, false);
            $attached  = count($res['attached']);
            return $attached > 0 ? $attached : null;
        } else {
            $res = $this->collections()->sync($fetch->id, false);
            $attached  = count($res['attached']);
            return $attached > 0 ? $attached : null;
        }

        return null;
    }


    /**
     * Detach a media from collections
     *
     * @param null|int|string|array|MediaCollection|Collection $collections
     * @return int|null
     */
    public function detachCollections($collections)
    {
        if ($this->shouldDetachAll($collections)) {
            return $this->collections()->detach();
        }

        // todo: check if null is returned on failure
        $fetch = $this->fetchCollections($collections);
        if (is_countable($fetch)) {
            $ids = $fetch->pluck('id')->all();
            return $this->collections()->detach($ids);
        } else {
            return $this->collections()->detach($fetch->id);
        }

        return null;
    }


    /**
     * Check if all collections should be detached
     *
     * bool|null|empty-string|empty-array to detach all collections
     *
     * @param mixed $collections
     * @return boolean
     */
    private function shouldDetachAll($collections)
    {
        if (is_bool($collections) || is_null($collections) || empty($collections)) {
            return true;
        }

        if (is_countable($collections) && count($collections) === 0) {
            return true;
        }

        return false;
    }


    /**
     * Fetch collections
     *
     * returns single collection for single item
     * and multiple collections for multiple items
     * todo: exception / strict return types
     *
     * @param int|string|array|MediaCollection|Collection $collections
     * @return Collection|Model|Object|null
     */
    private function fetchCollections($collections)
    {
        // eloquent collection doesn't need to be fetched again
        // it's treated as a valid source of MediaCollection resource
        if ($collections instanceof EloquentCollection) {
            return $collections;
        }
        // todo: check for instance of media model / collection instead?
        if ($collections instanceof BaseCollection) {
            $ids = $collections->pluck('id')->all();
            return MediaCollection::find($ids);
        }

        if (is_object($collections) && isset($collections->id)) {
            return MediaCollection::find($collections->id);
        }

        if (is_numeric($collections)) {
            return MediaCollection::find($collections);
        }

        if (is_string($collections)) {
            return MediaCollection::findByName($collections);
        }

        // all array items should be of same type
        // find by id or name based on the type of first item in the array
        if (is_array($collections) && isset($collections[0])) {
            if (is_numeric($collections[0])) {
                return MediaCollection::find($collections);
            }

            if (is_string($collections[0])) {
                return MediaCollection::findByName($collections);
            }
        }

        return null;
    }
}
