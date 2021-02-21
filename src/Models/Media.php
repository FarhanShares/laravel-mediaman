<?php

namespace FarhanShares\MediaMan\Models;


use Illuminate\Support\Str;
use FarhanShares\MediaMan\Casts\Json;
use Illuminate\Database\Eloquent\Model;
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


    public function syncCollection($collection, $detaching = false)
    {
        if (is_numeric($collection)) {
            $fetch = MediaCollection::find($collection);
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


    public function syncCollections(array $collections, $detaching = false)
    {
        if (is_numeric($collections[0])) {
            $fetchCollections = MediaCollection::find($collections);
        } else {
            $fetchCollections = MediaCollection::findByName($collections);
        }

        $ids = $fetchCollections->pluck('id');
        return $this->collections()->sync($ids, $detaching);
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
        if (is_numeric($collections[0])) {
            return $this->collections()->attach($collections);
            // $fetchCollections = MediaCollection::find($collections);
        }

        if (is_object($collections[0])) {
            $ids = $collections->pluck('id');
            return $this->collections()->attach($ids);
        }

        $fetchCollections = MediaCollection::findByName($collections);
        $ids = $fetchCollections->pluck('id');
        if ($ids) {
            return $this->collections()->attach($ids);
        }
        return false;
    }
}
