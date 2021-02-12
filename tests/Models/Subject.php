<?php

namespace FarhanShares\MediaMan\Tests\Models;


use Illuminate\Database\Eloquent\Model;
use FarhanShares\MediaMan\Traits\HasMedia;

class Subject extends Model
{
    use HasMedia;

    public function registerMediaGroups()
    {
        $this->addMediaChannel('converted-images')
            ->performConversions('conversion');
    }
}
