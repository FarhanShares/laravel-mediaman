<?php

namespace FarhanShares\MediaMan\Monitoring;

use Illuminate\Support\Facades\Log;
use FarhanShares\MediaMan\Models\Media;

class MediaMonitor
{
    protected string $channel;
    protected bool $enabled;
    protected array $collectors = [];

    public function __construct()
    {
        $this->channel = config('mediaman.monitoring.log_channel', 'mediaman');
        $this->enabled = config('mediaman.monitoring.enabled', true);
        $this->loadCollectors();
    }

    /**
     * Load metric collectors.
     */
    protected function loadCollectors(): void
    {
        $collectorClasses = config('mediaman.monitoring.collectors', []);

        foreach ($collectorClasses as $collectorClass) {
            if (class_exists($collectorClass)) {
                $this->collectors[] = app($collectorClass);
            }
        }
    }

    /**
     * Log media upload event.
     *
     * @param Media $media
     * @param array $context
     * @return void
     */
    public function logUpload(Media $media, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'event' => 'media.uploaded',
            'media_id' => $media->id,
            'media_uuid' => $media->uuid ?? null,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'disk' => $media->disk,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];

        Log::channel($this->channel)->info('Media uploaded', $logData);

        $this->recordMetrics('upload', $media, $context);
    }

    /**
     * Log media deletion event.
     *
     * @param Media $media
     * @param array $context
     * @return void
     */
    public function logDelete(Media $media, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::channel($this->channel)->info('Media deleted', [
            'event' => 'media.deleted',
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);

        $this->recordMetrics('delete', $media, $context);
    }

    /**
     * Log conversion event.
     *
     * @param Media $media
     * @param array $conversions
     * @param float $duration
     * @return void
     */
    public function logConversion(Media $media, array $conversions, float $duration): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::channel($this->channel)->info('Media conversion completed', [
            'event' => 'media.conversion',
            'media_id' => $media->id,
            'conversions' => $conversions,
            'duration' => $duration,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->recordMetrics('conversion', $media, [
            'conversions' => $conversions,
            'duration' => $duration,
        ]);
    }

    /**
     * Log security event.
     *
     * @param string $type
     * @param array $context
     * @return void
     */
    public function logSecurityEvent(string $type, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::channel($this->channel)->warning('Security event', [
            'event' => "security.{$type}",
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log performance metric.
     *
     * @param string $operation
     * @param float $duration
     * @param array $context
     * @return void
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::channel($this->channel)->debug('Performance metric', [
            'event' => 'performance',
            'operation' => $operation,
            'duration' => $duration,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);

        $this->recordMetrics('performance', null, [
            'operation' => $operation,
            'duration' => $duration,
            ...$context,
        ]);
    }

    /**
     * Record metrics using collectors.
     *
     * @param string $event
     * @param Media|null $media
     * @param array $context
     * @return void
     */
    protected function recordMetrics(string $event, ?Media $media, array $context = []): void
    {
        foreach ($this->collectors as $collector) {
            if (method_exists($collector, 'record')) {
                $collector->record($event, $media, $context);
            }
        }
    }

    /**
     * Get storage statistics.
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        return [
            'total_files' => Media::count(),
            'total_size' => Media::sum('size'),
            'by_disk' => Media::selectRaw('disk, COUNT(*) as count, SUM(size) as size')
                ->groupBy('disk')
                ->get()
                ->toArray(),
            'by_mime_type' => Media::selectRaw('mime_type, COUNT(*) as count')
                ->groupBy('mime_type')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }
}
