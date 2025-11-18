<?php

namespace FarhanShares\MediaMan\Traits;

use FarhanShares\MediaMan\Exceptions\LicenseException;
use FarhanShares\MediaMan\UI\License\LicenseManager;

trait HasLicenseCheck
{
    /**
     * License manager instance
     *
     * @var LicenseManager
     */
    protected $licenseManager;

    /**
     * Get the license manager instance
     *
     * @return LicenseManager
     */
    protected function getLicenseManager(): LicenseManager
    {
        if (!$this->licenseManager) {
            $this->licenseManager = app(LicenseManager::class);
        }

        return $this->licenseManager;
    }

    /**
     * Check if feature is licensed
     *
     * @param string $feature
     * @param bool $throw
     * @return bool
     * @throws LicenseException
     */
    protected function checkLicense(string $feature, bool $throw = false): bool
    {
        $licensed = $this->getLicenseManager()->hasFeature($feature);

        if (!$licensed && $throw) {
            throw LicenseException::featureNotAvailable($feature);
        }

        return $licensed;
    }

    /**
     * Check if running on localhost
     *
     * @return bool
     */
    protected function isLocalhost(): bool
    {
        return $this->getLicenseManager()->isLocalhost();
    }

    /**
     * Require pro license
     *
     * @return void
     * @throws LicenseException
     */
    protected function requireProLicense(): void
    {
        if (!$this->getLicenseManager()->isProLicensed() && !$this->isLocalhost()) {
            throw LicenseException::missing();
        }
    }
}
