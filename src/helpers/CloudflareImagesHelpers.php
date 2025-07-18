<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2025 André Elvan
 */

namespace spacecatninja\cloudflareimagestransformer\helpers;

use craft\elements\Asset;
use spacecatninja\cloudflareimagestransformer\CloudflareImagesTransformer;
use spacecatninja\imagerx\models\ConfigModel;
use spacecatninja\imagerx\services\ImagerService;
use function SSNepenthe\ColorUtils\color;
use function SSNepenthe\ColorUtils\red;
use function SSNepenthe\ColorUtils\blue;
use function SSNepenthe\ColorUtils\green;
use function SSNepenthe\ColorUtils\rgba;

class CloudflareImagesHelpers
{

    /**
     * Generates the image path based on the provided image URL and domain configuration.
     *
     * @param \craft\elements\Asset|string $image      The image asset or image URL as a string.
     * @param string                       $zoneDomain The domain to be used for URL validation and modification.
     *
     * @return string The resulting image path after processing.
     * @throws \yii\base\InvalidConfigException
     */
    public static function getImagePath(Asset|string $image, string $zoneDomain): string
    {
        $imageUrl = is_string($image) ? $image : $image->getUrl();

        if (str_starts_with($imageUrl, '//')) {
            $imageUrl = 'https:'.$imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return $imageUrl;
        }

        // Check if the url starts with http:// or https:// and the zone domain, with or without www, and if it does, strip away the protocol and domain.
        if ($zoneDomain !== '' && (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://'))) {
            // Create a pattern that matches the domain exactly (with or without www) after the protocol
            $pattern = '#^https?://(?:www\.)?'.preg_quote($zoneDomain, '#').'(?:/|$)#';

            // Check if URL matches the domain pattern and strip away protocol and domain if it does
            if (preg_match($pattern, $imageUrl)) {
                $imageUrl = preg_replace($pattern, '/', $imageUrl);
            }
        }

        return $imageUrl;
    }

    /**
     * Gets the quality setting based on the extension.
     *
     * @param \craft\elements\Asset|string $image
     * @param array|null                   $transform
     *
     * @return string
     */
    public static function getQualityFromExtension(Asset|string $image, array $transform = null): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        if (is_string($image)) {
            $ext = pathinfo($image, PATHINFO_EXTENSION);
        } else {
            $ext = $image->getExtension();
        }

        switch ($ext) {
            case 'png':
                $pngCompression = $config->getSetting('pngCompressionLevel', $transform);

                return max(100 - ($pngCompression * 10), 1);
            case 'webp':
                return $config->getSetting('webpQuality', $transform);
            case 'avif':
                return $config->getSetting('avifQuality', $transform);
            case 'jxl':
                return $config->getSetting('jxlQuality', $transform);
        }

        return $config->getSetting('jpegQuality', $transform);
    }

    /**
     * Determines the gravity value based on the image and provided parameters.
     *
     * @param \craft\elements\Asset|string $image  The image asset or file path.
     * @param array                        $params An array of parameters, including position.
     *
     * @return string The calculated gravity value in the format "x,y" or "auto".
     */
    public static function getGravityValue(Asset|string $image, array $params): string
    {
        /** @var \spacecatninja\cloudflareimagestransformer\models\Settings $settings */
        $settings = CloudflareImagesTransformer::$plugin->getSettings();
        
        if (isset($params['position'])) {
            $focalPoint = explode(' ', $params['position']);
            $left = (float)($focalPoint[0] ?? 50);
            $top = (float)($focalPoint[1] ?? 50);
        } else if (is_string($image)) {
            if ($settings->autoGravityWhenNoFocalPoint) {
                return 'auto';
            } 
        
            $config = ImagerService::getConfig();
            $focalPoint = explode(' ', $config->position);
            $left = (float)($focalPoint[0] ?? 50);
            $top = (float)($focalPoint[1] ?? 50);
        } else {
            if (!$image->hasFocalPoint && $settings->autoGravityWhenNoFocalPoint) {
                return 'auto';
            }
            
            $left = $image->getFocalPoint()['x'] * 100;
            $top = $image->getFocalPoint()['y'] * 100;
        }

        $left /= 100;
        $top /= 100;

        return $left.'x'.$top;
    }

    /**
     * Gets the fit value for Cloudflare Images, based on the Imager X transform mode.
     *
     * @param string $mode
     *
     * @return string
     */   
    public static function getFitValue(string $mode): string
    {
        $config = ImagerService::getConfig();

        if ($mode === 'croponly') {
            \Craft::error('The Cloudflare Images Transformer does not support mode `croponly`, reverting to `crop`.', __METHOD__);
            $mode = 'crop';
        }

        return match ($mode) {
            'fit' => $config->allowUpscale ? 'contain' : 'scale-down',
            'stretch', 'crop' => 'cover',
            'letterbox' => 'pad',
            default => 'crop',
        };
    }

    /**
     * Builds a URL by combining a base zone URL, an image path, and query parameters.
     *
     * @param string $zoneUrl   The base URL of the zone.
     * @param string $imagePath The path of the image to append to the URL.
     * @param array  $params    An associative array of query parameters to include in the URL.
     *
     * @return string The fully constructed URL.
     */
    public static function buildUrl(string $zoneUrl, string $imagePath, array $params): string
    {
        $segments = [rtrim($zoneUrl, '/')];
        
        $paramsArr = [];
        
        foreach ($params as $key => $val) {
            $paramsArr[] = $key.'='.urlencode($val);
        }
        
        $segments[] = implode(',', $paramsArr);
        $segments[] = ltrim($imagePath, '/');
        
        return implode('/', $segments);
    }

    /**
     * Converts letterbox definition to cloudflare color string
     * 
     * @param array $letterboxDef
     *
     * @return string
     */
    public static function getLetterboxColor(array $letterboxDef): string
    {
        $color = $letterboxDef['color'] ?? '#000';
        $opacity = $letterboxDef['opacity'] ?? 0;
        
        $colorObj = color($color);
        
        return rgba(red($colorObj), green($colorObj), blue($colorObj), $opacity)->getRgb();
    }

    /**
     * Prepares the format for Cloudflare Images.
     * jpg should be jpeg, and if interlace is disabled, we need to set the baseline-jpeg format.
     * 
     * @param $format
     *
     * @return string
     */
    public static function prepFormat($format): string
    {
        $config = ImagerService::getConfig();
        
        if ($format === 'jpg') {
            $format = 'jpeg';
        }
        
        if ($format === 'jpeg' && !$config->interlace) {
            return 'baseline-jpeg';
        }
        
        return $format;
    }
}
