<?php

namespace FarhanShares\MediaMan;

use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Jobs\ProcessBatchUpload;

class BatchUploader
{
    protected array $files = [];
    protected ?string $collection = null;
    protected ?string $disk = null;
    protected array $conversions = [];
    protected array $metadata = [];
    protected bool $useQueue = true;
    protected ?callable $progressCallback = null;
    protected ?callable $successCallback = null;
    protected ?callable $errorCallback = null;

    /**
     * Create a new batch uploader instance.
     *
     * @param array $files
     * @return static
     */
    public static function source(array $files): self
    {
        $instance = new self();
        $instance->files = $files;
        return $instance;
    }

    /**
     * Set the collection for all uploads.
     *
     * @param string $collection
     * @return $this
     */
    public function toCollection(string $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Set the disk for all uploads.
     *
     * @param string $disk
     * @return $this
     */
    public function toDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Set conversions for all uploads.
     *
     * @param array $conversions
     * @return $this
     */
    public function withConversions(array $conversions): self
    {
        $this->conversions = $conversions;
        return $this;
    }

    /**
     * Set metadata for all uploads.
     *
     * @param array $metadata
     * @return $this
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Process synchronously instead of using queue.
     *
     * @return $this
     */
    public function synchronously(): self
    {
        $this->useQueue = false;
        return $this;
    }

    /**
     * Set progress callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function onProgress(callable $callback): self
    {
        $this->progressCallback = $callback;
        return $this;
    }

    /**
     * Set success callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function onSuccess(callable $callback): self
    {
        $this->successCallback = $callback;
        return $this;
    }

    /**
     * Set error callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function onError(callable $callback): self
    {
        $this->errorCallback = $callback;
        return $this;
    }

    /**
     * Upload all files.
     *
     * @return Collection
     */
    public function upload(): Collection
    {
        if ($this->useQueue && config('mediaman.batch.use_queue', true)) {
            return $this->uploadViaQueue();
        }

        return $this->uploadSynchronously();
    }

    /**
     * Upload via queue.
     *
     * @return Collection
     */
    protected function uploadViaQueue(): Collection
    {
        $batchId = \Illuminate\Support\Str::uuid();

        dispatch(new ProcessBatchUpload(
            $this->files,
            $batchId,
            $this->collection,
            $this->disk,
            $this->conversions,
            $this->metadata
        ))->onQueue(config('mediaman.batch.queue', 'default'));

        // Return empty collection with batch ID for tracking
        return collect(['batch_id' => $batchId, 'status' => 'queued']);
    }

    /**
     * Upload synchronously.
     *
     * @return Collection
     */
    protected function uploadSynchronously(): Collection
    {
        $results = collect();
        $total = count($this->files);
        $processed = 0;

        foreach ($this->files as $file) {
            try {
                $uploader = MediaUploaderPro::source($file);

                if ($this->collection) {
                    $uploader->toCollection($this->collection);
                }

                if ($this->disk) {
                    $uploader->toDisk($this->disk);
                }

                if (!empty($this->conversions)) {
                    $uploader->withConversions($this->conversions);
                }

                if (!empty($this->metadata)) {
                    $uploader->withData($this->metadata);
                }

                $media = $uploader->upload();

                $results->push([
                    'status' => 'success',
                    'media' => $media,
                    'file' => $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
                ]);

                if ($this->successCallback) {
                    call_user_func($this->successCallback, $media, $file);
                }
            } catch (\Exception $e) {
                $results->push([
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'file' => $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
                ]);

                if ($this->errorCallback) {
                    call_user_func($this->errorCallback, $e, $file);
                }
            }

            $processed++;
            if ($this->progressCallback) {
                call_user_func($this->progressCallback, $processed, $total);
            }
        }

        return $results;
    }

    /**
     * Get batch upload status.
     *
     * @param string $batchId
     * @return array|null
     */
    public static function getBatchStatus(string $batchId): ?array
    {
        // This would integrate with Laravel's batch system or custom tracking
        return \Illuminate\Support\Facades\Cache::get("mediaman_batch_{$batchId}");
    }
}
