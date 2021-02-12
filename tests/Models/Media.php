<?php

namespace FarhanShares\MediaMan\Tests\Models;


use FarhanShares\MediaMan\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    protected $fillable = [
        'name', 'file_name', 'disk', 'mime_type', 'size', 'data', 'custom_attribute',
    ];
}
