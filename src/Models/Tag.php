<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use FarhanShares\MediaMan\Casts\Json;

class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'metadata',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => Json::class,
        'usage_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }

            if (empty($tag->type)) {
                $tag->type = 'user-defined';
            }
        });
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('mediaman.tables.tags', 'mediaman_tags');
    }

    /**
     * Get all media with this tag.
     */
    public function media()
    {
        return $this->belongsToMany(
            Media::class,
            config('mediaman.tables.media_tags', 'mediaman_media_tags'),
            'tag_id',
            'media_id'
        )->withTimestamps()->withPivot(['tagged_by', 'user_id']);
    }

    /**
     * Find or create a tag by name.
     *
     * @param string $name
     * @param string $type
     * @return static
     */
    public static function findOrCreateByName(string $name, string $type = 'user-defined'): self
    {
        $slug = Str::slug($name);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'type' => $type]
        );
    }

    /**
     * Find tag by slug.
     *
     * @param string $slug
     * @return static|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Scope to get popular tags.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope to filter by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Increment usage count.
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement usage count.
     *
     * @return void
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
    }

    /**
     * Search tags by name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
