<?php

namespace FarhanShares\MediaMan\Listeners;

use FarhanShares\MediaMan\Events\MediaUploaded;
use FarhanShares\MediaMan\Monitoring\MediaMonitor;

class LogMediaUpload
{
    protected MediaMonitor $monitor;

    /**
     * Create the event listener.
     */
    public function __construct(MediaMonitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * Handle the event.
     */
    public function handle(MediaUploaded $event): void
    {
        $this->monitor->logUpload($event->media, $event->context);
    }
}
