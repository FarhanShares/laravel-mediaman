<?php

namespace FarhanShares\MediaMan\UI\License;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use FarhanShares\MediaMan\Contracts\LicenseValidator;

class LicenseManager implements LicenseValidator
{
    protected ?string $licenseKey = null;
    protected array $features = [];
    protected string $version = '2.0.0';

    /**
     * Create a new license manager instance.
     */
    public function __construct()
    {
        $this->licenseKey = config('mediaman.license_key');
    }

    /**
     * Check if running on localhost
     *
     * @return bool
     */
    public function isLocalhost(): bool
    {
        $host = request()->getHost();

        return in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
            || str_starts_with($host, '172.16.')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost')
            || app()->environment('local', 'testing');
    }

    /**
     * Validate license key
     *
     * @param string|null $key
     * @return bool
     */
    public function validate(?string $key = null): bool
    {
        // Always allow on localhost/development
        if ($this->isLocalhost()) {
            $this->features = ['*']; // All features enabled
            return true;
        }

        $key = $key ?? $this->licenseKey;

        if (!$key) {
            return false;
        }

        return Cache::remember("mediaman_license_{$key}", 3600, function () use ($key) {
            try {
                $response = Http::timeout(5)->post('https://api.mediaman.dev/validate', [
                    'key' => $key,
                    'domain' => request()->getHost(),
                    'version' => $this->getVersion(),
                    'ip' => request()->ip(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->features = $data['features'] ?? [];
                    return $data['valid'] ?? false;
                }

                return false;
            } catch (\Exception $e) {
                // Log error but don't break the app
                logger()->error('MediaMan license validation failed', [
                    'error' => $e->getMessage(),
                    'key' => substr($key, 0, 8) . '...',
                ]);

                // Fail open in case of network issues
                return false;
            }
        });
    }

    /**
     * Check if feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        if ($this->isLocalhost()) {
            return true;
        }

        // Check if license is validated first
        if (!$this->validate()) {
            return false;
        }

        // Wildcard means all features
        if (in_array('*', $this->features)) {
            return true;
        }

        return in_array($feature, $this->features);
    }

    /**
     * Get all enabled features
     *
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Get package version
     *
     * @return string
     */
    protected function getVersion(): string
    {
        $composerFile = base_path('vendor/farhanshares/laravel-mediaman/composer.json');

        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? $this->version;
        }

        return $this->version;
    }

    /**
     * Clear license cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        if ($this->licenseKey) {
            Cache::forget("mediaman_license_{$this->licenseKey}");
        }
    }

    /**
     * Check if Pro features are available
     *
     * @return bool
     */
    public function isProLicensed(): bool
    {
        return $this->validate();
    }
}
