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

        $params = $this->preOptimizeImage($params);

        // Optimize the image
        $optimizer = new Optimizer();
        $optimizer->optimize($params['file']);

        return $params;
    }

    public function preOptimizeImage(array $params): array
    {
        // Make sure this is a type of image that we want to convert and that it exists.
        $scaler = new Scaler();
        $converter = new Converter();

        // Image magick isn't installed correctly. Nothing more can be done here.
        if (!$scaler->canScale() || !$converter->canConvert()) {
            return $params;
        }

        $originalFilepath = $params['file'];
        $manager = new ImageManager(['driver' => 'imagick']);

        try {
            $img = $manager->make($params['file']);
        } catch (\Exception $e) {
            // An error occurred loading our image file. Might be an unsupported
            // format. Nothing more can be done here.
            return $params;
        }

        // Scale if we need to
        $scaled = false;
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

        // Generate the WebP
        if (in_array('WEBP', \Imagick::queryFormats())) {
            $pathinfo = pathinfo($params['file']);
            $img->save($pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.webp');
        }
        
        return $params;
    }
}
