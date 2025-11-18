<?php

namespace FarhanShares\MediaMan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use FarhanShares\MediaMan\Licensing\LicenseManager;

class CheckLicense
{
    protected LicenseManager $licenseManager;

    public function __construct(LicenseManager $licenseManager)
    {
        $this->licenseManager = $licenseManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $feature
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $feature = null)
    {
        // Skip if licensing is disabled
        if (!$this->licenseManager->isEnabled()) {
            return $next($request);
        }

        $licenseKey = config('mediaman.licensing.key');

        if (!$licenseKey) {
            return response()->json([
                'error' => 'No license key configured',
            ], 403);
        }

        $siteUrl = config('app.url');
        $instanceId = $this->getInstanceId();

        $result = $this->licenseManager->validate($licenseKey, $siteUrl, $instanceId);

        if (!$result['valid']) {
            return response()->json([
                'error' => $result['error'] ?? 'Invalid license',
            ], 403);
        }

        // Check specific feature if required
        if ($feature && !$this->licenseManager->hasFeature($licenseKey, $feature)) {
            return response()->json([
                'error' => "License does not have access to feature: {$feature}",
            ], 403);
        }

        // Attach license info to request
        $request->attributes->set('license', $result['license'] ?? []);

        return $next($request);
    }

    /**
     * Get or generate instance ID
     */
    protected function getInstanceId(): string
    {
        $cacheKey = 'mediaman:license:instance_id';

        return cache()->rememberForever($cacheKey, function () {
            return \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
