<?php

namespace FarhanShares\MediaMan\Traits;

use FarhanShares\MediaMan\Exceptions\FileSizeException;
use FarhanShares\MediaMan\Exceptions\InvalidMimeTypeException;
use Illuminate\Http\UploadedFile;

trait ValidatesMedia
{
    /**
     * Validate the source file
     *
     * @return void
     * @throws FileSizeException
     * @throws InvalidMimeTypeException
     */
    protected function validateSource(): void
    {
        if (!$this->source instanceof UploadedFile) {
            return;
        }

        $this->validateFileSize();

        if (config('mediaman.check_mime_type')) {
            $this->validateMimeType();
        }
    }

    /**
     * Validate file size
     *
     * @return void
     * @throws FileSizeException
     */
    protected function validateFileSize(): void
    {
        $maxSize = $this->getMaxFileSize();

        if ($this->source->getSize() > $maxSize) {
            throw new FileSizeException(
                "File size ({$this->source->getSize()} bytes) exceeds maximum allowed size ({$maxSize} bytes)"
            );
        }
    }

    /**
     * Validate MIME type
     *
     * @return void
     * @throws InvalidMimeTypeException
     */
    protected function validateMimeType(): void
    {
        $allowedTypes = $this->getAllowedMimeTypes();

        if ($allowedTypes === null) {
            return;
        }

        $mimeType = $this->source->getMimeType();

        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidMimeTypeException(
                "MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedTypes)
            );
        }
    }

    /**
     * Sanitize filename
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove directory traversal attempts
        $filename = basename($filename);

        // Remove special characters but keep extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Replace problematic characters
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        return $name . '.' . $extension;
    }
}
