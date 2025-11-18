<?php

namespace FarhanShares\MediaMan\Exceptions;

use Exception;

class SecurityException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct(string $message = 'Security validation failed')
    {
        parent::__construct($message);
    }

    /**
     * Create exception for virus detection
     *
     * @param string $virus
     * @return static
     */
    public static function virusDetected(string $virus): self
    {
        return new static("Virus detected: {$virus}");
    }

    /**
     * Create exception for malicious content
     *
     * @return static
     */
    public static function maliciousContent(): self
    {
        return new static('Potentially malicious file detected');
    }
}
