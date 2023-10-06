<?php

namespace FarhanShares\MediaMan\Console\Commands;

use Illuminate\Http\File;
use Illuminate\Console\Command;

class MediamanPublishCommand extends Command
{
    protected $signature = 'mediaman:publish {--config : Publish the config file}';

    protected $description = 'Publish Mediaman assets';

    public function handle()
    {
        if ($this->option('config') || $this->confirm('Do you wish to publish the config file?')) {
            $this->publishConfig();
        }
    }

    protected function publishConfig()
    {
        $destinationConfigPath = config_path('mediaman.php');
        $sourceConfigPath = __DIR__ . '../../../config/mediaman.php';

        if (File::exists($destinationConfigPath)) {
            if (!$this->confirm('The mediaman config file already exists. Do you want to overwrite it?')) {
                $this->info('Config file was not overwritten.');
                return;
            }
        }

        File::copy($sourceConfigPath, $destinationConfigPath);
        $this->info('Published config file.');
    }
}
