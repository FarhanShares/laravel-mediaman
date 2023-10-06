<?php

namespace FarhanShares\MediaMan\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MediamanPublishCommand extends Command
{
    protected $signature = 'mediaman:publish-config';

    protected $description = 'Publish Mediaman Config';

    public function handle()
    {
        $this->publishConfig();
    }

    protected function publishConfig()
    {
        $destinationConfigPath = config_path('mediaman.php');
        $sourceConfigPath = __DIR__ . '/../../../config/mediaman.php';

        if (File::exists($destinationConfigPath)) {
            if (!$this->confirm('The mediaman config file already exists. Do you want to overwrite it?')) {
                $this->info('Config file was not overwritten.');
                return;
            }
        }

        File::copy($sourceConfigPath, $destinationConfigPath);
        $this->info('Published mediaman config file.');
    }
}
