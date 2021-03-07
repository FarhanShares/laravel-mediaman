<p align="center"><a href="https://farhanshares.com/projects/laravel-mediaman" target="_blank" title="Laravel MediaMan"><img src="https://raw.githubusercontent.com/FarhanShares/laravel-mediaman/main/docs/assets/mediaman-banner.png" /></a></p>

<p align="center">
<a href="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml"><img src="https://github.com/farhanshares/laravel-mediaman/actions/workflows/ci.yml/badge.svg" alt="Github CI"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/dt/farhanshares/laravel-mediaman" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/v/farhanshares/laravel-mediaman" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/farhanshares/laravel-mediaman"><img src="https://img.shields.io/packagist/l/farhanshares/laravel-mediaman" alt="License"></a>
</p>

# Laravel MediaMan</h1>
The most elegant & powerful media management package for Laravel!


## Installation

You can install the package via composer:

```bash
composer require farhanshares/laravel-mediaman
```

Once installed, you should publish the provided assets to create the necessary migration and config files.

```bash
php artisan vendor:publish --provider="FarhanShares\MediaMan\MediaManServiceProvider"
```

## Key concepts

There are a few key concepts that should be understood before continuing:

* Media: It can be any type of file. You should specify any file restrictions in your
  application's validation logic before you attempt to upload a file.

* MediaUploader: Items are uploaded as its own entity. It does not belong to any other model in the system when it's created, so items can be managed independently (which makes it the perfect engine for a media manager).

* Channel: Media items are bound to "channel". Thus you can easily associate multiple types of media to a model. For example, a model might have an "images" channel and a "documents" channel.

* Collection: A media item can be bundled to any number of "collection".

* Association: Media must be attached to a model for an association to be made.

* Conversion: You can manipulate images using conversions. You can specify conversions to be performed when a media item is associated to a model. For example, you can register a "thumbnail" conversion to run when images are attached to a
  model's "gallery" group.

* Conversions are registered globally. This means that they can be reused across your application, i.e a Post and a User can have the same sized thumbnail without having to register the same conversion twice.

### Project is in active development:

Docs will be updated over time, give it a star to support the project.
