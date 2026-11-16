# Cloudflare Images transformer for Imager X Changelog

## 2.0.0 - Unreleased

> [!IMPORTANT]
> Version 2 requires Craft CMS 6, Imager X 7 and PHP 8.5, the config file moves to `config/craft/`, and there are breaking changes for code that uses the plugin's classes. See [UPGRADE.md](UPGRADE.md).

### Added
- Added `CloudflareImagesHelpers::getExtension()`.

### Changed
- The plugin now requires Craft CMS 6, Imager X 7.0.0-beta.2 or later, and PHP 8.5, and uses Craft 6's native plugin APIs. It doesn't need the Yii 2 adapter.
- The config file is now `config/craft/imager-x-cloudflare-images-transformer.php`, and is a plain Laravel config file.
- Classes moved to the `SpaceCatNinja\CloudflareImagesTransformer` namespace, and to Laravel-style folders.
- The transformer is now registered with Imager X's `Transformers` registry.
- `CloudflareImagesHelpers::getQualityFromExtension()` now takes a file extension or format instead of an image, and returns an integer.
- `CloudflareImagesHelpers::getFitValue()` and `CloudflareImagesHelpers::prepFormat()` now take the transform, to read its settings.

### Removed
- Removed the `CloudflareImagesTransformer::$plugin` property.

### Fixed
- Fixed `webpQuality`, `avifQuality` and `jxlQuality` being ignored when a transform set `format`. The quality came from `jpegQuality`.
- Fixed `allowUpscale` and `interlace` being ignored when they were set in a transform instead of in the config.
- Fixed a `background` in `transformerParams` being replaced by the letterbox color when using mode `letterbox`.
- Fixed mode `crop` enlarging images when `allowUpscale` was off. It now uses Cloudflare's `crop` fit instead of `cover` then, and the transformed image's size is no larger than the source.
- Fixed mode `stretch` cropping the image. It now uses Cloudflare's `squeeze` fit, which stretches the image to the exact size, instead of `cover`.
- Fixed the size of images transformed with mode `fit`. With `allowUpscale` on, Cloudflare enlarges them to fit the size, but the size was capped at the source size. With it off, a transform with only a `width` or `height` was reported larger than the source.
- Fixed the README example for combining a local transform with Cloudflare.

## 1.0.2 - 2026-08-28

### Fixed
- Fixed an issue where a transformed image's dimensions were reported as zero, when only one of `width` or `height` was set and the source image's dimensions could not be determined.

## 1.0.1 - 2026-03-07

### Added
- Added `zoom` to $validParams (thanks, @low)

### Fixed
- Fixed division-by-zero error (thanks, @elivz)

## 1.0.0 - 2025-07-18

### Added
- Initial release
