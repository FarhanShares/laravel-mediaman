<?php

namespace FarhanShares\MediaMan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use FarhanShares\MediaMan\Licensing\LicenseManager;

class LicenseController extends Controller
{
    protected LicenseManager $licenseManager;

    public function __construct(LicenseManager $licenseManager)
    {
        $this->licenseManager = $licenseManager;
    }

    /**
     * Validate a license key
     *
     * POST /api/mediaman/licenses/validate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'site_url' => 'required|url',
            'instance_id' => 'nullable|string',
        ]);

        $result = $this->licenseManager->validate(
            $request->input('license_key'),
            $request->input('site_url'),
            $request->input('instance_id')
        );

        if (!$result['valid']) {
            return response()->json($result, 401);
        }

        return response()->json($result);
    }

    /**
     * Activate a license for a site
     *
     * POST /api/mediaman/licenses/activate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'site_url' => 'required|url',
            'instance_name' => 'required|string|max:255',
            'meta' => 'nullable|array',
        ]);

        $result = $this->licenseManager->activate(
            $request->input('license_key'),
            $request->input('site_url'),
            $request->input('instance_name'),
            $request->input('meta', [])
        );

        if (!($result['success'] ?? false)) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Deactivate a license activation
     *
     * POST /api/mediaman/licenses/deactivate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'instance_id' => 'required|string',
        ]);

        $result = $this->licenseManager->deactivate(
            $request->input('license_key'),
            $request->input('instance_id')
        );

        if (!($result['success'] ?? false)) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Get license information
     *
     * GET /api/mediaman/licenses/{licenseKey}
     *
     * @param string $licenseKey
     * @return JsonResponse
     */
    public function show(string $licenseKey): JsonResponse
    {
        $info = $this->licenseManager->getLicenseInfo($licenseKey);

        if (!$info) {
            return response()->json([
                'error' => 'License not found',
            ], 404);
        }

        return response()->json($info);
    }
}
