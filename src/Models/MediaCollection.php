<?php

namespace FarhanShares\MediaMan\Models;


use Illuminate\Support\Str;
use FarhanShares\MediaMan\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

class MediaCollection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'created_at', 'updated_at',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('mediaman.tables.collections');
    }

    public  function scopeFindByName($query, $names, array $columns = ['*'])
    {
        if (is_array($names)) {
            return $query->select($columns)->whereIn('name', $names)->get();
        }

        return $query->select($columns)->where('name', $names)->first();
    }
    /**
     * The media that belong to the collection.
     */
    public function media()
    {
        return $this->belongsToMany(Media::class, config('mediaman.tables.collection_media'), 'media_id', 'collection_id');
    }
}
