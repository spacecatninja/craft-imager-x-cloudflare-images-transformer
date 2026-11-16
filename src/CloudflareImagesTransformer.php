<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2026 André Elvan
 */

namespace SpaceCatNinja\CloudflareImagesTransformer;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\PluginSettings;
use SpaceCatNinja\CloudflareImagesTransformer\Transformers\CloudflareImages;
use SpaceCatNinja\ImagerX\Transformers\Transformers;

class CloudflareImagesTransformer extends Plugin
{
    // Public Methods
    // =========================================================================

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, fn() => $this->getSettings());
    }

    public function boot(Transformers $transformers): void
    {
        // Register transformer with Imager X
        $transformers->register('cloudflareimages', CloudflareImages::class);
    }

    // Protected Methods
    // =========================================================================

    protected static function createSettings(): ?PluginSettings
    {
        return new Settings();
    }
}
