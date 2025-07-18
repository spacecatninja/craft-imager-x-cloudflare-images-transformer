<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2025 André Elvan
 */

namespace spacecatninja\cloudflareimagestransformer\transformers;

use craft\base\Component;
use craft\elements\Asset;

use spacecatninja\cloudflareimagestransformer\CloudflareImagesTransformer;
use spacecatninja\cloudflareimagestransformer\helpers\CloudflareImagesHelpers;
use spacecatninja\cloudflareimagestransformer\models\CloudflareImagesTransformedImageModel;
use spacecatninja\cloudflareimagestransformer\models\Settings;
use spacecatninja\imagerx\services\ImagerService;
use spacecatninja\imagerx\transformers\TransformerInterface;
use spacecatninja\imagerx\exceptions\ImagerException;

class CloudflareImages extends Component implements TransformerInterface
{

    /**
     * All valid cloudflare images transform params, see: https://developers.cloudflare.com/images/transform-images/transform-via-url/
     */
    public static array $validParams = [
        'width',
        'height',
        'anim',
        'background',
        'blur',
        'border',
        'brightness',
        'compression',
        'contrast',
        'dpr',
        'fit',
        'flip',
        'format',
        'gamma',
        'gravity',
        'metadata',
        'onerror',
        'quality',
        'rotate',
        'saturation',
        'sharpen',
        'slow-connection-quality',
    ];
    
    /**
     * @param Asset|string $image
     * @param array $transforms
     *
     * @return array|null
     *
     * @throws ImagerException|\yii\base\InvalidConfigException
     */
    public function transform(Asset|string $image, array $transforms): ?array
    {
        $transformedImages = [];

        foreach ($transforms as $transform) {
            $transformedImages[] = $this->getTransformedImage($image, $transform);
        }

        return $transformedImages;
    }

    /**
     * @param Asset|string $image
     * @param array $transform
     *
     * @return CloudflareImagesTransformedImageModel
     * @throws ImagerException|\yii\base\InvalidConfigException
     */
    private function getTransformedImage(Asset|string $image, array $transform): CloudflareImagesTransformedImageModel
    {
        /** @var Settings $settings */
        $settings = CloudflareImagesTransformer::$plugin->getSettings();
        $config = ImagerService::getConfig();
        $transformerParams = $transform['transformerParams'] ?? [];
        
        if (empty($settings->zoneDomain)) {
            throw new ImagerException('The Cloudflare Images Transformer requires a zone domain to be set in the plugin settings.');
        }
        
        $imagePath = CloudflareImagesHelpers::getImagePath($image, $settings->zoneDomain);
        $zoneUrl = (!str_starts_with($settings->zoneDomain, 'http') ? 'https://' : '') . rtrim($settings->zoneDomain, '/') . '/cdn-cgi/image/'; 
        
        // Merge all params
        $params = [...$settings->defaultParams, ...$transform, ...$transformerParams];
        
        // Get quality
        if (empty($params['quality'])) {
            if (isset($params['format'])) {
                $params['quality'] = CloudflareImagesHelpers::getQualityFromExtension($params['format'], $params);
            } else {
                $params['quality'] = CloudflareImagesHelpers::getQualityFromExtension($image, $params);
            }
        }

        // Get correct format
        if (!empty($params['format'])) {
            $params['format'] = CloudflareImagesHelpers::prepFormat($params['format']);
        }
        
        // Get correct fit from mode
        $params['fit'] = CloudflareImagesHelpers::getFitValue($params['mode'] ?? 'crop');
        
        // Add gravity if not set and fit is cover or crop
        if (isset($params['width'], $params['height']) && ($params['fit'] === 'cover' || $params['fit'] === 'crop') && !isset($params['gravity'])) {
            $params['gravity'] = CloudflareImagesHelpers::getGravityValue($image, $params);
        }
        
        // Add letterbox color if not set and fit is pad
        if ($params['fit'] === 'pad' && !isset($transform['background'])) {
            $letterboxDef = $config->getSetting('letterbox', $transform);
            $params['background'] = CloudflareImagesHelpers::getLetterboxColor($letterboxDef);
        }
        
        // Prune params
        $params = array_intersect_key($params, array_flip(self::$validParams));
        
        // Create the final URL
        $url = CloudflareImagesHelpers::buildUrl($zoneUrl, $imagePath, $params);
        
        return new CloudflareImagesTransformedImageModel($url, $image, $transform);
    }
    
    
}
