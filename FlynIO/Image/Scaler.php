<?php

namespace FlynIO\Image;

use Intervention\Image\Image;

/**
 * Handles scaling an imaage into min/max dimensions
 */
class Scaler
{
    public Image $image;
    public array $minDimensions = [100, 100];
    public array $maxDimensions = [3840, 3840];

    /**
     * Constructor
     *
     * @param Image $image
     * @param array $minDimensions  [int width = 100, int height = 100]
     * @param array $maxDimensions  [int width = 3840, int height = 3840]
     * @return void
     */
    public function __construct(Image $image, array $minDimensions = [100, 100], array $maxDimensions = [3840, 3840])
    {
        $this->image = $image;
        $this->minDimensions = $minDimensions;
        $this->maxDimensions = $maxDimensions;
    }

    public function scale(): bool
    {
        if (!$this->needsToScale()) {
            return false;
        }

        // Resize up to min proportions
        if ($this->image->width() < $this->minDimensions[0]) {
            $this->image->widen($this->minDimensions[0]);
        }
        if ($this->image->height() < $this->minDimensions[1]) {
            $this->image->heighten($this->minDimensions[1]);
        }

        //Resize down to max proportions
        if ($this->image->width() > $this->maxDimensions[0]) {
            $this->image->widen($this->maxDimensions[0]);
        }
        if ($this->image->height() > $this->maxDimensions[1]) {
            $this->image->heighten($this->maxDimensions[1]);
        }

        return true;
    }

    /**
     * Returns whether or not the current image needs to scale depending on
     * the given min/max dimensions.
     *
     * @return boolean
     */
    public function needsToScale(): bool
    {
        $width = $this->image->width();
        $height = $this->image->height();

        return
            $width < $this->minDimensions[0] ||
            $width > $this->maxDimensions[0] ||
            $height < $this->minDimensions[1] ||
            $height > $this->maxDimensions[1];
    }
}
