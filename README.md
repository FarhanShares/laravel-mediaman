<p align="center"><a href="https://farhanshares.com/projects/laravel-mediaman" target="_blank" title="Laravel MediaMan"><img src="https://raw.githubusercontent.com/FarhanShares/laravel-mediaman/main/docs/assets/mediaman-banner.png" /></a></p>

<p align="center">
<a href="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml"><img src="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml/badge.svg" alt="Github CI"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/dt/farhanshares/laravel-mediaman" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/v/farhanshares/laravel-mediaman" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/l/farhanshares/laravel-mediaman" alt="License"></a>
</p>

# Laravel MediaMan</h1>
The most elegant & powerful media management package for Laravel!




## In a hurry? Here's a quick example:

```php
$media = MediaUploader::source($request->file->('file'))
            ->useCollection('Posts')
            ->upload();

$post = Post::find(1);
$post->attachMedia($media, 'featured-image');
```



## Overview & Key concepts

There are a few key concepts that need to be understood before continuing:

* **Media**: It can be any type of file. You should specify file restrictions in your application's validation logic before you attempt to upload a file.

* **MediaUploader**: Media items are uploaded as its own entity. It does not belong to any other model in the system when it's being created, so items can be managed independently (which makes it the perfect engine for a media manager). MediaMan provides "MediaUploader" for creating records in the database & storing in the filesystem as well.

* **MediaCollection**: Media items can be bundled to any "collection". Media & Collections will form many-to-many relation. You can use it to create groups of media without really associating media to your App Models.

* **Association**: Media need be attached to a model for an association to be made. MediaMan exposes helpers to easily get the job done. Many-to-many polymorphic relationships allow any number of media to be associated to any number of other models without the need of modifying their schema.

* **Channel**: Media items are bound to a "channel" of a model during association. Thus you can easily associate multiple types of media to a model. For example, a "User" model might have an "avatar" and a "documents" media channel.

* **Conversion**: You can manipulate images using conversions, conversions will be performed when a media item (image) is associated to a model. For example, you can register a "thumbnail" conversion to run when images are attached to the "gallery" channel of a model.




