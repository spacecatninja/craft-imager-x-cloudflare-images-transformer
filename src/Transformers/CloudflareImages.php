<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\CloudflareImagesTransformer\Transformers;

use CraftCms\Cms\Asset\Elements\Asset;
use SpaceCatNinja\CloudflareImagesTransformer\Data\CloudflareImagesTransformedImageModel;
use SpaceCatNinja\CloudflareImagesTransformer\Settings;
use SpaceCatNinja\CloudflareImagesTransformer\Support\CloudflareImagesHelpers;
use SpaceCatNinja\ImagerX\Exceptions\ImagerException;
use SpaceCatNinja\ImagerX\Services\ImagerService;
use SpaceCatNinja\ImagerX\Transformers\TransformerInterface;

class CloudflareImages implements TransformerInterface
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
        'zoom',
    ];
    
    /**
     * @param Asset|string $image
     * @param array $transforms
     *
     * @return array|null
     *
     * @throws ImagerException
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
     * @throws ImagerException
     */
    private function getTransformedImage(Asset|string $image, array $transform): CloudflareImagesTransformedImageModel
    {
        $settings = app(Settings::class);
        $config = ImagerService::getConfig();
        $transformerParams = $transform['transformerParams'] ?? [];
        
        if (empty($settings->zoneDomain)) {
            throw new ImagerException('The Cloudflare Images Transformer requires a zone domain to be set in the plugin settings.');
        }
        
        $imagePath = CloudflareImagesHelpers::getImagePath($image, $settings->zoneDomain);
        $zoneUrl = (!str_starts_with($settings->zoneDomain, 'http') ? 'https://' : '') . rtrim($settings->zoneDomain, '/') . '/cdn-cgi/image/'; 
        
        // Merge all params
        $params = [...$settings->defaultParams, ...$transform, ...$transformerParams];
        
        // Get quality, from the format the image is transformed to, or the image's own
        if (empty($params['quality'])) {
            $params['quality'] = CloudflareImagesHelpers::getQualityFromExtension($params['format'] ?? CloudflareImagesHelpers::getExtension($image), $params);
        }

        // Get correct format
        if (!empty($params['format'])) {
            $params['format'] = CloudflareImagesHelpers::prepFormat($params['format'], $params);
        }
        
        // Get correct fit from mode
        $params['fit'] = CloudflareImagesHelpers::getFitValue($params['mode'] ?? 'crop', $params);
        
        // Add gravity if not set and fit is cover or crop
        if (isset($params['width'], $params['height']) && ($params['fit'] === 'cover' || $params['fit'] === 'crop') && !isset($params['gravity'])) {
            $params['gravity'] = CloudflareImagesHelpers::getGravityValue($image, $params);
        }
        
        // Add letterbox color if not set and fit is pad
        if ($params['fit'] === 'pad' && !isset($transform['background']) && !isset($transformerParams['background'])) {
            $letterboxDef = $config->getSetting('letterbox', $transform);
            $params['background'] = CloudflareImagesHelpers::getLetterboxColor($letterboxDef);
        }
        
        // Prune params
        $params = array_intersect_key($params, array_flip(self::$validParams));
        
        // Create the final URL
        $url = CloudflareImagesHelpers::buildUrl($zoneUrl, $imagePath, $params);
        
        return new CloudflareImagesTransformedImageModel($url, $image, $transform, $params['fit']);
    }
    
    
}
