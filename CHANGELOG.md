# Cloudflare Images transformer for Imager X Changelog

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
