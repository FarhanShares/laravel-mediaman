<?php

namespace FarhanShares\MediaMan;

use FarhanShares\MediaMan\Models\MediaCollection;
use Illuminate\Http\UploadedFile;

class MediaUploader
{
    /** @var UploadedFile */
    protected $file;

    /** @var string */
    protected $name;

    /** @var array */
    protected $collections = [];

    /** @var string */
    protected $fileName;

    /** @var string */
    protected $disk;

    /** @var string */
    protected $data = [];

    /**
     * Create a new uploader instance.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function __construct(UploadedFile $file)
    {
        $this->setFile($file);
    }

    /**
     * @param UploadedFile $file
     * @return MediaUploader
     */
    public static function source(UploadedFile $file)
    {
        return new static($file);
    }

    /**
     * Set the file to be uploaded.
     *
     * @param UploadedFile $file
     * @return MediaUploader
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;

        $fileName = $file->getClientOriginalName();
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        $this->setName($name);
        $this->setFileName($fileName);

        return $this;
    }

    /**
     * Set the name of the media item.
     *
     * @param string $name
     * @return MediaUploader
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return MediaUploader
     */
    public function useName(string $name)
    {
        return $this->setName($name);
    }

    /**
     * Set the name of the media item.
     *
     * @param string $name
     * @return MediaUploader
     */
    public function setCollection(string $name)
    {
        $this->collections[] = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return MediaUploader
     */
    public function useCollection(string $name)
    {
        return $this->setCollection($name);
    }

    /**
     * @param string $name
     * @return MediaUploader
     */
    public function toCollection(string $name)
    {
        return $this->setCollection($name);
    }

    /**
     * Set the name of the file.
     *
     * @param string $fileName
     * @return MediaUploader
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $this->sanitiseFileName($fileName);

        return $this;
    }

    /**
     * @param string $fileName
     * @return MediaUploader
     */
    public function useFileName(string $fileName)
    {
        return $this->setFileName($fileName);
    }

    /**
     * Sanitise the file name.
     *
     * @param string $fileName
     * @return string
     */
    protected function sanitiseFileName(string $fileName)
    {
        return str_replace(['#', '/', '\\', ' '], '-', $fileName);
    }

    /**
     * Specify the disk where the file will be stored.
     *
     * @param string $disk
     * @return MediaUploader
     */
    public function setDisk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @param string $disk
     * @return MediaUploader
     */
    public function toDisk(string $disk)
    {
        return $this->setDisk($disk);
    }

    /**
     * Set any custom data to be saved to the media item.
     *
     * @param array $data
     * @return MediaUploader
     */
    public function withData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function useData(array $data)
    {
        return $this->withData($data);
    }

    /**
     * Upload the file to the specified disk.
     *
     * @return mixed
     */
    public function upload()
    {
        $model = config('mediaman.models.media');

        $media = new $model();

        $media->name = $this->name;
        $media->file_name = $this->fileName;
        $media->disk = $this->disk ?: config('mediaman.disk');
        $media->mime_type = $this->file->getMimeType();
        $media->size = $this->file->getSize();
        $media->data = $this->data;

        $media->save();

        $media->filesystem()->putFileAs(
            $media->getDirectory(),
            $this->file,
            $this->fileName
        );

        if (count($this->collections) > 0) {
            // todo: support multiple collections
            $collection = MediaCollection::firstOrCreate([
                'name' => $this->collections[0]
            ]);

            $media->collections()->attach($collection->id);
        } else {
            // add to the default collection
            $media->collections()->attach(1);
        }

        // dump($collection);


        return $media->fresh();
    }
}
