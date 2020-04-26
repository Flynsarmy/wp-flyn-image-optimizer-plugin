<?php

namespace FlynIO;

use FlynIO\Image\Optimizer;
use FlynIO\Image\PictureTags;
use FlynIO\Image\Scaler;
use FlynIO\Image\Converter;
use FlynIO\Image\WebP;
use Intervention\Image\ImageManager;

class FlynIO
{
    public function __construct()
    {
        // Add backend menu pages and actions
        $backend = new Backend();
        $backend->init();

        if (class_exists('\WP_CLI')) {
            \WP_CLI::add_command('flynio', new \FlynIO\CLI());
        }

        // Scale/convert/optimize full size image on upload
        add_filter('wp_handle_upload', [$this, 'handleUpload']);
    
        // Generate WebP's of thumb sizes on creation
        add_filter('wp_generate_attachment_metadata', [$this, 'handleThumbGeneration'], 10, 3);
        // Delete WebP's along with jpegs/pngs
        add_filter('wp_delete_file', [$this, 'onDeleteFile'], 10, 2);
        // Change <img> tags to <picture> tags to display our WebP's in frontned post content
        add_filter('the_content', [$this, 'filterTheContent'], 999);
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

    /**
     * Generate WebP files for all image sizes on upload
     *
     * @param array $metadata
     * @param integer $attachment_id
     * @param string $context
     * @return array
     */
    public function handleThumbGeneration(array $metadata, int $attachment_id, string $context): array
    {
        if (!apply_filters('flynio-generate-webp-images', true)) {
            return $metadata;
        }

        try {
            $webp = new WebP();

            if (!$webp->canConvert()) {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                throw new \Exception("The WebP generator needs both imagick PHP extension with WEBP support and imagemagick app installed on your server to operate.");
            }
        } catch (\Exception $e) {
            // Requirements for WebP generation not met. Nothing to do here.
            return $metadata;
        }

        $manager = new ImageManager(['driver' => 'imagick']);

        // Get the full directory path the images are uploaded to
        $dir = wp_upload_dir()['basedir'] . '/' . dirname($metadata['file']) . '/';

        foreach ($metadata['sizes'] as $size) {
            // We're only creating WebP's of jpegs and pngs
            if (!in_array($size['mime-type'], ['image/jpeg', 'image/png'])) {
                continue;
            }

            $fromFilePath = $dir . $size['file'];
            $img = $manager->make($fromFilePath);

            try {
                $webp->convert($img, $fromFilePath . '.webp');
            } catch (\Exception $e) {
                continue;
            }
        }

        return $metadata;
    }

    /**
     * Delete WebP images along with jpegs/pngs
     *
     * @param string $filename
     * @return string
     */
    public function onDeleteFile(string $filename): string
    {
        if (!apply_filters('flynio-generate-webp-images', true)) {
            return $filename;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        // If we're deleting a JPG or PNG
        if (in_array($ext, ['jpg', 'png'])) {
            // Delete the equivalent WebP file
            @unlink($filename . ".webp");
        }

        return $filename;
    }

    /**
     * Scale, Convert and generate WebP copy of an image
     *
     * @param array $params
     * @return array
     */
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
        $scaled = $scaler->scale($img);

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
        if (apply_filters('flynio-generate-webp-images', true)) {
            try {
                $webp = new WebP();
    
                if ($webp->canConvert()) {
                    $webp->convert($img, $fromFilePath . '.webp');
                }
            } catch (\Exception $e) {
                ;
            }
        }
        
        return $params;
    }

    public function filterTheContent(string $content): string
    {
        if (!apply_filters('flynio-use-webp-images', false)) {
            return $content;
        }

        return (new PictureTags())->replace($content);
    }
}
