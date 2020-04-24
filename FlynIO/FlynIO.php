<?php

namespace FlynIO;

use FlynIO\Image\Scaler;
use Intervention\Image\ImageManager;

class FlynIO
{
    public const MIN_WIDTH = 100;
    public const MIN_HEIGHT = 100;
    public const MAX_WIDTH = 3840;
    public const MAX_HEIGHT = 3840;

    public function __construct()
    {
        add_filter('wp_handle_upload', [$this, 'handleUpload']);
    }

    /**
     * Handles scaling and converting of images on upload. This takes place
     * before the thumbs are all created.
     *
     * @param array $params
     * @return void
     */
    public function handleUpload(array $params)
    {
        // If "notamper" is included in the filename then we will bypass scaling/optimization.
        if (strpos($params['file'], 'notamper') !== false) {
            return $params;
        }

        // Only modify images
        if (strpos($params['type'], 'image') === false) {
            return $params;
        }

        // Make sure this is a type of image that we want to convert and that it exists.
        $originalFilepath = $params['file'];

        $manager = new ImageManager(['driver' => 'imagick']);
        $img = $manager->make($params['file']);

        // Scale if we need to
        $scaler = new Scaler($img, [self::MIN_WIDTH, self::MIN_HEIGHT], [self::MAX_WIDTH, self::MAX_HEIGHT]);
        $scaled = $scaler->scale();

        // Do we need to convert?
        $converted = false;
        if (in_array($params['type'], ['image/bmp', 'image/tif', 'image/tiff'])) {
            $ext = pathinfo($params['file'], PATHINFO_EXTENSION);
            $params['file'] = substr($params['file'], 0, -1 * strlen($ext)) . 'jpg';
            $params['url'] = substr($params['url'], 0, -1 * strlen($ext)) . 'jpg';
            $params['type'] = 'image/jpeg';
            $converted = true;
        }

        // Did we modify the image at all?
        if ($scaled || $converted) {
            $img->save($params['file']);
        }

        if ($converted) {
            unlink($originalFilepath);
        }

        // Optimize the image
        $options = apply_filters('flyn-wpio-optimizer-options', []);
        $logger = apply_filters('flyn-wpio-optimizer-logger', new \Psr\Log\NullLogger());
        $factory = new \ImageOptimizer\OptimizerFactory($options, $logger);
        
        $factory->get()->optimize($params['file']);

        return $params;
    }
}
