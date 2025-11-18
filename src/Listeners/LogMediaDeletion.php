<?php

namespace FarhanShares\MediaMan\Listeners;

use FarhanShares\MediaMan\Events\MediaDeleted;
use FarhanShares\MediaMan\Monitoring\MediaMonitor;

class LogMediaDeletion
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
    public function handle(MediaDeleted $event): void
    {
        $this->monitor->logDelete($event->media);
    }
}
