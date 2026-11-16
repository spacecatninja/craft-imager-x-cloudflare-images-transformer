<?php
/**
 * Cloudflare Images transformer for Imager X
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2025 André Elvan
 */

namespace SpaceCatNinja\CloudflareImagesTransformer;

use craft\base\Model;
use craft\base\Plugin;

use SpaceCatNinja\CloudflareImagesTransformer\Settings;
use SpaceCatNinja\CloudflareImagesTransformer\Transformers\CloudflareImages;

use yii\base\Event;

class CloudflareImagesTransformer extends Plugin
{
    // Static Properties
    // =========================================================================

    public static CloudflareImagesTransformer $plugin;

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;
        
        // Register transformer with Imager
        Event::on(\spacecatninja\imagerx\ImagerX::class,
            \spacecatninja\imagerx\ImagerX::EVENT_REGISTER_TRANSFORMERS,
            static function (\spacecatninja\imagerx\events\RegisterTransformersEvent $event) {
                $event->transformers['cloudflareimages'] = CloudflareImages::class;
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

}
