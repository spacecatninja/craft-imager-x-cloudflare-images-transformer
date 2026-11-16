# Upgrading to the Cloudflare Images transformer 2 (Craft 6)

This guide lists what changed in version 2 of the Cloudflare Images transformer for Imager X, and what you need to update in your project and custom code. It applies when upgrading from version 1 (Craft 5, and Imager X 5 or 6).

Templates that transform with Cloudflare Images through Imager X (`craft.imagerx.transformImage()` with the `cloudflareimages` transformer) aren't affected, and give the same URLs as version 1.0.2, apart from the fixes listed in the changelog. Projects need to move and convert the config file. The class changes only affect custom code that uses the plugin's classes directly.

## Requirements

- Craft CMS 6.0.0 or later
- Imager X 7.0.0-beta.2 or later, Pro edition
- PHP 8.5 or later
- The plugin doesn't need the Yii 2 adapter (`craftcms/yii2-adapter`).

## Upgrade steps

1. Upgrade your project to Craft 6, and Imager X to version 7, following their upgrade guides.
2. Update the plugin: `composer require spacecatninja/imager-x-cloudflare-images-transformer:^2.0.0`.
3. Move your config file to `config/craft/imager-x-cloudflare-images-transformer.php`, and convert it (see below).
4. Review the breaking changes below, and update your code.

## Breaking changes

### The config file

Craft 6 reads plugin config files from `config/craft/`, as plain Laravel config. Move `config/imager-x-cloudflare-images-transformer.php` to `config/craft/imager-x-cloudflare-images-transformer.php`, return a flat array instead of the multi-environment `'*'` format, and use `env()` instead of `craft\helpers\App::env()`. The settings themselves are unchanged.

Before:

```php
use craft\helpers\App;

return [
    '*' => [
        'zoneDomain' => 'example.com',
        'autoGravityWhenNoFocalPoint' => true,
        'defaultParams' => [
            'format' => 'auto',
        ],
    ],
    'staging' => [
        'zoneDomain' => App::env('CLOUDFLARE_IMAGES_ZONE_DOMAIN'),
    ],
];
```

After:

```php
return [
    'zoneDomain' => env('CLOUDFLARE_IMAGES_ZONE_DOMAIN', 'example.com'),
    'autoGravityWhenNoFocalPoint' => true,
    'defaultParams' => [
        'format' => 'auto',
    ],
];
```

Config files are loaded before Laravel knows the environment, so use `env()` for per-environment values, not `app()->environment()`. For example: `'zoneDomain' => env('APP_ENV') === 'production' ? 'example.com' : 'staging.example.com'`.

### Crop and stretch modes

Two modes now use different Cloudflare `fit` values, so their URLs change, and Cloudflare transforms the images again:

- Mode `crop` (the default) uses `fit=crop` when `allowUpscale` is off, instead of `fit=cover`. Cloudflare's `crop` fills the area like `cover`, but never enlarges the image, so an image smaller than the requested size comes out smaller, and the transformed image's width and height say so. With `allowUpscale` on (Imager X's default), crops still use `fit=cover`.
- Mode `stretch` uses `fit=squeeze`, which stretches the image to the exact size, like the `craft` transformer does. It used `fit=cover`, which cropped the image instead.

### Namespace and classes

The plugin moved from `spacecatninja\cloudflareimagestransformer` to the `SpaceCatNinja\CloudflareImagesTransformer` namespace, with Laravel-style folders.

| Before | After |
| --- | --- |
| `spacecatninja\cloudflareimagestransformer\CloudflareImagesTransformer` | `SpaceCatNinja\CloudflareImagesTransformer\CloudflareImagesTransformer` |
| `spacecatninja\cloudflareimagestransformer\models\Settings` | `SpaceCatNinja\CloudflareImagesTransformer\Settings` |
| `spacecatninja\cloudflareimagestransformer\models\CloudflareImagesTransformedImageModel` | `SpaceCatNinja\CloudflareImagesTransformer\Data\CloudflareImagesTransformedImageModel` |
| `spacecatninja\cloudflareimagestransformer\transformers\CloudflareImages` | `SpaceCatNinja\CloudflareImagesTransformer\Transformers\CloudflareImages` |
| `spacecatninja\cloudflareimagestransformer\helpers\CloudflareImagesHelpers` | `SpaceCatNinja\CloudflareImagesTransformer\Support\CloudflareImagesHelpers` |

The `CloudflareImages` transformer no longer extends `craft\base\Component`. It implements Imager X 7's `SpaceCatNinja\ImagerX\Transformers\TransformerInterface`, and throws Imager X 7's `SpaceCatNinja\ImagerX\Exceptions\ImagerException`.

### Getting the settings

`CloudflareImagesTransformer::$plugin` was removed. The settings are a singleton in the container:

Before:

```php
$settings = \spacecatninja\cloudflareimagestransformer\CloudflareImagesTransformer::$plugin->getSettings();
```

After:

```php
$settings = app(\SpaceCatNinja\CloudflareImagesTransformer\Settings::class);
```

### Helpers

`CloudflareImagesHelpers::getQualityFromExtension()` takes a file extension or format instead of an image, and returns an integer. Use the new `CloudflareImagesHelpers::getExtension()` to get an image's extension:

Before:

```php
$quality = CloudflareImagesHelpers::getQualityFromExtension($image, $transform);
```

After:

```php
$quality = CloudflareImagesHelpers::getQualityFromExtension(CloudflareImagesHelpers::getExtension($image), $transform);
```

`CloudflareImagesHelpers::getFitValue()` and `CloudflareImagesHelpers::prepFormat()` take the transform as an optional second argument, and read `allowUpscale` and `interlace` from it before the config. `getFitValue()` returns `squeeze` for mode `stretch`, and `crop` for mode `crop` when `allowUpscale` is off.

The `CloudflareImagesTransformedImageModel` constructor takes the Cloudflare `fit` value as an optional fourth argument, to calculate whether the image is enlarged. Without it, the size is calculated as if the image is never enlarged in mode `fit`, like in version 1.
