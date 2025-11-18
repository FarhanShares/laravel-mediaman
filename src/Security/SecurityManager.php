<?php

namespace FarhanShares\MediaMan\Security;

use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Contracts\SecurityScanner;
use FarhanShares\MediaMan\Exceptions\SecurityException;
use FarhanShares\MediaMan\Exceptions\InvalidMimeTypeException;

class SecurityManager implements SecurityScanner
{
    /**
     * Scan file for viruses using ClamAV
     *
     * @param UploadedFile $file
     * @return bool
     * @throws SecurityException
     */
    public function scanForVirus(UploadedFile $file): bool
    {
        if (!config('mediaman.virus_scan')) {
            return true;
        }

        // TODO: Implement ClamAV integration
        // For now, perform basic checks
        $content = file_get_contents($file->path());

        // Check for embedded PHP code
        if ($this->containsPhpCode($content)) {
            throw SecurityException::maliciousContent();
        }

        return true;
    }

    /**
     * Validate MIME type deeply
     *
     * @param UploadedFile $file
     * @return bool
     * @throws InvalidMimeTypeException
     * @throws SecurityException
     */
    public function validateMimeType(UploadedFile $file): bool
    {
        $allowedTypes = config('mediaman.allowed_mimetypes');

        if (!$allowedTypes) {
            return true;
        }

        // Convert string to array if needed
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(',', $allowedTypes);
        }

        // Check actual content, not just extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->path());
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw InvalidMimeTypeException::notAllowed($mimeType, $allowedTypes);
        }

        // Additional checks for images
        if (str_starts_with($mimeType, 'image/')) {
            return $this->validateImage($file);
        }

        return true;
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @return bool
     * @throws SecurityException
     */
    protected function validateImage(UploadedFile $file): bool
    {
        try {
            $image = getimagesize($file->path());

            if ($image === false) {
                throw SecurityException::maliciousContent();
            }

            // Check for embedded PHP
            $content = file_get_contents($file->path());

            if ($this->containsPhpCode($content)) {
                throw SecurityException::maliciousContent();
            }

            return true;
        } catch (\Exception $e) {
            throw new SecurityException("Image validation failed: " . $e->getMessage());
        }
    }

    /**
     * Check if content contains PHP code
     *
     * @param string $content
     * @return bool
     */
    protected function containsPhpCode(string $content): bool
    {
        return preg_match('/<\?php|<\?=|<script[\s\S]*?php/i', $content) === 1;
    }

    /**
     * Generate signed URL
     *
     * @param string $url
     * @param int $expiration Minutes until expiration
     * @return string
     */
    public function signUrl(string $url, int $expiration = 60): string
    {
        if (!config('mediaman.signed_urls')) {
            return $url;
        }

        $expires = now()->addMinutes($expiration)->timestamp;
        $signature = $this->generateSignature($url, $expires);

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query([
            'expires' => $expires,
            'signature' => $signature,
        ]);
    }

    /**
     * Verify signed URL
     *
     * @param string $url
     * @param string $signature
     * @param int $expires
     * @return bool
     */
    public function verifySignedUrl(string $url, string $signature, int $expires): bool
    {
        if (!config('mediaman.signed_urls')) {
            return true;
        }

        // Check expiration
        if ($expires < time()) {
            return false;
        }

        // Verify signature
        $expected = $this->generateSignature($url, $expires);

        return hash_equals($expected, $signature);
    }

    /**
     * Generate signature for URL
     *
     * @param string $url
     * @param int $expires
     * @return string
     */
    protected function generateSignature(string $url, int $expires): string
    {
        return hash_hmac('sha256', "{$url}:{$expires}", config('app.key'));
    }

    /**
     * Sanitize filename
     *
     * @param string $filename
     * @return string
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove directory traversal attempts
        $filename = basename($filename);

        // Get extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Replace problematic characters
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        // Limit length
        $name = substr($name, 0, 200);

        return $name . '.' . strtolower($extension);
    }

    /**
     * Check if file size is within limits
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function validateFileSize(UploadedFile $file): bool
    {
        $maxSize = config('mediaman.max_file_size', 104857600); // 100MB default

        return $file->getSize() <= $maxSize;
    }
}
