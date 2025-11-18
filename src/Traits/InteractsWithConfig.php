<?php

namespace FarhanShares\MediaMan\Traits;

trait InteractsWithConfig
{
    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return config("mediaman.{$key}", $default);
    }

    /**
     * Check if UUID is enabled
     *
     * @return bool
     */
    protected function isUuidEnabled(): bool
    {
        return (bool) $this->getConfig('use_uuid', false);
    }

    /**
     * Check if conversions should be queued
     *
     * @return bool
     */
    protected function shouldQueueConversions(): bool
    {
        return (bool) $this->getConfig('queue_conversions', true);
    }

    /**
     * Get the conversion queue name
     *
     * @return string
     */
    protected function getConversionQueue(): string
    {
        return $this->getConfig('conversion_queue', 'default');
    }

    /**
     * Get max file size
     *
     * @return int
     */
    protected function getMaxFileSize(): int
    {
        return (int) $this->getConfig('max_file_size', 104857600); // 100MB
    }

    /**
     * Get allowed MIME types
     *
     * @return array|null
     */
    protected function getAllowedMimeTypes(): ?array
    {
        $types = $this->getConfig('allowed_mimetypes');

        if (is_string($types)) {
            return explode(',', $types);
        }

        return $types;
    }
}
