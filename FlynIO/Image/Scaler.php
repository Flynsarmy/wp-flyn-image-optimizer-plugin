<?php

namespace FlynIO\Image;

use Intervention\Image\Image;

/**
 * Handles scaling an imaage into min/max dimensions
 */
class Scaler
{
    public function canScale(): bool
    {
        return class_exists("\Imagick") && !empty(\Imagick::queryFormats());
    }

    public function getAllowedDimensions(): array
    {
        return apply_filters('flynio-limit-dimensions', [
            [100, 100],
            [3840, 3840],
        ]);
    }

    /**
     * Scales an image to the min/max dimensions allowed.
     *
     * @param Image $image
     * @return boolean
     */
    public function scale(Image $image): bool
    {
        list($minDimensions, $maxDimensions) = $this->getAllowedDimensions();

        if (!$this->needsToScale($image, $minDimensions, $maxDimensions)) {
            return false;
        }

        // Resize up to min proportions
        if ($image->width() < $minDimensions[0]) {
            $image->widen($minDimensions[0]);
        }
        if ($image->height() < $minDimensions[1]) {
            $image->heighten($minDimensions[1]);
        }

        //Resize down to max proportions
        if ($image->width() > $maxDimensions[0]) {
            $image->widen($maxDimensions[0]);
        }
        if ($image->height() > $maxDimensions[1]) {
            $image->heighten($maxDimensions[1]);
        }

        return true;
    }

    /**
     * Returns whether or not the current image needs to scale depending on
     * the given min/max dimensions.
     *
     * @param Image $image
     * @return boolean
     */
    public function needsToScale(Image $image): bool
    {
        list($minDimensions, $maxDimensions) = $this->getAllowedDimensions();

        $width = $image->width();
        $height = $image->height();

        return
            $width < $minDimensions[0] ||
            $width > $maxDimensions[0] ||
            $height < $minDimensions[1] ||
            $height > $maxDimensions[1];
    }
}
