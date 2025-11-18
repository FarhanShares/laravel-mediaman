<?php

namespace FarhanShares\MediaMan\Licensing;

use FarhanShares\MediaMan\Models\License;
use FarhanShares\MediaMan\Models\LicenseActivation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LicenseManager
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('mediaman.licensing', []);
    }

    /**
     * Validate a license key
     */
    public function validate(string $licenseKey, string $siteUrl, ?string $instanceId = null): array
    {
        // Check if licensing is enabled
        if (!$this->isEnabled()) {
            return [
                'valid' => true,
                'message' => 'License validation is disabled',
            ];
        }

        // Allow localhost by default
        if ($this->isLocalhost($siteUrl)) {
            return [
                'valid' => true,
                'message' => 'Localhost is always allowed',
                'localhost' => true,
            ];
        }

        // Check cache first
        $cacheKey = $this->getCacheKey('validate', $licenseKey, $siteUrl);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Find license
        $license = License::findByKey($licenseKey);

        if (!$license) {
            return $this->errorResponse('License key not found');
        }

        // Check if license is valid
        if (!$license->isValid()) {
            return $this->errorResponse('License is ' . $license->status);
        }

        // Check if site is activated
        $domain = parse_url($siteUrl, PHP_URL_HOST);
        $activation = null;

        if ($instanceId) {
            $activation = $license->activations()
                ->where('instance_id', $instanceId)
                ->active()
                ->first();
        }

        if (!$activation) {
            $activation = $license->activations()
                ->where('site_domain', $domain)
                ->active()
                ->first();
        }

        if (!$activation) {
            return $this->errorResponse('License not activated for this site');
        }

        // Update timestamps
        $license->markAsValidated();
        $activation->markAsChecked();

        $response = [
            'valid' => true,
            'license' => [
                'key' => $license->key,
                'type' => $license->type,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'features' => $license->features ?? [],
                'activation_limit' => $license->activation_limit,
                'activation_count' => $license->activation_count,
            ],
            'activation' => [
                'instance_id' => $activation->instance_id,
                'instance_name' => $activation->instance_name,
                'activated_at' => $activation->activated_at->toIso8601String(),
            ],
        ];

        // Cache the response
        Cache::put($cacheKey, $response, $this->getCacheTtl());

        return $response;
    }

    /**
     * Activate a license for a site
     */
    public function activate(string $licenseKey, string $siteUrl, string $instanceName, array $meta = []): array
    {
        // Check if licensing is enabled
        if (!$this->isEnabled()) {
            return [
                'success' => true,
                'message' => 'License validation is disabled',
            ];
        }

        // Allow localhost
        if ($this->isLocalhost($siteUrl)) {
            return [
                'success' => true,
                'message' => 'Localhost does not require activation',
                'localhost' => true,
            ];
        }

        // Find license
        $license = License::findByKey($licenseKey);

        if (!$license) {
            return $this->errorResponse('License key not found');
        }

        if (!$license->isValid()) {
            return $this->errorResponse('License is ' . $license->status);
        }

        // Check if already activated for this domain
        $domain = parse_url($siteUrl, PHP_URL_HOST);
        $existing = $license->activations()
            ->where('site_domain', $domain)
            ->active()
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'License already activated for this site',
                'instance_id' => $existing->instance_id,
                'activation' => [
                    'instance_id' => $existing->instance_id,
                    'instance_name' => $existing->instance_name,
                    'site_url' => $existing->site_url,
                    'activated_at' => $existing->activated_at->toIso8601String(),
                ],
            ];
        }

        // Check activation limit
        if ($license->hasReachedActivationLimit()) {
            return $this->errorResponse(
                'Activation limit reached. Please deactivate on another site first.',
                ['activation_limit' => $license->activation_limit]
            );
        }

        try {
            $activation = $license->activate($siteUrl, $instanceName, $meta);

            // Clear cache
            $this->clearCache($licenseKey);

            return [
                'success' => true,
                'message' => 'License activated successfully',
                'instance_id' => $activation->instance_id,
                'activation' => [
                    'instance_id' => $activation->instance_id,
                    'instance_name' => $activation->instance_name,
                    'site_url' => $activation->site_url,
                    'activated_at' => $activation->activated_at->toIso8601String(),
                ],
                'license' => [
                    'key' => $license->key,
                    'type' => $license->type,
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'features' => $license->features ?? [],
                    'activations_left' => $license->activation_limit - $license->activation_count,
                ],
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Deactivate a license activation
     */
    public function deactivate(string $licenseKey, string $instanceId): array
    {
        $license = License::findByKey($licenseKey);

        if (!$license) {
            return $this->errorResponse('License key not found');
        }

        $result = $license->deactivate($instanceId);

        if (!$result) {
            return $this->errorResponse('Activation not found or already deactivated');
        }

        // Clear cache
        $this->clearCache($licenseKey);

        return [
            'success' => true,
            'message' => 'License deactivated successfully',
            'activations_left' => $license->activation_limit - $license->activation_count,
        ];
    }

    /**
     * Validate license with LemonSqueezy API
     */
    public function validateWithLemonSqueezy(string $licenseKey, ?string $instanceId = null): array
    {
        $url = 'https://api.lemonsqueezy.com/v1/licenses/validate';

        try {
            $response = Http::post($url, [
                'license_key' => $licenseKey,
                'instance_id' => $instanceId,
            ]);

            if (!$response->successful()) {
                return $this->errorResponse('Failed to validate with LemonSqueezy');
            }

            $data = $response->json();

            // Verify product/store ID to prevent cross-product usage
            if (isset($data['meta']['product_id'])) {
                $expectedProductId = $this->config['lemonsqueezy']['product_id'] ?? null;
                if ($expectedProductId && $data['meta']['product_id'] != $expectedProductId) {
                    return $this->errorResponse('License key is for a different product');
                }
            }

            return [
                'valid' => $data['valid'] ?? false,
                'license_key' => $data['license_key'] ?? [],
                'instance' => $data['instance'] ?? null,
                'meta' => $data['meta'] ?? [],
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('LemonSqueezy API error: ' . $e->getMessage());
        }
    }

    /**
     * Check if license has a specific feature
     */
    public function hasFeature(string $licenseKey, string $feature): bool
    {
        $license = License::findValidByKey($licenseKey);

        if (!$license) {
            return false;
        }

        return $license->hasFeature($feature);
    }

    /**
     * Get license information
     */
    public function getLicenseInfo(string $licenseKey): ?array
    {
        $license = License::findByKey($licenseKey);

        if (!$license) {
            return null;
        }

        return [
            'key' => $license->key,
            'email' => $license->email,
            'type' => $license->type,
            'status' => $license->status,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'features' => $license->features ?? [],
            'activation_limit' => $license->activation_limit,
            'activation_count' => $license->activation_count,
            'activations_left' => $license->activation_limit - $license->activation_count,
            'created_at' => $license->created_at->toIso8601String(),
        ];
    }

    /**
     * Check if licensing is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Check if URL is localhost
     */
    protected function isLocalhost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            '.local',
            '.test',
            '.localhost',
        ];

        foreach ($localhostPatterns as $pattern) {
            if ($host === $pattern || Str::endsWith($host, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(string $operation, string $licenseKey, ?string $identifier = null): string
    {
        $parts = ['mediaman', 'license', $operation, md5($licenseKey)];

        if ($identifier) {
            $parts[] = md5($identifier);
        }

        return implode(':', $parts);
    }

    /**
     * Get cache TTL
     */
    protected function getCacheTtl(): int
    {
        return (int) ($this->config['cache_ttl'] ?? 3600);
    }

    /**
     * Clear license cache
     */
    protected function clearCache(string $licenseKey): void
    {
        Cache::forget($this->getCacheKey('validate', $licenseKey, '*'));
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, array $extra = []): array
    {
        return array_merge([
            'valid' => false,
            'success' => false,
            'error' => $message,
        ], $extra);
    }
}
