<?php

namespace FarhanShares\MediaMan\Facades;


use Illuminate\Support\Facades\Facade;
use FarhanShares\MediaMan\ConversionRegistry;

class Conversion extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConversionRegistry::class;
    }
}
