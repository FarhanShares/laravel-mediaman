<?php

namespace FarhanShares\MediaMan\UI\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use FarhanShares\MediaMan\MediaUploaderPro;
use FarhanShares\MediaMan\Traits\HasLicenseCheck;
use FarhanShares\MediaMan\Security\SecurityManager;
use FarhanShares\MediaMan\Conversions\ConversionManager;

class UploadController extends Controller
{
    use HasLicenseCheck;

    /**
     * Handle file upload
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Check license (except for localhost)
        if (!$this->isLocalhost() && !$this->checkLicense('ui')) {
            return response()->json([
                'error' => 'MediaMan Pro license required for UI features'
            ], 403);
        }

        $request->validate([
            'file' => 'required|file',
            'collection' => 'nullable|string',
            'conversions' => 'nullable|array',
            'tags' => 'nullable|array',
            'ai_features' => 'nullable|array',
        ]);

        try {
            $uploader = MediaUploaderPro::source($request->file('file'));

            if ($request->has('collection')) {
                $uploader->toCollection($request->input('collection'));
            }

            if ($request->has('conversions')) {
                $uploader->withConversions($request->input('conversions'));
            }

            if ($request->has('tags')) {
                $uploader->withTags($request->input('tags'));
            }

            if ($request->has('ai_features') && config('mediaman.ai.enabled')) {
                $uploader->withAI($request->input('ai_features'));
            }

            // Inject managers
            $uploader->setSecurityManager(app(SecurityManager::class));
            $uploader->setConversionManager(app(ConversionManager::class));

            $media = $uploader->upload();

            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'uuid' => $media->uuid ?? null,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'friendly_size' => $media->friendly_size,
                    'url' => $media->media_url,
                    'type' => $media->type,
                    'extension' => $media->extension,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload with progress tracking (chunked upload)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadChunked(Request $request)
    {
        // Check license
        if (!$this->isLocalhost() && !$this->checkLicense('chunked_upload')) {
            return response()->json([
                'error' => 'MediaMan Pro license required for chunked uploads'
            ], 403);
        }

        // TODO: Implement chunked upload logic
        return response()->json([
            'error' => 'Chunked upload not yet implemented'
        ], 501);
    }

    /**
     * Validate license endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateLicense()
    {
        return response()->json([
            'is_localhost' => $this->isLocalhost(),
            'is_licensed' => $this->getLicenseManager()->isProLicensed(),
            'features' => $this->getLicenseManager()->getFeatures(),
        ]);
    }
}
