<?php

namespace FarhanShares\MediaMan\UI\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Traits\HasLicenseCheck;

class MediaController extends Controller
{
    use HasLicenseCheck;

    /**
     * List all media
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Media::query();

        // Filter by collection
        if ($request->has('collection')) {
            $query->whereHas('collections', function ($q) use ($request) {
                $q->where('name', $request->input('collection'));
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('mime_type', 'like', $request->input('type') . '/%');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $media = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($media);
    }

    /**
     * Get single media item
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $media = config('mediaman.use_uuid') && config('mediaman.expose_uuid_in_routes')
            ? Media::findByUuid($id)
            : Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        return response()->json($media);
    }

    /**
     * Update media
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $media = config('mediaman.use_uuid') && config('mediaman.expose_uuid_in_routes')
            ? Media::findByUuid($id)
            : Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'data' => 'sometimes|array',
        ]);

        if ($request->has('name')) {
            $media->name = $request->input('name');
        }

        if ($request->has('data')) {
            $media->data = array_merge($media->data ?? [], $request->input('data'));
        }

        $media->save();

        return response()->json($media);
    }

    /**
     * Delete media
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $media = config('mediaman.use_uuid') && config('mediaman.expose_uuid_in_routes')
            ? Media::findByUuid($id)
            : Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }
}
