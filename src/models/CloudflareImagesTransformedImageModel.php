<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2025 André Elvan
 */

namespace spacecatninja\cloudflareimagestransformer\models;

use craft\elements\Asset;
use craft\helpers\App;
use spacecatninja\imagerx\models\BaseTransformedImageModel;
use spacecatninja\imagerx\models\TransformedImageInterface;

class CloudflareImagesTransformedImageModel extends BaseTransformedImageModel implements TransformedImageInterface
{
    /**
     * @param string|null $imageUrl
     * @param Asset|string|null  $source
     * @param array       $transform
     */
    public function __construct(string $imageUrl = null, Asset|string $source = null, array $transform = [])
    {
        if ($imageUrl !== null) {
            $this->url = $imageUrl;
        }

        $mode = $transform['mode'] ?? 'crop';

        if (isset($transform['width'], $transform['height'])) {
            $this->width = (int)$transform['width'];
            $this->height = (int)$transform['height'];

            if ($source !== null && $mode === 'fit') {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);

                $transformW = (int)$transform['width'];
                $transformH = (int)$transform['height'];

                if ($sourceWidth !== 0 && $sourceHeight !== 0) {
                    if ($sourceWidth / $sourceHeight > $transformW / $transformH) {
                        $useW = min($transformW, $sourceWidth);
                        $this->width = $useW;
                        $this->height = round($useW * ($sourceHeight / $sourceWidth));
                    } else {
                        $useH = min($transformH, $sourceHeight);
                        $this->width = round($useH * ($sourceWidth / $sourceHeight));
                        $this->height = $useH;
                    }
                }
            }
        } else if (isset($transform['width']) || isset($transform['height'])) {
            if ($source !== null && $transform !== null) {
                [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);
                [$w, $h] = $this->calculateTargetSize($transform, $sourceWidth ?? 1, $sourceHeight ?? 1);

                $this->width = $w;
                $this->height = $h;
            }
        } else {
            // Neither is set, image is not resized. Just get dimensions and return.
            [$sourceWidth, $sourceHeight] = $this->getSourceImageDimensions($source);
    
            $this->width = $sourceWidth;
            $this->height = $sourceHeight;
        }
        
        // Check if dpr is set and adjust size accordingly
        if (isset($transform['transformerParams']['dpr'])) {
            $this->width = round($this->width * $transform['transformerParams']['dpr']);
            $this->height = round($this->height * $transform['transformerParams']['dpr']);
        }
    }

    /**
     * @param Asset|string $source
     *
     * @return array
     */
    protected function getSourceImageDimensions(Asset|string $source): array
    {
        $imageWidth = 0;
        $imageHeight = 0;

        if (is_string($source)) {
            $imageInfo = @getimagesize(App::parseEnv('@webroot/'.ltrim($source, '/')));

            if (\is_array($imageInfo) && $imageInfo[0] !== '' && $imageInfo[1] !== '') {
                [$imageWidth, $imageHeight] = $imageInfo;
            }
        } else {
            $imageWidth = $source->width;
            $imageHeight = $source->height;
        }
        
        return [$imageWidth, $imageHeight];
    }

    /**
     * @param array $transform
     * @param int   $sourceWidth
     * @param int   $sourceHeight
     *
     * @return array
     */
    protected function calculateTargetSize(array $transform, int $sourceWidth, int $sourceHeight): array
    {
        $ratio = $sourceWidth / $sourceHeight;

        $w = $transform['width'] ?? null;
        $h = $transform['height'] ?? null;

        if ($w) {
            return [$w, (int)round($w / $ratio)];
        }
        if ($h) {
            return [(int)round($h * $ratio), $h];
        }

        return [0, 0];
    }
}
