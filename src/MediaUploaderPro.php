<?php

namespace FarhanShares\MediaMan;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Models\Media;
use FarhanShares\MediaMan\Contracts\Uploader;
use FarhanShares\MediaMan\Traits\InteractsWithConfig;
use FarhanShares\MediaMan\Traits\ValidatesMedia;
use FarhanShares\MediaMan\Traits\ProcessesMedia;
use FarhanShares\MediaMan\Security\SecurityManager;
use FarhanShares\MediaMan\Conversions\ConversionManager;

/**
 * MediaMan Pro - Enhanced media uploader with advanced features
 */
class MediaUploaderPro implements Uploader
{
    use InteractsWithConfig, ValidatesMedia, ProcessesMedia;

    protected $source;
    protected string $name = '';
    protected array $metadata = [];
    protected ?string $disk = null;
    protected array $collections = [];
    protected string $fileName = '';
    protected array $conversions = [];
    protected array $tags = [];
    protected bool $preserveOriginal = true;
    protected array $data = [];

    // Chunked upload support
    protected bool $useChunkedUpload = false;
    protected ?string $uploadSessionId = null;

    // AI features
    protected array $aiFeatures = [];

    // Security
    protected ?SecurityManager $securityManager = null;

    // Conversions
    protected ?ConversionManager $conversionManager = null;

    /**
     * Create uploader from various sources
     *
     * @param UploadedFile|string $source
     * @return self
     */
    public static function source($source): self
    {
        $instance = new self();

        if ($source instanceof UploadedFile) {
            $instance->source = $source;
            $instance->fileName = $source->getClientOriginalName();
            $instance->name = pathinfo($instance->fileName, PATHINFO_FILENAME);
        } elseif (is_string($source)) {
            $instance->handleStringSource($source);
        }

        return $instance;
    }

    /**
     * Set the name of the media item
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Alias for setName
     *
     * @param string $name
     * @return self
     */
    public function useName(string $name): self
    {
        return $this->setName($name);
    }

    /**
     * Set the collection for the media
     *
     * @param string $name
     * @return self
     */
    public function setCollection(string $name): self
    {
        $this->collections[] = $name;
        return $this;
    }

    /**
     * Aliases for setCollection
     */
    public function useCollection(string $name): self
    {
        return $this->setCollection($name);
    }

    public function toCollection(string $name): self
    {
        return $this->setCollection($name);
    }

    /**
     * Set the disk for storage
     *
     * @param string $disk
     * @return self
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Aliases for setDisk
     */
    public function useDisk(string $disk): self
    {
        return $this->setDisk($disk);
    }

    public function toDisk(string $disk): self
    {
        return $this->setDisk($disk);
    }

    /**
     * Set the file name
     *
     * @param string $fileName
     * @return self
     */
    public function setFileName(string $fileName): self
    {
        if (config('mediaman.sanitize_filenames')) {
            $fileName = $this->sanitizeFilename($fileName);
        }

        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Alias for setFileName
     */
    public function useFileName(string $fileName): self
    {
        return $this->setFileName($fileName);
    }

    /**
     * Set custom metadata
     *
     * @param array $data
     * @return self
     */
    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Alias for withData
     */
    public function useData(array $data): self
    {
        return $this->withData($data);
    }

    /**
     * Chain multiple conversions
     *
     * @param array $conversions
     * @return self
     */
    public function withConversions(array $conversions): self
    {
        $this->conversions = array_merge($this->conversions, $conversions);
        return $this;
    }

    /**
     * Set tags for better organization
     *
     * @param array|string $tags
     * @return self
     */
    public function withTags($tags): self
    {
        $this->tags = is_array($tags) ? $tags : [$tags];
        return $this;
    }

    /**
     * Enable AI processing
     *
     * @param array $features
     * @return self
     */
    public function withAI(array $features = ['auto_tag', 'extract_text']): self
    {
        if (!config('mediaman.ai.enabled')) {
            return $this;
        }

        $this->aiFeatures = $features;
        $this->metadata['ai_features'] = $features;
        return $this;
    }

    /**
     * Enable chunked upload
     *
     * @param string|null $sessionId
     * @return self
     */
    public function useChunkedUpload(?string $sessionId = null): self
    {
        $this->useChunkedUpload = true;
        $this->uploadSessionId = $sessionId ?? Str::uuid();
        return $this;
    }

    /**
     * Upload the file
     *
     * @return Media
     */
    public function upload(): Media
    {
        return $this->uploadWithProgress();
    }

    /**
     * Upload with progress callback
     *
     * @param callable|null $callback
     * @return Media
     */
    public function uploadWithProgress(?callable $callback = null): Media
    {
        // Validate source
        $this->validateSource();

        // Security checks
        if ($this->securityManager && config('mediaman.virus_scan')) {
            $this->securityManager->scanForVirus($this->source);
        }

        // Prepare metadata
        $this->prepareMetadata();

        // Perform upload
        if ($this->useChunkedUpload) {
            $media = $this->performChunkedUpload($callback);
        } else {
            $media = $this->performUpload($callback);
        }

        // Process conversions
        if (!empty($this->conversions) && $this->conversionManager) {
            if ($this->shouldQueueConversions()) {
                $this->conversionManager->processInQueue($media, $this->conversions);
            } else {
                $this->conversionManager->process($media, $this->conversions);
            }
        }

        // Process AI features
        if (!empty($this->aiFeatures)) {
            $this->processAI($media);
        }

        return $media;
    }

    /**
     * Process AI features
     *
     * @param Media $media
     * @return void
     */
    protected function processAI(Media $media): void
    {
        // TODO: Implement AI processing pipeline
        // This would integrate with services like:
        // - Auto-tagging: AWS Rekognition, Google Vision
        // - Text extraction: Tesseract OCR
        // - Alt text generation: GPT-4 Vision

        $aiData = [];

        foreach ($this->aiFeatures as $feature) {
            switch ($feature) {
                case 'auto_tag':
                    // $aiData['tags'] = $this->generateTags($media);
                    break;
                case 'extract_text':
                    // $aiData['extracted_text'] = $this->extractText($media);
                    break;
                case 'generate_alt':
                    // $aiData['alt_text'] = $this->generateAltText($media);
                    break;
            }
        }

        if (!empty($aiData)) {
            $data = $media->data ?? [];
            $data['ai'] = $aiData;
            $media->data = $data;
            $media->save();
        }
    }

    /**
     * Set security manager
     *
     * @param SecurityManager $manager
     * @return self
     */
    public function setSecurityManager(SecurityManager $manager): self
    {
        $this->securityManager = $manager;
        return $this;
    }

    /**
     * Set conversion manager
     *
     * @param ConversionManager $manager
     * @return self
     */
    public function setConversionManager(ConversionManager $manager): self
    {
        $this->conversionManager = $manager;
        return $this;
    }
}
