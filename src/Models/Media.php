<?php

namespace FarhanShares\MediaMan\Models;

use Countable;
use Illuminate\Support\Str;
use Illuminate\Support\Collection as BaseCollection;
use FarhanShares\MediaMan\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use FarhanShares\MediaMan\Models\MediaCollection;

class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'disk', 'display_name', 'name', 'mime_type', 'size', 'data'
    ];

    protected $casts = [
        'data' => Json::class
    ];

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
        return $this->getKey();
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


    public function collections()
    {
        return $this->belongsToMany(MediaCollection::class, config('mediaman.tables.collection_media'), 'collection_id', 'media_id');
    }


    public function syncCollection($collection, $detaching = true)
    {
        if ($this->shouldDetachAll($collection)) {
            return $this->collections()->sync([]);
        }

        if ($fetch = $this->fetchCollections($collection)) {
            return $this->collections()->sync($fetch->id, $detaching);
        }

        return false;
    }


    public function syncCollections($collections, $detaching = true)
    {
        if ($this->shouldDetachAll($collections)) {
            return $this->collections()->sync([]);
        }

        $fetch = $this->fetchCollections($collections);
        if (count($fetch) > 0) {
            $ids = $fetch->pluck('id');
            return $this->collections()->sync($ids, $detaching);
        }

        return false;
    }

    public function attachCollection($collection)
    {
        if ($fetch = $this->fetchCollections($collection)) {
            return $this->collections()->attach($fetch->id);
        }

        return false;
    }

    public function attachCollections($collections)
    {
        $fetch = $this->fetchCollections($collections);
        if (count($fetch) > 0) {
            $ids = $fetch->pluck('id');
            return $this->collections()->attach($ids);
        }

        return false;
    }

    public function detachCollection($collection)
    {
        if ($this->shouldDetachAll($collection)) {
            return $this->collections()->detach();
        }

        if ($fetch = $this->fetchCollections($collection)) {
            return $this->collections()->detach($fetch->id);
        }

        return false;
    }

    public function detachCollections($collections)
    {
        if ($this->shouldDetachAll($collections)) {
            return $this->collections()->detach();
        }

        if ($fetch = $this->fetchCollections($collections)) {
            $ids = $fetch->pluck('id')->all();
            return $this->collections()->detach($ids);
        }

        return false;
    }

    // bool|null|empty-string|empty-array to detach all collections
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

    // returns single collection for single item
    // and multiple collections for multiple items
    private function fetchCollections($collections)
    {
        // eloquent collection doesn't need to be fetched again
        // it's treated as a valid source of MediaCollection resource
        if ($collections instanceof EloquentCollection) {
            return $collections;
        }

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

        return false;
    }
}
