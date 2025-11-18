<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use FarhanShares\MediaMan\Casts\Json;

class MediaVersion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'media_id',
        'uuid',
        'version_number',
        'file_name',
        'disk',
        'size',
        'mime_type',
        'data',
        'reason',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => Json::class,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            if (config('mediaman.use_uuid') && empty($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
        });

        static::deleted(function ($version) {
            // Delete the version file from storage
            Storage::disk($version->disk)->delete($version->getPath());
        });
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('mediaman.tables.versions', 'mediaman_versions');
    }

    /**
     * Get the parent media.
     */
    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Get the user who created this version.
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the path to the version file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->media->getDirectory() . '/versions/' . $this->version_number . '/' . $this->file_name;
    }

    /**
     * Get the full URL to the version.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->getPath());
    }

    /**
     * Get the filesystem instance.
     */
    public function filesystem()
    {
        return Storage::disk($this->disk);
    }

    /**
     * Get friendly file size.
     *
     * @return string
     */
    public function getFriendlySizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;

        if ($size == 0) {
            return '0 ' . $units[1];
        }

        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Restore this version as the current media.
     *
     * @return Media
     */
    public function restore(): Media
    {
        $media = $this->media;

        // Create a new version from current media before restoring
        $media->createVersion('Backup before restoring to version ' . $this->version_number);

        // Copy version file to media location
        $versionContent = $this->filesystem()->get($this->getPath());
        $media->filesystem()->put($media->getPath(), $versionContent);

        // Update media attributes
        $media->update([
            'file_name' => $this->file_name,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
        ]);

        return $media->fresh();
    }
}
