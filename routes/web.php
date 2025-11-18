<?php

use Illuminate\Support\Facades\Route;
use FarhanShares\MediaMan\UI\Controllers\UploadController;
use FarhanShares\MediaMan\UI\Controllers\MediaController;
use FarhanShares\MediaMan\Http\Middleware\MediaManRateLimiter;

/*
|--------------------------------------------------------------------------
| MediaMan Pro Routes
|--------------------------------------------------------------------------
|
| Here are the routes for MediaMan Pro UI and API features.
| These routes can be customized via config/mediaman.php
|
*/

// UI Routes
if (config('mediaman.enable_ui')) {
    $prefix = config('mediaman.ui_route_prefix', 'mediaman');
    $middleware = config('mediaman.ui_middleware', ['web', 'auth']);

    Route::prefix($prefix)
        ->middleware($middleware)
        ->name('mediaman.')
        ->group(function () {
            // Upload routes
            Route::post('/upload', [UploadController::class, 'upload'])
                ->middleware(MediaManRateLimiter::class . ':upload')
                ->name('upload');

            Route::post('/upload/chunked', [UploadController::class, 'uploadChunked'])
                ->middleware(MediaManRateLimiter::class . ':upload')
                ->name('upload.chunked');

            Route::post('/upload/batch', [UploadController::class, 'uploadBatch'])
                ->middleware(MediaManRateLimiter::class . ':batch')
                ->name('upload.batch');

            Route::get('/batch/{batchId}/status', [UploadController::class, 'batchStatus'])
                ->name('batch.status');

            Route::get('/validate-license', [UploadController::class, 'validateLicense'])
                ->name('validate-license');

            // Media management routes
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::get('/media/{id}', [MediaController::class, 'show'])->name('media.show');
            Route::put('/media/{id}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

            // Batch operations
            Route::post('/media/batch/delete', [MediaController::class, 'batchDelete'])
                ->name('media.batch.delete');

            // Version routes
            Route::get('/media/{id}/versions', [MediaController::class, 'versions'])
                ->name('media.versions');
            Route::post('/media/{id}/versions', [MediaController::class, 'createVersion'])
                ->name('media.versions.create');
            Route::post('/versions/{versionId}/restore', [MediaController::class, 'restoreVersion'])
                ->name('versions.restore');

            // Tag routes
            Route::get('/tags', [MediaController::class, 'tags'])->name('tags.index');
            Route::post('/media/{id}/tags', [MediaController::class, 'attachTags'])
                ->name('media.tags.attach');
            Route::delete('/media/{id}/tags', [MediaController::class, 'detachTags'])
                ->name('media.tags.detach');
        });
}

// API Routes
if (config('mediaman.enable_api')) {
    $prefix = config('mediaman.api_route_prefix', 'api/mediaman');
    $middleware = array_merge(
        config('mediaman.api_middleware', ['api', 'auth:sanctum']),
        [MediaManRateLimiter::class . ':api']
    );

    Route::prefix($prefix)
        ->middleware($middleware)
        ->name('mediaman.api.')
        ->group(function () {
            // Same routes as UI but for API
            Route::apiResource('media', MediaController::class);
            Route::post('upload', [UploadController::class, 'upload']);
            Route::post('batch/upload', [UploadController::class, 'uploadBatch']);
            Route::get('batch/{batchId}/status', [UploadController::class, 'batchStatus']);
        });
}

// OpenAPI Documentation Route
if (config('mediaman.openapi.enabled')) {
    Route::get(config('mediaman.openapi.route', 'mediaman/docs'))
        ->middleware(config('mediaman.openapi.middleware', ['web']))
        ->name('mediaman.docs')
        ->uses(function () {
            return response()->json([
                'message' => 'MediaMan API Documentation',
                'note' => 'Install dedoc/scramble or l5-swagger package for auto-generated docs',
                'manual_docs' => route('mediaman.api.media.index'),
            ]);
        });
}
