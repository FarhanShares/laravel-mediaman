<?php

namespace FarhanShares\MediaMan\Conversions;

use Intervention\Image\ImageManager;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Contracts\ConversionProcessor;
use FarhanShares\MediaMan\Jobs\ProcessConversions;

class ConversionManager implements ConversionProcessor
{
    protected ImageManager $imageManager;
    protected array $conversions = [];

    /**
     * Create a new conversion manager instance.
     *
     * @param ImageManager $imageManager
     */
    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * Register a conversion
     *
     * @param string $name
     * @param callable $conversion
     * @return self
     */
    public function register(string $name, callable $conversion): self
    {
        $this->conversions[$name] = $conversion;
        return $this;
    }

    /**
     * Process conversions for media
     *
     * @param Media $media
     * @param array $conversions
     * @return void
     */
    public function process(Media $media, array $conversions): void
    {
        foreach ($conversions as $conversion) {
            if (isset($this->conversions[$conversion])) {
                $this->executeConversion($media, $conversion);
            }
        }
    }

    /**
     * Process conversions in queue
     *
     * @param Media $media
     * @param array $conversions
     * @return void
     */
    public function processInQueue(Media $media, array $conversions): void
    {
        dispatch(new ProcessConversions($media, $conversions))
            ->onQueue(config('mediaman.conversion_queue', 'default'));
    }

    /**
     * Register responsive image conversion
     *
     * @return self
     */
    public function registerResponsive(): self
    {
        $this->register('responsive', function ($image, Media $media) {
            $sizes = config('mediaman.conversions.responsive_breakpoints', [320, 640, 768, 1024, 1920]);
            $variants = [];

            foreach ($sizes as $size) {
                $variant = clone $image;
                $variant->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $path = $media->getPath("responsive-w{$size}");
                $media->filesystem()->put($path, $variant->encode());

                $variants["w{$size}"] = $path;
            }

            return $variants;
        });

        return $this;
    }

    /**
     * Register WebP conversion
     *
     * @return self
     */
    public function registerWebP(): self
    {
        $this->register('webp', function ($image, Media $media) {
            $quality = config('mediaman.conversions.webp_quality', 90);
            $webp = $image->encode('webp', $quality);

            $path = str_replace(
                '.' . pathinfo($media->file_name, PATHINFO_EXTENSION),
                '.webp',
                $media->getPath()
            );

            $media->filesystem()->put($path, $webp);

            return $path;
        });

        return $this;
    }

    /**
     * Register blur hash for lazy loading
     *
     * @return self
     */
    public function registerBlurHash(): self
    {
        $this->register('blurhash', function ($image, Media $media) {
            $thumbnail = clone $image;
            $thumbnail->resize(20, 20);
            $thumbnail->blur(5);

            $path = $media->getPath('blurhash');
            $media->filesystem()->put($path, $thumbnail->encode('data-url'));

            // TODO: Generate actual BlurHash string
            // For now, return base64 thumbnail
            return [
                'path' => $path,
                'data_url' => (string) $thumbnail->encode('data-url'),
            ];
        });

        return $this;
    }

    /**
     * Register watermark conversion
     *
     * @param string|null $watermarkPath
     * @return self
     */
    public function registerWatermark(?string $watermarkPath = null): self
    {
        $watermarkPath = $watermarkPath ?? config('mediaman.conversions.watermark_path');

        if (!$watermarkPath || !file_exists($watermarkPath)) {
            return $this;
        }

        $this->register('watermark', function ($image, Media $media) use ($watermarkPath) {
            $watermark = $this->imageManager->make($watermarkPath);

            // Resize watermark to 10% of image height
            $watermark->resize(null, $image->height() * 0.1, function ($constraint) {
                $constraint->aspectRatio();
            });

            $position = config('mediaman.conversions.watermark_position', 'bottom-right');
            $image->insert($watermark, $position, 10, 10);

            $path = $media->getPath('watermark');
            $media->filesystem()->put($path, $image->encode());

            return $path;
        });

        return $this;
    }

    /**
     * Register smart crop with center focus
     *
     * @return self
     */
    public function registerSmartCrop(): self
    {
        $this->register('smart_crop', function ($image, Media $media) {
            // TODO: Implement face detection
            // For now, use center crop
            $image->fit(500, 500, function ($constraint) {
                $constraint->aspectRatio();
            });

            $path = $media->getPath('crop');
            $media->filesystem()->put($path, $image->encode());

            return $path;
        });

        return $this;
    }

    /**
     * Register thumbnail conversion
     *
     * @return self
     */
    public function registerThumbnail(): self
    {
        $this->register('thumbnail', function ($image, Media $media) {
            $size = config('mediaman.conversions.thumbnail_size', [150, 150]);

            $image->fit($size[0], $size[1]);

            $path = $media->getPath('thumbnail');
            $media->filesystem()->put($path, $image->encode());

            return $path;
        });

        return $this;
    }

    /**
     * Execute a specific conversion
     *
     * @param Media $media
     * @param string $conversion
     * @return mixed
     */
    protected function executeConversion(Media $media, string $conversion)
    {
        if (!$media->isOfType('image')) {
            return null;
        }

        $converter = $this->conversions[$conversion];
        $image = $this->imageManager->make(
            $media->filesystem()->readStream($media->getPath())
        );

        return $converter($image, $media);
    }

    /**
     * Get all registered conversions
     *
     * @return array
     */
    public function all(): array
    {
        return array_keys($this->conversions);
    }

    /**
     * Check if conversion exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return isset($this->conversions[$name]);
    }
}
