<?php

namespace FlynIO;

use FlynIO\Image\Optimizer;
use FlynIO\Image\Scaler;
use FlynIO\Image\Converter;
use Intervention\Image\ImageManager;

class FlynIO
{
    public function __construct()
    {
        // Add backend menu pages and actions
        $backend = new Backend();
        $backend->init();

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
        $scaler = new Scaler();
        $converter = new Converter();

        if ($scaler->canScale() && $converter->canConvert()) {
            $manager = new ImageManager(['driver' => 'imagick']);
            $img = $manager->make($params['file']);

            // Scale if we need to
            if ($scaler->needsToScale($img)) {
                $scaled = $scaler->scale($img);
            }

            // Do we need to convert?
            $converting = $converter->needsToConvert($params['type']);
            if ($converting) {
                $params = $converter->convert($params);
            }

            // Save the image if we modified it
            if ($scaled || $converting) {
                $img->save($params['file']);
            }

            if ($converting) {
                unlink($originalFilepath);
            }
        }

        // Optimize the image
        $optimizer = new Optimizer();
        $optimizer->optimize($params['file']);

        return $params;
    }
}
