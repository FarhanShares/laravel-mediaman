<?php

namespace FarhanShares\MediaMan\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MediamanPublishMigrationCommand extends Command
{
    protected $signature = 'mediaman:publish-migration';

    protected $description = 'Publish Mediaman Migration';

    public function handle()
    {
        $this->publishMigration();
    }

    protected function publishMigration()
    {
        $existingMigration = $this->getExistingMigration();
        if ($existingMigration) {
            $this->info('Found mediaman migration: ' . $existingMigration);
            if (!$this->confirm('The mediaman migration file already exists. Do you want to overwrite it?')) {
                $this->info('Config file was not overwritten.');
                return;
            }
        }

        $sourcePath = __DIR__ . '/../../../database/migrations/create_mediaman_tables.php.stub';
        $targetPath = $existingMigration ?: database_path('migrations/' . date('Y_m_d_His', time()) . '_create_mediaman_tables.php');
        File::copy($sourcePath, $targetPath);

        $relativePath = str_replace(base_path() . '/', '', $targetPath);
        $this->info('Migration published to: ' . $relativePath);
    }

    protected function getExistingMigration()
    {
        $allFiles = File::files(database_path('migrations'));
        foreach ($allFiles as $file) {
            if (str_ends_with($file->getFilename(), '_create_mediaman_tables.php')) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
