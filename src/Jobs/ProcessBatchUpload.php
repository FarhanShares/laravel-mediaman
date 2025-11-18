<?php

namespace FarhanShares\MediaMan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use FarhanShares\MediaMan\MediaUploaderPro;

class ProcessBatchUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $files;
    public string $batchId;
    public ?string $collection;
    public ?string $disk;
    public array $conversions;
    public array $metadata;

    public $tries = 1; // Batch uploads shouldn't retry
    public $timeout = 3600; // 1 hour timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $files,
        string $batchId,
        ?string $collection = null,
        ?string $disk = null,
        array $conversions = [],
        array $metadata = []
    ) {
        $this->files = $files;
        $this->batchId = $batchId;
        $this->collection = $collection;
        $this->disk = $disk;
        $this->conversions = $conversions;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $total = count($this->files);
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $results = [];

        $this->updateBatchStatus('processing', 0, $total);

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

                $results[] = [
                    'status' => 'success',
                    'media_id' => $media->id,
                    'file_name' => $media->file_name,
                ];

                $successful++;
            } catch (\Exception $e) {
                $results[] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'file' => is_object($file) && method_exists($file, 'getClientOriginalName')
                        ? $file->getClientOriginalName()
                        : 'unknown',
                ];

                $failed++;

                logger()->error('Batch upload file failed', [
                    'batch_id' => $this->batchId,
                    'error' => $e->getMessage(),
                ]);
            }

            $processed++;
            $this->updateBatchStatus('processing', $processed, $total, $successful, $failed);
        }

        $this->updateBatchStatus('completed', $total, $total, $successful, $failed, $results);
    }

    /**
     * Update batch status in cache.
     */
    protected function updateBatchStatus(
        string $status,
        int $processed,
        int $total,
        int $successful = 0,
        int $failed = 0,
        array $results = []
    ): void {
        Cache::put("mediaman_batch_{$this->batchId}", [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'progress' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'results' => $results,
            'updated_at' => now()->toIso8601String(),
        ], 3600); // Keep for 1 hour
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateBatchStatus('failed', 0, count($this->files), 0, count($this->files));

        logger()->error('Batch upload job failed', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);
    }
}
