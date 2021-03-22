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

## Installation

You can install the package via composer:

```bash
composer require farhanshares/laravel-mediaman
```

Once installed, you should publish the provided assets to create the necessary migration and config files.

```bash
php artisan vendor:publish --provider="FarhanShares\MediaMan\MediaManServiceProvider"
```

## Overview & Key concepts

There are a few key concepts that need to be understood before continuing:

* **Media**: It can be any type of file. You should specify file restrictions in your application's validation logic before you attempt to upload a file.

* **MediaUploader**: Media items are uploaded as its own entity. It does not belong to any other model in the system when it's being created, so items can be managed independently (which makes it the perfect engine for a media manager). MediaMan provides "MediaUploader" for creating records in the database & storing in the filesystem as well.

* **MediaCollection**: Media items can be bundled to any "collection". Media & Collections will form many-to-many relation. You can use it to create groups of media without really associating media to your app models.

* **Association**: Media need be attached to a model for an association to be made. MediaMan exposes helpers to easily get the job done. Many-to-many polymorphic relationships allow any number of media to be associated to any number of other models without the need of modifying their schema.

* **Channel**: Media items are bound to a "channel" of a model during association. Thus you can easily associate multiple types of media to a model. For example, a "User" model might have an "avatar" and a "documents" media channel.

* **Conversion**: You can manipulate images using conversions, conversions will be performed when a media item (image) is associated to a model. For example, you can register a "thumbnail" conversion to run when images are attached to the "gallery" channel of a model.


# Usage


## Upload media
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

A: Yes, you'll. If you want, extend the `FarhanShares\MediaMan\Models\Media` model & you can customize however you like. Finally point your customized model in the mediaman config. But we recommend sticking to the default, thus you don't need to worry about file conflicts. A hash is added along with the mediaId, thus users won't be able to guess & retrieve a random file. More on customization will be added later.

**Reminder: MediaMan treats any file (instance of `Illuminate\Http\UploadedFile`) as a media source. If you want a certain file types can be uploaded, you can use Laravel's validator.**
## Associate media
MediaMan exposes easy to use API via `FarhanShares\MediaMan\HasMedia` trait for associating media items to models. Use the trait in your app model & you are good to go.

```php
use Illuminate\Database\Eloquent\Model;
use FarhanShares\MediaMan\Traits\HasMedia;

class Post extends Model
{
    use HasMedia;
}
```
This will establish the relationship between your model and the media model.

Once included, you can attach media to the model as demonstrated below. The first parameter of the attach media method can either be a media model instance, an id, or an iterable list of models / ids.

```php
$post = Post::first();

// To the default channel
$post->attachMedia($media);

// To a custom channel
$post->attachMedia($media, 'featured-image');
```



## Disassociate media


## Retrieve media
## Update media

## Delete media

-----
## Retrieve collection

## Update collection
## Delete collection


------
## Collections & Media

-----
# Conversions

Conversions are registered globally. This means that they can be reused across your application, i.e a Post and a User can have the same sized thumbnail without having to register the same conversion twice.