<?php

namespace FarhanShares\MediaMan\Exceptions;

use Exception;

class FileSizeException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct(string $message = 'File size exceeds maximum allowed size')
    {
        parent::__construct($message);
    }

    /**
     * Create exception for specific size
     *
     * @param int $size
     * @param int $maxSize
     * @return static
     */
    public static function exceeds(int $size, int $maxSize): self
    {
        return new static("File size ({$size} bytes) exceeds maximum allowed size ({$maxSize} bytes)");
    }
}
