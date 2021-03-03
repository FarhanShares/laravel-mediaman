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

        if (is_numeric($collection)) {
            $fetch = MediaCollection::find($collection);
        } else if (is_object($collection)) {
            $fetch = MediaCollection::find($collection->id);
        } else if (is_string($collection)) {
            $fetch = MediaCollection::findByName($collection);
        } else {
            return false;
        }

        if ($fetch) {
            return $this->collections()->sync($fetch->id, $detaching);
        }
        return false;
    }


    public function syncCollections($collections, $detaching = true)
    {
        if ($this->shouldDetachAll($collections)) {
            return $this->collections()->sync([]);
        }

        // fetch collections based on the first item type
        // to verify the collection really exists
        // todo: throw exception?
        // todo: improve it (instance of Model or Collection checking)
        if (is_numeric($collections[0])) {
            $fetchCollections = MediaCollection::find($collections);
        } else {
            $fetchCollections = MediaCollection::findByName($collections);
        }

        // perform synchronization
        if ($fetchCollections) {
            $ids = $fetchCollections->pluck('id');
            return $this->collections()->sync($ids, $detaching);
        }
        // todo: throw exception?
        return false;
    }

    public function attachCollection($collection)
    {
        if (is_string($collection) && $fetch = MediaCollection::findByName($collection)) {
            return $this->collections()->attach($fetch->id);
        }

        $id = is_numeric($collection)
            ? $collection
            : (is_object($collection)
                ? $collection->id
                : null);

        if ($id) {
            return $this->collections()->attach($id);
        }

        return false;
    }

    public function attachCollections($collections)
    {
        if (is_object($collections)) {
            $ids = $collections->pluck('id');
            return $this->collections()->attach($ids);
        }

        if (is_array($collections) && is_numeric($collections[0])) {
            return $this->collections()->attach($collections);
        }

        if (is_array($collections) && is_string($collections[0])) {
            $fetchCollections = MediaCollection::findByName($collections);
            $ids = $fetchCollections->pluck('id');
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

        if (is_object($collections)) {
            $ids = $collections->pluck('id');
            return $this->collections()->detach($ids);
        }

        if (is_array($collections) && is_numeric($collections[0])) {
            return $this->collections()->detach($collections);
        }

        if (is_array($collections) && is_string($collections[0])) {
            $fetchCollections = MediaCollection::findByName($collections);
            $ids = $fetchCollections->pluck('id');
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


        if ($collections instanceof BaseCollection || $collections instanceof EloquentCollection) {
            $ids = $collections->pluck('id')->all();
            return $this->collections()->find($ids);
        }

        if (is_object($collections)) {
            return $this->collections()->find($collections->id);
        }

        if (is_numeric($collections)) {
            return $this->collections()->find($collections);
        }

        if (is_string($collections)) {
            return $this->collections()->findByName($collections);
        }

        if (is_array($collections) && count($collections) > 0) {
            if (is_numeric($collections[0])) {
                return $this->collections()->find($collections);
            }

            if (is_string($collections[0])) {
                return $this->collections()->findByName($collections);
            }
        }

        return false;
    }
}
