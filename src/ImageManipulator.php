<?php

namespace FarhanShares\MediaMan;


use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Intervention\Image\ImageManager;
use FarhanShares\MediaMan\Exceptions\InvalidConversion;
use FarhanShares\MediaMan\Models\File;

class ImageManipulator
{
    /** @var ConversionRegistry */
    protected $conversionRegistry;

    /** @var ImageManager */
    protected $imageManager;

    /**
     * Create a new manipulator instance.
     *
     * @param ConversionRegistry $conversionRegistry
     * @param ImageManager $imageManager
     * @return void
     */
    public function __construct(ConversionRegistry $conversionRegistry, ImageManager $imageManager)
    {
        $this->conversionRegistry = $conversionRegistry;

        $this->imageManager = $imageManager;
    }

    /**
     * Perform the specified conversions on the given media item.
     *
     * @param File $file
     * @param array $conversions
     * @param bool $onlyIfMissing
     * @return void
     *
     * @throws InvalidConversion
     * @throws FileNotFoundException
     */
    public function manipulate(File $file, array $conversions, $onlyIfMissing = true)
    {
        if (!$file->isOfType('image')) {
            return;
        }

        foreach ($conversions as $conversion) {
            $path = $file->getPath($conversion);

            $filesystem = $file->filesystem();

            if ($onlyIfMissing && $filesystem->exists($path)) {
                continue;
            }

            $converter = $this->conversionRegistry->get($conversion);

            $image = $converter($this->imageManager->make(
                $filesystem->readStream($file->getPath())
            ));

            $filesystem->put($path, $image->stream());
        }
    }
}
