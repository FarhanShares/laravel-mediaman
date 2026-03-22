<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Support\Str;
use FarhanShares\MediaMan\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Media extends Model
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
    protected $appends = ['friendly_size',  'media_uri', 'media_url', 'type', 'extension'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (config('mediaman.use_uuids')) {
            $this->incrementing = false;
            $this->keyType = 'string';
        }
    }


    public static function booted()
    {
        static::creating(function ($media) {
            if (config('mediaman.use_uuids') && empty($media->getKey())) {
                $media->setAttribute($media->getKeyName(), (string) Str::uuid());
            }
        });

        static::deleted(static function ($media) {
            // delete the media directory
            $deleted = Storage::disk($media->disk)->deleteDirectory($media->getDirectory());
            // if failed, try deleting the file then
            !$deleted && Storage::disk($media->disk)->delete($media->getPath());
        });

        static::updating(function ($media) {
            // If the disk attribute is changed, validate the new disk usability
            if ($media->isDirty('disk')) {
                $newDisk = $media->disk; // updated disk
                self::ensureDiskUsability($newDisk);
            }
        });

        static::updated(function ($media) {
            $originalDisk = $media->getOriginal('disk');
            $newDisk = $media->disk;

            $originalFileName = $media->getOriginal('file_name');
            $newFileName = $media->file_name;

            $path = $media->getDirectory();

            // If the disk has changed, move the file to the new disk first
            if ($media->isDirty('disk')) {
                $filePathOnOriginalDisk = $path . '/' . $originalFileName;
                $fileContent = Storage::disk($originalDisk)->get($filePathOnOriginalDisk);

                // Store the file to the new disk
                Storage::disk($newDisk)->put($filePathOnOriginalDisk, $fileContent);

                // Delete the original file
                Storage::disk($originalDisk)->delete($filePathOnOriginalDisk);
            }

            // If the filename has changed, rename the file on the disk it currently resides
            if ($media->isDirty('file_name')) {
                // Rename the file in the storage
                Storage::disk($newDisk)->move($path . '/' . $originalFileName, $path . '/' . $newFileName);
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
     * Get the original media url.
     *
     * @return string
     */
    public function getMediaUrlAttribute()
    {
        return asset($this->filesystem()->url($this->getPath()));
    }

    /**
     * Get the original media uri.
     *
     * @return string
     */
    public function getMediaUriAttribute()
    {
        return $this->filesystem()->url($this->getPath());
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
        return $this->belongsToMany($this->collectionModel(), config('mediaman.tables.collection_media'), 'collection_id', 'media_id');
    }

    /**
     * Resolve the configured collection model class.
     *
     * @return string
     */
    protected function collectionModel(): string
    {
        return config('mediaman.models.collection');
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
        if (!$fetch) {
            return false;
        }

        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            return ($this->collections()->sync($ids, $detaching));
        }

        if (method_exists($fetch, 'getKey')) {
            return ($this->collections()->sync($fetch->getKey(), $detaching));
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
        if (!$fetch) {
            return null;
        }

        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            $res = $this->collections()->sync($ids, false);
            $attached  = count($res['attached']);
            return $attached > 0 ? $attached : null;
        }

        if (method_exists($fetch, 'getKey')) {
            $res = $this->collections()->sync($fetch->getKey(), false);
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
        if (!$fetch) {
            return null;
        }

        if (is_countable($fetch)) {
            $ids = $this->extractKeys($fetch);
            return $this->collections()->detach($ids);
        }

        if (method_exists($fetch, 'getKey')) {
            return $this->collections()->detach($fetch->getKey());
        }

        return null;
    }

    /**
     * Ensure the specified disk exists and is writable.
     *
     * This method first checks if the provided disk name exists in the
     * filesystems configuration. Next, it ensures that the disk is accessible
     * by attempting to write and then delete a temporary file.
     *
     * @param  string  $diskName  The name of the disk as defined in the filesystems configuration.
     *
     * @throws \InvalidArgumentException  If the disk is not defined in the filesystems configuration.
     * @throws \Exception  If there's an error writing to or deleting from the disk.
     *
     * @return void
     */
    protected static function ensureDiskUsability($diskName)
    {
        $allDisks = config('filesystems.disks');

        if (!array_key_exists($diskName, $allDisks)) {
            throw new \InvalidArgumentException("Disk [{$diskName}] is not defined in the filesystems configuration.");
        }

        // Early return if accessibility check is disabled
        if (!config('mediaman.check_disk_accessibility', false)) {
            return;
        }

        // Accessibility checks for read-write operations
        $disk = Storage::disk($diskName);
        $tempFileName = 'temp_check_file_' . uniqid();

        try {
            // Attempt to write to the disk
            $disk->put($tempFileName, 'check');

            // Now, attempt to delete the temporary file
            $disk->delete($tempFileName);
        } catch (\Exception $e) {
            throw new \Exception("Failed to write or delete on the disk [{$diskName}]. Error: " . $e->getMessage(), 0, $e);
        }
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
            $ids = $this->extractKeys($collections);
            $model = $this->collectionModel();

            return $model::find($ids);
        }

        if (is_object($collections) && method_exists($collections, 'getKey')) {
            $model = $this->collectionModel();

            return $model::find($collections->getKey());
        }

        if (is_numeric($collections)) {
            $model = $this->collectionModel();

            return $model::find($collections);
        }

        if (is_string($collections)) {
            $model = $this->collectionModel();

            return $model::findByName($collections);
        }

        // all array items should be of same type
        // find by id or name based on the type of first item in the array
        if (is_array($collections) && isset($collections[0])) {
            if (is_numeric($collections[0])) {
                $model = $this->collectionModel();

                return $model::find($collections);
            }

            if (is_string($collections[0])) {
                $model = $this->collectionModel();

                return $model::findByName($collections);
            }
        }

        return null;
    }

    /**
     * Extract primary keys from an iterable of models or scalar keys.
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