## Table of Contents
  * [Installation](#installation)
  * [Configuration](#configuration)
  * [Media](#media)
  * [Media & Models](#media--models)
  * [Collections](#collections)
  * [Media & Collections](#media--collections)
  * [Media, Models & Conversions](#conversions)



## Installation

You can install the package via composer:

```bash
composer require farhanshares/laravel-mediaman
```
The package should be auto discovered by Laravel unless you've disabled auto-discovery mode. In that case, add the service provider to your config/app.php file:

`FarhanShares\MediaMan\MediaManServiceProvider::class`

Once installed, you should publish the provided assets to create the necessary migration and config files.

```bash
php artisan vendor:publish --provider="FarhanShares\MediaMan\MediaManServiceProvider"
```



## Configuration
MediaMan works out of the box. If you want to tweak it, MediaMan ships with a config/mediaman.php. One common need of tweaking could be to store media in a dedicated Storage.

MediaMan supports all of the storage drivers that are supported by Laravel. For i.e. Let's configure a local media disk for MediaMan.

```php
// file: config/filesystems.php
// add the lines in the disks array
'media' => [
    'driver' => 'local',
    'root' => storage_path('app/media'),
    'url' => env('APP_URL') . '/media',
    'visibility' => 'public',
  ],


// file: config/mediaman.php
// update the disk config to use our recently created media disk
'disk' => 'media'
```
Now, run `php artisan storage:link` to create the symbolic link of our newly created media disk.


## Media
### Upload media
You should use the `FarhanShares\MediaMan\MediaUploader` class to handle file uploads. You can upload, create a record in the database & store the file in the filesystem in one go.

```php
$file  = $request->file('file')
$media = MediaUploader::source($file)->upload();
```
The file will be stored in the default disk & bundled in the default collection specified in the mediaman config. The file size will be stored in the database & the file name will be sanitized automatically.

However, you can do a lot more, not just stick to the defaults.
```php
$file  = $request->file('file')
$media = MediaUploader::source($file)
            ->useName('Custom name')
            ->useFileName('custom-name.png')
            ->useCollection('Images')
            ->useDisk('media')
            ->useData([
                'additional_data' => 'will be stored as json',
                'use_associative_array' => 'to store any data you want to be with the file',
            ])
            ->upload();
```
If the collection doesn't exist, it'll be created on the fly. You can read more about collections below.

**Q: What happens if I don't provide a unique file name in the above process?**

A: Don't worry, MediaMan manages uploading in a smart & safe way. Files are stored in the disk in a way that conflicts are barely going to happen. When storing in the disk, MediaMan will create a directory in the disk with a format of: `mediaId-hash` & put the file inside of it. Anything related to the file will have it's own little house.

**Q: But why? Won't I get a bunch of directories?**

A: Yes, you'll. If you want, extend the `FarhanShares\MediaMan\Models\Media` model & you can customize however you like. Finally point your customized model in the mediaman config. But we recommend sticking to the default, thus you don't need to worry about file conflicts. A hash is added along with the mediaId, hence users won't be able to guess & retrieve a random file. More on customization will be added later.

**Reminder: MediaMan treats any file (instance of `Illuminate\Http\UploadedFile`) as a media source. If you want a certain file types can be uploaded, you can use Laravel's validator.**




### Retrieve media
You can use any Eloquent operation to retrieve a media plus we've added findByName().

```php
// by id
$media = Media::find(1);

// by name
$media = Media::findByName('media-name');

// with collections
$media = Media::with('collections')->find(1);
```

An instance of Media has the following attributes:

```php
'id' => int
'name' => string
'file_name' => string
'extension' => string
'type' => string
'mime_type' => string
'size' =>  int // in bytes
'friendly_size' => string // in human readable format
'media_url' =>  string // original media url
'disk' =>  string
'data' => array // casts as array
'created_at' =>  string
'updated_at' => string
'collections' => object // eloquent collection
```

Once you have the media instance, you can also retrieve the converted media URLs:
```php
// by id
$media = Media::find(1);
// original media url
$media->getUrl()
// converted media url
$media->getUrl('conversion-name');
```

### Update media
You can update a media name with an instance of Media.

```php
$media = Media::first();
$media->name = 'New name';
$media->data = ['additional_data' => 'new additional data']
$media->save()
```

**Note:** Support for updating file name & disk will be added soon.

**Heads Up!** Do not update anything other than `name` & `data` using the Media instance. If you need to deal with collections, please read the docs below.




### Delete media
You can delete a media by calling delete() method on an instance of Media.

```php
$media = Media::first();
$media->delete()
```

Or you delete media like this:
```php
Media::destroy(1);
Media::destroy([1, 2, 3]);
```

**Note:** When a Media instance gets deleted, file will be removed from the filesystem, all the association with your App Models & MediaCollection will be removed as well.

**Heads Up!:** You should not delete media using queries, e.g. `Media::where('name', 'the-file')->delete()`, this will not trigger deleted event & the file won't be deleted from the filesystem. Read more about it in the [official documentation](https://laravel.com/docs/master/eloquent#deleting-models-using-queries).


-----
## Media & Models

### Associate media
MediaMan exposes easy to use API via `FarhanShares\MediaMan\HasMedia` trait for associating media items to models. Use the trait in your App Model & you are good to go.

```php
use Illuminate\Database\Eloquent\Model;
use FarhanShares\MediaMan\Traits\HasMedia;

class Post extends Model
{
    use HasMedia;
}
```
This will establish the relationship between your App Model and the Media Model.

Once done, you can associate media to the model as demonstrated below.

The first parameter of the `attachMedia()` method can either be a media model / id or an iterable collection of models / ids.

```php
$post = Post::first();

// Associate in the default channel
$post->attachMedia($media); // or 1 or [1, 2, 3] or collection of media models

// Associate in a custom channel
$post->attachMedia($media, 'featured-image');
```

`attachMedia()` returns number of media attached (int) on success & null on failure.

### Retrieve media of a model
Apart from that, `HasMedia` trait enables your App Models retrieving media conveniently.

```php
// All media from the default channel
$post->getMedia();

// All media from the specified channel
$post->getMedia('featured-image');
```

It might be a common scenario for most of the Laravel apps to use the first media item more often, hence MediaMan has dedicated methods to retrieve the first item among all associated media.

```php
// First media item from the default channel
$post->getFirstMedia();

// First media item from the specified channel
$post->getFirstMedia('featured-image');

// URL of the first media item from the default channel
$post->getFirstMediaUrl();

// URL of the first media item from the specified channel
$post->getFirstMediaUrl('featured-image');
```

### Disassociate media
You can use `detachMedia()` method which is also shipped with HasMedia trait to disassociate media from model.

```php
// Detach the specified media
$post->detachMedia($media); // or 1 or [1, 2, 3] or collection of media models

// Detach all media from all channels
$post->detachMedia();

// Detach all media of the default channel
$post->clearMediaChannel();

// Detach all media of the specific channel
$post->clearMediaChannel('channel-name');
```

`detachMedia()` returns number of media detached (int) on success & null on failure.

### Synchronize association / disassociation
This feature will be added soon.


-----
## Collections
MediaMan provides collections to bundle your media for better media management. Use `FarhanShares\MediaMan\Models\MediaCollection` to deal with media collections.
### Create collection
Collections are created on thy fly if it doesn't exist while uploading file.
```php
$media = MediaUploader::source($request->file('file'))
            ->useCollection('My Collection')
            ->upload();
```

If you wish to create collection without uploading a file, you can do it, after all, it's an Eloquent model.

```php
MediaCollection::create(['name' => 'My Collection']);
```
### Retrieve collection
You can retrieve a collection by it's id or name.
```php
MediaCollection::find(1);
MediaCollection::findByName('My Collection');

// Retrieve the bound media as well
MediaCollection::with('media')->find(1);
MediaCollection::with('media')->findByName('My Collection');
```
### Update collection
You can update a collection name. It doesn't really have any other things to update.
```php
$collection = MediaCollection::findByName('My Collection');
$collection->name = 'Our Collection'
$collection->save();
```

### Delete collection
You can delete a collection using an instance of MediaCollection.
```php
$collection = MediaCollection::find(1);
$collection->delete()
```
This won't delete the media from disk but the bindings will be removed from database.

*Heads Up!* deleteWithMedia() is a conceptual method that hasn't implemented yet, create a feature request if you need this. PRs are very much appreciated.

------
## Media & Collections
The relationship between `Media` & `MediaCollection` are already configured. You can bind, unbind & sync binding & unbinding easily. The method signatures are similar for `Media::**Collections()` and `MediaCollection::**Media()`.

### Bind media
```php
$collection = MediaCollection::first();
// You can just pass a media model / id / name or an iterable collection of those
// e.g. 1 or [1, 2] or $media or [$mediaOne, $mediaTwo] or 'media-name' or ['media-name', 'another-media-name']
$collection->attachMedia($media);
```

`attachMedia()` returns number of media attached (int) on success & null on failure. Alternatively, you can use `Media::attachCollections()` to bind to collections from a media model instance.

*Heads Up!* Unlike `HasMedia` trait, you can not have channels on media collections.
### Unbind media
```php
$collection = MediaCollection::first();
// You can just pass a media model / id / name or an iterable collection of those
// e.g. 1 or [1, 2] or $media or [$mediaOne, $mediaTwo] or 'media-name' or ['media-name', 'another-media-name']
$collection->detachMedia($media);

// Detach all media by passing null / bool / empty-string / empty-array
$collection->detachMedia([]);
```
`detachMedia()` returns number of media detached (int) on success & null on failure. Alternatively, you can use `Media::detachCollections()` to unbind from collections from a media model instance.

### Synchronize binding & unbinding

```php
$collection = MediaCollection::first();
// You can just pass media model / id / name
$collection->syncMedia($media);

// You can even pass iterable list / collection
$collection->syncMedia(Media::all())
$collection->syncMedia([1, 2, 3, 4, 5]);
$collection->syncMedia([$mediaSix, $mediaSeven]);
$collection->syncMedia(['media-name', 'another-media-name']);

// Synchronize to having zero media by passing null / bool / empty-string / empty-array
$collection->syncMedia([]);
```
`syncMedia()` always returns an array containing synchronization status. Alternatively, you can use `Media::syncCollections()` to sync with collections from a media model instance.



## Conversions
You can specify a model to perform "conversions" when a media is attached to a channel.

MediaMan provides a fluent api to manipulate images. It uses the popular [intervention/image](https://github.com/Intervention/image) library under the hood. Resizing, adding watermark, converting to a different format or anything that is supported can be done. In short, You can utilize all functionalities from the library.

Conversions are registered globally. This means that they can be reused across your application, for i.e a Post and a User both can have the same sized thumbnail without having to register the same conversion twice.

To get started, you should first register a conversion in one of your application's service providers:

```php
use Intervention\Image\Image;
use FarhanShares\MediaMan\Facades\Conversion;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Conversion::register('thumb', function (Image $image) {
            // you have access to intervention/image library,
            // perform your desired conversions here
            return $image->fit(64, 64);
        });
    }
}
```

Once you've registered a conversion, you should configure a media channel to perform the conversion when media is attached to your model.

```php
class Post extends Model
{
    use HasMedia;

    public function registerMediaChannels()
    {
        $this->addMediaChannel('gallery')
             ->performConversions('thumb');
    }
}
```

From now on, whenever a media item is attached to the "gallery" channel, a converted image will be generated. You can get the url of the converted image as demonstrated below:

```php
// The 'thumb' conversion URL of the first image from the 'gallery' channel
$post->getFirstMediaUrl('gallery', 'thumb');
```

```php
// if you have multiple media associated & need to retrieve URLs you can do it:
$media = $post->getMedia();
// getUrl() accepts two arguments: name of the channel & name of the conversion
$mediaOneThumb = $media[0]->getUrl('gallery', 'thumb');
```


## License
The MIT License (MIT). Please read [License File](LICENSE.md) for more information.