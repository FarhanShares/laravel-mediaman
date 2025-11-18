<?php

namespace FarhanShares\MediaMan\Contracts;

use FarhanShares\MediaMan\Models\Media;

interface ConversionProcessor
{
    /**
     * Register a conversion
     *
     * @param string $name
     * @param callable $conversion
     * @return self
     */
    public function register(string $name, callable $conversion): self;

    /**
     * Process conversions for media
     *
     * @param Media $media
     * @param array $conversions
     * @return void
     */
    public function process(Media $media, array $conversions): void;

    /**
     * Process conversions in queue
     *
     * @param Media $media
     * @param array $conversions
     * @return void
     */
    public function processInQueue(Media $media, array $conversions): void;
}
