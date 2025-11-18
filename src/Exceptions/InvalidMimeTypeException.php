<?php

namespace FarhanShares\MediaMan\Exceptions;

use Exception;

class InvalidMimeTypeException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct(string $message = 'Invalid MIME type')
    {
        parent::__construct($message);
    }

    /**
     * Create exception for disallowed type
     *
     * @param string $mimeType
     * @param array $allowedTypes
     * @return static
     */
    public static function notAllowed(string $mimeType, array $allowedTypes): self
    {
        return new static(
            "MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedTypes)
        );
    }
}
