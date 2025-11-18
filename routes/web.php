<?php

use Illuminate\Support\Facades\Route;
use FarhanShares\MediaMan\UI\Controllers\UploadController;
use FarhanShares\MediaMan\UI\Controllers\MediaController;

/*
|--------------------------------------------------------------------------
| MediaMan Pro Routes
|--------------------------------------------------------------------------
|
| Here are the routes for MediaMan Pro UI features.
| These routes can be customized via config/mediaman.php
|
*/

if (config('mediaman.enable_ui')) {
    $prefix = config('mediaman.ui_route_prefix', 'mediaman');
    $middleware = config('mediaman.ui_middleware', ['web', 'auth']);

    Route::prefix($prefix)
        ->middleware($middleware)
        ->name('mediaman.')
        ->group(function () {
            // Upload routes
            Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
            Route::post('/upload/chunked', [UploadController::class, 'uploadChunked'])->name('upload.chunked');
            Route::get('/validate-license', [UploadController::class, 'validateLicense'])->name('validate-license');

            // Media management routes
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::get('/media/{id}', [MediaController::class, 'show'])->name('media.show');
            Route::put('/media/{id}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
        });
}
