<?php

namespace FarhanShares\MediaMan\Exceptions;

use Exception;

class LicenseException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct(string $message = 'Invalid or missing license')
    {
        parent::__construct($message);
    }

    /**
     * Create exception for invalid license
     *
     * @return static
     */
    public static function invalid(): self
    {
        return new static('MediaMan Pro license is invalid or has expired');
    }

    /**
     * Create exception for missing license
     *
     * @return static
     */
    public static function missing(): self
    {
        return new static('MediaMan Pro license key is required for this feature');
    }

    /**
     * Create exception for feature not available
     *
     * @param string $feature
     * @return static
     */
    public static function featureNotAvailable(string $feature): self
    {
        return new static("Feature '{$feature}' is not available in your license");
    }
}
