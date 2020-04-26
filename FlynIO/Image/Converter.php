<?php

namespace FlynIO\Image;

use Intervention\Image\Image;

/**
 * Handles converting images to JPEG
 */
class Converter
{
    public function canConvert(): bool
    {
        return class_exists("\Imagick") && !empty(\Imagick::queryFormats());
    }

    public function getMimeTypesToConvert(): array
    {
        return apply_filters(
            'flynio-mimes-to-convert',
            ['image/bmp', 'image/tif', 'image/tiff']
        );
    }

    public function needsToConvert(string $mime): bool
    {
        return in_array($mime, $this->getMimeTypesToConvert());
    }

    /**
     * Converts an image's parameters to JPG format. These params come from
     * the wordpress wp_handle_upload filter.
     *
     * @param array $params
     * @return array
     */
    public function convert(array $params): array
    {
        $ext = pathinfo($params['file'], PATHINFO_EXTENSION);
        $params['file'] = substr($params['file'], 0, -1 * strlen($ext)) . 'jpg';
        $params['url'] = substr($params['url'], 0, -1 * strlen($ext)) . 'jpg';
        $params['type'] = 'image/jpeg';

        return $params;
    }
}
