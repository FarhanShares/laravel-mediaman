<?php

namespace FarhanShares\MediaMan\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use FarhanShares\MediaMan\Models\Media;

class MediaUploaded
{
    use Dispatchable, SerializesModels;

    public Media $media;
    public array $context;

    /**
     * Create a new event instance.
     */
    public function __construct(Media $media, array $context = [])
    {
        $this->media = $media;
        $this->context = $context;
    }
}
