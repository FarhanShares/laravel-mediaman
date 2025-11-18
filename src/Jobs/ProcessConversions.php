<?php

namespace FarhanShares\MediaMan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Conversions\ConversionManager;

class ProcessConversions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The media instance.
     *
     * @var Media
     */
    public $media;

    /**
     * The conversions to process.
     *
     * @var array
     */
    public $conversions;

    /**
     * Number of times to retry the job.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout for the job.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Media $media
     * @param array $conversions
     * @return void
     */
    public function __construct(Media $media, array $conversions)
    {
        $this->media = $media;
        $this->conversions = $conversions;
    }

    /**
     * Execute the job.
     *
     * @param ConversionManager $conversionManager
     * @return void
     */
    public function handle(ConversionManager $conversionManager)
    {
        $conversionManager->process($this->media, $this->conversions);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        logger()->error('Conversion processing failed', [
            'media_id' => $this->media->id,
            'conversions' => $this->conversions,
            'error' => $exception->getMessage(),
        ]);
    }
}
