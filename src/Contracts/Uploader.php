<?php

namespace FarhanShares\MediaMan\Contracts;

use FarhanShares\MediaMan\Models\Media;
use Illuminate\Http\UploadedFile;

interface Uploader
{
    /**
     * Create uploader instance from a source
     *
     * @param UploadedFile|string $source
     * @return self
     */
    public static function source($source): self;

    /**
     * Set the collection for the media
     *
     * @param string $name
     * @return self
     */
    public function setCollection(string $name): self;

    /**
     * Set the disk for storage
     *
     * @param string $disk
     * @return self
     */
    public function setDisk(string $disk): self;

    /**
     * Set custom metadata
     *
     * @param array $data
     * @return self
     */
    public function withData(array $data): self;

    /**
     * Upload the file
     *
     * @return Media
     */
    public function upload(): Media;
}
