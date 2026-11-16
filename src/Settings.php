<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\CloudflareImagesTransformer;

use CraftCms\Cms\Plugin\PluginSettings;

class Settings extends PluginSettings
{
    public string $zoneDomain = '';
    public bool $autoGravityWhenNoFocalPoint = false;
    public array $defaultParams = [];
}
