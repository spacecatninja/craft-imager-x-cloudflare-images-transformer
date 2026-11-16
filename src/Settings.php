<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2025 André Elvan
 */

namespace SpaceCatNinja\CloudflareImagesTransformer;

use craft\base\Model;

class Settings extends Model
{
    public string $zoneDomain = '';
    public bool $autoGravityWhenNoFocalPoint = false;
    public array $defaultParams = [];
}
