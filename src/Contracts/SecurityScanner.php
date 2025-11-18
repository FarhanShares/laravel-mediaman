<?php

namespace FarhanShares\MediaMan\Contracts;

use Illuminate\Http\UploadedFile;

interface SecurityScanner
{
    /**
     * Scan file for viruses
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function scanForVirus(UploadedFile $file): bool;

    /**
     * Validate MIME type
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function validateMimeType(UploadedFile $file): bool;

    /**
     * Generate signed URL
     *
     * @param string $url
     * @param int $expiration
     * @return string
     */
    public function signUrl(string $url, int $expiration = 60): string;

    /**
     * Verify signed URL
     *
     * @param string $url
     * @param string $signature
     * @param int $expires
     * @return bool
     */
    public function verifySignedUrl(string $url, string $signature, int $expires): bool;
}
