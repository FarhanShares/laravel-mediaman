<?php

namespace FarhanShares\MediaMan\Contracts;

interface LicenseValidator
{
    /**
     * Validate license key
     *
     * @param string|null $key
     * @return bool
     */
    public function validate(?string $key = null): bool;

    /**
     * Check if running on localhost
     *
     * @return bool
     */
    public function isLocalhost(): bool;

    /**
     * Check if a specific feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool;
}
