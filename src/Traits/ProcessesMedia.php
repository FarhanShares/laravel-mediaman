<?php

namespace FarhanShares\MediaMan\Traits;

use FarhanShares\MediaMan\Models\Media;
use Illuminate\Http\UploadedFile;

trait ProcessesMedia
{
    /**
     * Prepare metadata for the media
     *
     * @return void
     */
    protected function prepareMetadata(): void
    {
        if ($this->source instanceof UploadedFile) {
            $this->metadata['original_name'] = $this->source->getClientOriginalName();
            $this->metadata['mime_type'] = $this->source->getMimeType();
            $this->metadata['size'] = $this->source->getSize();
        }
    }

    /**
     * Perform standard upload
     *
     * @param callable|null $callback
     * @return Media
     */
    protected function performUpload(?callable $callback = null): Media
    {
        $model = config('mediaman.models.media');
        $media = new $model();

        // Set basic attributes
        $media->name = $this->name ?? pathinfo($this->fileName, PATHINFO_FILENAME);
        $media->file_name = $this->fileName;
        $media->disk = $this->disk ?: config('mediaman.disk');
        $media->mime_type = $this->source->getMimeType();
        $media->size = $this->source->getSize();
        $media->data = array_merge($this->data, $this->metadata);

        $media->save();

        // Store file
        $media->filesystem()->putFileAs(
            $media->getDirectory(),
            $this->source,
            $this->fileName
        );

        // Handle collections
        $this->attachCollections($media);

        // Progress callback
        if ($callback) {
            $callback(100, $media);
        }

        return $media;
    }

    /**
     * Perform chunked upload
     *
     * @param callable|null $callback
     * @return Media
     */
    protected function performChunkedUpload(?callable $callback = null): Media
    {
        // TODO: Implement chunked upload
        // For now, fallback to standard upload
        return $this->performUpload($callback);
    }

    /**
     * Handle string source (URL or path)
     *
     * @param string $source
     * @return void
     */
    protected function handleStringSource(string $source): void
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            // TODO: Implement URL download
            throw new \RuntimeException('URL upload not yet implemented');
        } else {
            // Local file path
            if (!file_exists($source)) {
                throw new \InvalidArgumentException("File does not exist: {$source}");
            }

            $this->source = new UploadedFile(
                $source,
                basename($source),
                mime_content_type($source),
                null,
                true
            );
        }
    }

    /**
     * Handle PSR-7 stream source
     *
     * @param mixed $source
     * @return void
     */
    protected function handleStreamSource($source): void
    {
        // TODO: Implement PSR-7 stream support
        throw new \RuntimeException('PSR-7 stream upload not yet implemented');
    }

    /**
     * Attach collections to media
     *
     * @param Media $media
     * @return void
     */
    protected function attachCollections(Media $media): void
    {
        if (count($this->collections) > 0) {
            $collectionModel = config('mediaman.models.collection');

            foreach ($this->collections as $collectionName) {
                $collection = $collectionModel::firstOrCreate([
                    'name' => $collectionName
                ]);

                $media->collections()->attach($collection->id);
            }
        } else {
            // Add to default collection
            $collectionModel = config('mediaman.models.collection');
            $collection = $collectionModel::findByName(config('mediaman.collection'));

            if ($collection) {
                $media->collections()->attach($collection->id);
            }
        }
    }
}
