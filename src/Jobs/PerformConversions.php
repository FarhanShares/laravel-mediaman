<?php

namespace FarhanShares\MediaMan\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use FarhanShares\MediaMan\ImageManipulator;
use FarhanShares\MediaMan\Models\File;

class PerformConversions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var File */
    protected $file;

    /** @var array */
    protected $conversions;

    /**
     * Create a new job instance.
     *
     * @param File $file
     * @param array $conversions
     * @return void
     */
    public function __construct(File $file, array $conversions)
    {
        $this->file = $file;

        $this->conversions = $conversions;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(ImageManipulator::class)->manipulate(
            $this->file,
            $this->conversions
        );
    }

    /** @return File */
    public function getFile()
    {
        return $this->file;
    }

    /** @return array */
    public function getConversions()
    {
        return $this->conversions;
    }
}
