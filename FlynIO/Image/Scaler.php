<?php

namespace FlynIO\Image;

use Intervention\Image\Image;

/**
 * Handles scaling an imaage into min/max dimensions
 */
class Scaler
{
    /**
     * [x, y]
     */
    public array $minDimensions;

    /**
     * [x, y]
     */
    public array $maxDimensions;

    /**
     * Constructor
     */
    public function __construct()
    {
        list($minDimensions, $maxDimensions) = $this->getAllowedDimensions();
        $this->minDimensions = $minDimensions;
        $this->maxDimensions = $maxDimensions;
    }

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
        if (!$this->needsToScale($image)) {
            return false;
        }

        // Resize up to min proportions
        if ($image->width() < $this->minDimensions[0]) {
            $image->widen($this->minDimensions[0]);
        }
        if ($image->height() < $this->minDimensions[1]) {
            $image->heighten($this->minDimensions[1]);
        }

        //Resize down to max proportions
        if ($image->width() > $this->maxDimensions[0]) {
            $image->widen($this->maxDimensions[0]);
        }
        if ($image->height() > $this->maxDimensions[1]) {
            $image->heighten($this->maxDimensions[1]);
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
        $width = $image->width();
        $height = $image->height();

        return
            $width < $this->minDimensions[0] ||
            $width > $this->maxDimensions[0] ||
            $height < $this->minDimensions[1] ||
            $height > $this->maxDimensions[1];
    }
}
